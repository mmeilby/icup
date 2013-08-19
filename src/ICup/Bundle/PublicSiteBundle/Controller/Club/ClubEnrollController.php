<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Serializer\Exception\Exception;

/**
 * List the categories and groups available
 */
class ClubEnrollController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/club/enroll/list", name="_club_enroll_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listenrolled.html.twig")
     */
    public function listAction() {
        $this->get('util')->setupController($this);
        $tournamentid = $this->get('util')->getTournament($this);

        $em = $this->getDoctrine()->getManager();
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new Exception("This controller is not available for anonymous users");
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->findOneBy(array('username' => $user->getUsername()));
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list', array('tournamentid' => $tournamentid)));
        }
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournamentid), array('classification' => 'asc', 'gender' => 'asc'));
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where c.pid=:tournament and e.pid=c.id and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('tournament', $tournamentid);
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
            'categories' => $categoryMap,
            'error' => isset($error) ? $error : null);
    }

    /**
     * Add new category
     * @Route("/club/enroll/add/{categoryid}", name="_club_enroll_add")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function addAction($categoryid) {
        $this->get('util')->setupController($this);
        $tournamentid = $this->get('util')->getTournament($this);

        $em = $this->getDoctrine()->getManager();
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new Exception("This controller is not available for anonymous users");
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->findOneBy(array('username' => $user->getUsername()));
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list', array('tournamentid' => $tournamentid)));
        }
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null || $category->getPid() != $tournamentid) {
            $error = "FORM.ERROR.BADCATEGORY";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();
 
        $noTeams = array_count_values($enrolled);
        if ($noTeams >= 20) {
            $error = "FORM.ERROR.NOMORETEAMS";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        
        $team = new Team();
        $team->setPid($club->getId());
        $team->setName($club->getName());
        $team->setDivision($noTeams + 'A');
        $em->persist($team);
        $em->flush();
        
        $today = new DateTime();
        $enroll = new Enrollment();
        $enroll->setCid($team->getId());
        $enroll->setPid($categoryid);
        $enroll->setDate($today->format('d/m/Y'));
        $em->persist($enroll);
        $em->flush();

        return $this->redirect($this->generateUrl('_club_enroll_list'));
    }
    
    /**
     * Remove category from the register - including all related groups and match results
     * @Route("/club/enroll/del/{categoryid}", name="_club_enroll_del")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function delAction($categoryid) {
        $this->get('util')->setupController($this);
        $tournamentid = $this->get('util')->getTournament($this);

        $em = $this->getDoctrine()->getManager();
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new Exception("This controller is not available for anonymous users");
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->findOneBy(array('username' => $user->getUsername()));
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list', array('tournamentid' => $tournamentid)));
        }
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null || $category->getPid() != $tournamentid) {
            $error = "FORM.ERROR.BADCATEGORY";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();
 
        $enroll = array_pop(&$enrolled);
        if ($enroll == null) {
            $error = "FORM.ERROR.NOMORETEAMS";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
                
        $team = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team')->find($enroll->getCid());
        $em->remove($team);
        
        $em->remove($enroll);
        $em->flush();

        return $this->redirect($this->generateUrl('_club_enroll_list'));
    }
}
