<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\User\User;

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
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->findOneBy(array('username' => $user->getUsername()));
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list', array('tournamentid' => $tournament->getId())));
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
            'categories' => $categoryMap,
            'error' => isset($error) ? $error : null);
    }

    /**
     * Add new category
     * @Route("/club/enroll/add/{categoryid}", name="_club_enroll_add")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function addAction($categoryid) {
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
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->findOneBy(array('username' => $user->getUsername()));
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list', array('tournamentid' => $tournament->getId())));
        }
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null || $category->getPid() != $tournament->getId()) {
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
 
        $noTeams = count($enrolled);
        if ($noTeams >= 20) {
            $error = "FORM.ERROR.NOMORETEAMS";
            return $this->redirect($this->generateUrl('_club_enroll_list'));
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
        $enroll->setDate($today->format('d/m/Y'));
        $em->persist($enroll);
        $em->flush();

        return $this->redirect($this->generateUrl('_club_enroll_list'));
    }
    
    /**
     * Remove category from the register - including all related groups and match results
     * @Route("/club/enroll/del/{categoryid}", name="_club_enroll_del")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function delAction($categoryid) {
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
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->findOneBy(array('username' => $user->getUsername()));
        if ($club == null) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list', array('tournamentid' => $tournament->getId())));
        }
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null || $category->getPid() != $tournament->getId()) {
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
    
    /**
     * Enrolls a club in a tournament
     * @Route("/enroll", name="_club_enroll")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function enrollAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user != null) {
            /* @var $club Club */
            $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                                ->findOneBy(array('username' => $user->getUsername()));
            if ($club != null) {
                // Controller is called by non editor or admin - switch to club edit view
                return $this->redirect($this->generateUrl('_club_chg', array('clubid' => $club->getId())));
            }
        }
        
        $club = new Club();
        $form = $this->makeClubForm($club, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        if ($form->isValid()) {
            if (array_search($club->getUsername(), array(1 => 'admin', 2 => 'editor'))) {
                $form->addError(new FormError('Can not use editor or admin'));
            }
            else if ($em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->findOneBy(array('username' => $club->getUsername())) != null) {
                $form->addError(new FormError('Username in use'));
            }
            else {
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($club);
                $password = $encoder->encodePassword($club->getPassword(), $club->getSalt());
                echo $password;
                $club->setPassword($password);
                $em->persist($club);
                $em->flush();
                return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig');
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club, 'error' => isset($error) ? $error : null);
    }
    
    private function makeClubForm($club, $action) {
        $countries = array();
        foreach (array_keys($this->get('util')->getCountries()) as $ccode) {
            $countries[$ccode] = $ccode;
        }
        $formDef = $this->createFormBuilder($club);
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'lang'));
        $formDef->add('username', 'text', array('label' => 'FORM.CLUB.USERNAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('password', 'password', array('label' => 'FORM.CLUB.PASSWORD', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUB.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUB.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
}
