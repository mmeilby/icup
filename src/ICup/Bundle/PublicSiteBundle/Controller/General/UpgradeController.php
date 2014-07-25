<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;

class UpgradeController extends Controller
{
    /**
     * @Route("/admin/upgrade/2")
     */
    public function upgradeAction()
    {
        $em = $this->getDoctrine()->getManager();
        // matches.time
        // matches.date
        $qb = $em->createQuery("select m ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ");
        $matches = $qb->getResult();
        foreach ($matches as $match) {
            $matchdate = date_create_from_format("d/m/Y", $match->getDate());
            $match->setDate(date_format($matchdate, $this->container->getParameter('db_date_format')));
            $matchtime = date_create_from_format("H:i", str_replace(".", ":", $match->getTime()));
            $match->setTime(date_format($matchtime, $this->container->getParameter('db_time_format')));
        }
        // events.date
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Event e ");
        $events = $qb->getResult();
        foreach ($events as $event) {
            $eventdate = date_create_from_format("d/m/Y", $event->getDate());
            $event->setDate(date_format($eventdate, $this->container->getParameter('db_date_format')));
        }
        // enrollments.date
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e ");
        $enrollments = $qb->getResult();
        foreach ($enrollments as $enrollment) {
            $enrollmentdate = date_create_from_format("d/m/Y", $enrollment->getDate());
            $enrollment->setDate(date_format($enrollmentdate, $this->container->getParameter('db_date_format')));
        }
        // templates.last_modified
        $em->flush();
        return $this->redirect($this->generateUrl('_icup'));
     }
     
    /**
     * @Route("/admin/upgrade/1")
     */
    public function upgrade1Action()
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQuery("select t.id,c.id as category ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where g.pid=c.id and ".
                                     "g.classification = 0 and ".
                                     "o.pid=g.id and ".
                                     "o.cid=t.id and ".
                                     "t.id not in (select e.cid ".
                                                  "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e ".
                                                  "where e.pid=c.id) ".
                               "order by c.id");
        $teams = $qb->getResult();
        $user = $this->get('util')->getCurrentUser();
        $enrollmentdate = date_create_from_format("d/m/Y", '01/01/2000');
        foreach ($teams as $team) {
            $enroll = new Enrollment();
            $enroll->setCid($team['id']);
            $enroll->setPid($team['category']);
            $enroll->setUid($user->getId());
            $enroll->setDate(date_format($enrollmentdate, $this->container->getParameter('db_date_format')));
            $em->persist($enroll);
        }
        $em->flush();
        return $this->redirect($this->generateUrl('_icup'));
     }
}
