<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the categories and groups available
 */
class ClubEnrollController extends Controller
{
    /**
     * List the current enrollments for a club
     * @Route("/club/enroll/list", name="_club_enroll_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listenrolled.html.twig")
     */
    public function listAction() {
        $this->get('util')->setupController($this);
        $tournament = $this->get('util')->getTournament($this);

        $em = $this->getDoctrine()->getManager();
        if ($tournament == null) {
            return $this->render('ICupPublicSiteBundle:Errors:needatournament.html.twig');
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournament->getId()), array('classification' => 'asc', 'gender' => 'asc'));
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where c.pid=:tournament and e.pid=c.id and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('tournament', $tournament->getId());
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();

        $enrolledList = array();
        foreach ($enrolled as $enroll) {
            $enrolledList[$enroll->getPid()][] = $enroll;
        }
        
        $classMap = array();
        $categoryMap = array();
        foreach ($categories as $category) {
            $classMap[$category->getClassification()] = $category->getClassification();
            $cls = $category->getGender() . $category->getClassification();
            $categoryMap[$cls][] = $category;
        }
        return array(
            'tournament' => $tournament,
            'club' => $club,
            'classifications' => $classMap,
            'enrolled' => $enrolledList,
            'categories' => $categoryMap);
    }

    /**
     * Enrolls a club in a tournament by adding new team to category
     * @Route("/club/enroll/add/{categoryid}", name="_club_enroll_add")
     * @Method("GET")
     */
    public function addEnrollAction($categoryid) {
        $this->get('util')->setupController($this);
        $tournament = $this->get('util')->getTournament($this);

        $em = $this->getDoctrine()->getManager();
        if ($tournament == null) {
            return $this->render('ICupPublicSiteBundle:Errors:needatournament.html.twig');
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null || $category->getPid() != $tournament->getId()) {
            return $this->render('ICupPublicSiteBundle:Errors:badcategory.html.twig', array('redirect' => $this->generateUrl('_club_enroll_list')));
        }
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();
 
        $noTeams = count($enrolled);
        if ($noTeams >= 26) {
            // Can not add more than 26 teams to same category - Team A -> Team Z
            return $this->render('ICupPublicSiteBundle:Errors:nomoreteams.html.twig', array('redirect' => $this->generateUrl('_club_enroll_list')));
        }
        
        $team = new Team();
        $team->setPid($club->getId());
        $team->setName($club->getName());
        $team->setColor('');
        $team->setDivision(chr($noTeams + 65));
        $em->persist($team);
        $em->flush();
        
        $today = new DateTime();
        $enroll = new Enrollment();
        $enroll->setCid($team->getId());
        $enroll->setPid($categoryid);
        $enroll->setUid($user->getId());
        $enroll->setDate($today->format('d/m/Y'));
        $em->persist($enroll);
        $em->flush();

        return $this->redirect($this->generateUrl('_club_enroll_list'));
    }
    
    /**
     * Remove last team from category - including all related match results
     * @Route("/club/enroll/del/{categoryid}", name="_club_enroll_del")
     * @Method("GET")
     */
    public function delEnrollAction($categoryid) {
        $this->get('util')->setupController($this);
        $tournament = $this->get('util')->getTournament($this);

        $em = $this->getDoctrine()->getManager();
        if ($tournament == null) {
             return $this->render('ICupPublicSiteBundle:Errors:needatournament.html.twig');
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null || $category->getPid() != $tournament->getId()) {
            return $this->render('ICupPublicSiteBundle:Errors:badcategory.html.twig', array('redirect' => $this->generateUrl('_club_enroll_list')));
        }
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                               "order by t.division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();
 
        $enroll = array_pop(&$enrolled);
        if ($enroll == null) {
            return $this->render('ICupPublicSiteBundle:Errors:noteams.html.twig', array('redirect' => $this->generateUrl('_club_enroll_list')));
        }
                
        $team = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team')->find($enroll->getCid());
        $em->remove($team);
        
        $em->remove($enroll);
        $em->flush();

        return $this->redirect($this->generateUrl('_club_enroll_list'));
    }
}
