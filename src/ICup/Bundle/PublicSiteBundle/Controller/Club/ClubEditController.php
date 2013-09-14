<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

/**
 * List the categories and groups available
 */
class ClubEditController extends Controller
{
    /**
     * Add new club
     * @Route("/club/add", name="_club_add")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function addAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if ($user->isClub()) {
            /* @var $newclub Club */
            $newclub = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
            if ($newclub != null) {
                // Controller is called by user assigned to a club - switch to club edit view
                return $this->redirect($this->generateUrl('_club_chg_withid', array('clubid' => $newclub->getId())));
            }
        }
        
        $club = new Club();
        $form = $this->makeClubForm($club, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        if ($form->isValid()) {
            if ($em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->findOneBy(array('name' => $club->getName())) != null) {
                $form->addError(new FormError('ERROR.NAMEEXIST'));
            }
            else {
                $em->persist($club);
                $em->flush();
                if ($user->isClub()) {
                    $user->setPid($club->getId());
                    $em->flush();
                    return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig', array('club' => $club));
                }
                else {
                    return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig', array('club' => $club));
                }
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
    }
    
   /**
     * Change club information
     * @Route("/club/chg", name="_club_chg")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function chgClubAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
            
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
        if ($club == null) {
            // Controller is called by user assigned to a club - switch to club edit view
            return $this->redirect($this->generateUrl('_club_add'));
        }

        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        $form = $this->makeClubForm($club, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        if ($form->isValid()) {
            $otherClub = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->findOneBy(array('name' => $club->getName()));
            if ($otherClub != null && $otherClub->getId() != $club->getId()) {
                $form->addError(new FormError('ERROR.CANTCHANGENAME'));
            }
            else {
                $em->flush();
                return $this->redirect($this->generateUrl('_club_enroll_list'));
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
   /**
     * Delete club information
     * @Route("/club/del", name="_club_del")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function delClubAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
            
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
        if ($club == null) {
            // Controller is called by user assigned to a club - switch to club edit view
            return $this->redirect($this->generateUrl('_club_add'));
        }

        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        $form = $this->makeClubForm($club, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        if ($form->isValid()) {
            $em->remove($club);
            $user->setPid(0);
            $em->flush();
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
    private function makeClubForm($club, $action) {
        $countries = array();
        foreach (array_keys($this->get('util')->getCountries()) as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort(&$countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'lang'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUB.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUB.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * Enrolls a club in a tournament
     * @Route("/club/newuser/{clubid}", name="_club_enroll_new_user")
     * @Template("ICupPublicSiteBundle:Club:newuser.html.twig")
     */
    public function addUserAction($clubid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user != null) {
            /* @var $club Club */
            $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
            if ($club != null) {
                // Controller is called by non editor or admin - switch to club edit view
                return $this->redirect($this->generateUrl('_club_chg', array('clubid' => $club->getId())));
            }
        }
        
        $user = new User();
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        if ($form->isValid()) {
            if ($em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->findOneBy(array('username' => $user->getUsername())) != null) {
                $form->addError(new FormError('User name in use'));
            }
            else {
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                echo $password;
                $user->setPassword($password);
                $user->setRoles(array('ROLE_USER'));
                $em->persist($user);
                $em->flush();
                return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig');
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
    }
    
    private function makeUserForm($user, $action) {
        $formDef = $this->createFormBuilder($user);
        $formDef->add('name', 'text', array('label' => 'FORM.USER.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('username', 'text', array('label' => 'FORM.USER.USERNAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('password', 'password', array('label' => 'FORM.USER.PASSWORD', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.USER.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.USER.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
}
