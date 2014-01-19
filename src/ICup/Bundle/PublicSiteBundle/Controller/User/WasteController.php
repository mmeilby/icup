<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\NewClub;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * List the categories and groups available
 */
class WasteController extends Controller
{
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
    

    
    
// CHECK CODE FROM HERE - IS THIS MORE LIKE ADMIN CODE ???    
   
    /**
     * Add new club for user not related to any club
     * Current user must be a non related plain user
     * @Route("/club/add", name="_club_add")
     * @Template("ICupPublicSiteBundle:User:ausr_new_club.html.twig")
     */
    public function addAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getCurrentUser();
            // Validate current user - is it a club user/administrator?
            $this->validateClubUser($user);
            // Validate user - must be a non related club user
            if ($user->isRelated()) {
                // Controller is called by user assigned to a club - switch to club edit view
                return $this->redirect($this->generateUrl('_club_chg_withid', array('clubid' => $user->getCid())));
            }
            $club = new Club();
            $form = $this->makeClubForm($club, 'add');
            $request = $this->getRequest();
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirect($this->generateUrl('_user_my_page'));
            }
            if ($this->checkForm($form, $club, 'add')) {
                $em->persist($club);
                $em->flush();
                $user->setCid($club->getId());
                $user->setRole(User::$CLUB_ADMIN);
                $user->setStatus(User::$ATT);
                $em->flush();
                return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig', array('club' => $club));
            }
            return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
        } catch (RedirectException $rexc) {
            return $rexc->getResponse();
        } catch (ValidationException $vexc) {
            $this->get('logger')->addError("User CID/PID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => '_user_my_page'));
        }
    }

    /**
     * Change club information for current user
     * Current user must be club administrator
     * @Route("/club/chg", name="_club_chg")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function chgClubAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getCurrentUser();
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage());
        } 

        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
            
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // Controller is called by user assigned to a club - switch to club edit view
            return $this->redirect($this->generateUrl('_club_add'));
        }

        $form = $this->makeClubForm($club, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($form->isValid()) {
            $otherClub = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->findOneBy(array('name' => $club->getName()));
            if ($otherClub != null && $otherClub->getId() != $club->getId()) {
                $form->addError(new FormError('ERROR.CANTCHANGENAME'));
            }
            else {
                $em->flush();
                return $this->redirect($this->generateUrl('_user_my_page'));
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
   /**
     * Delete club information for current user
     * Current user must be club administrator
     * @Route("/club/del", name="_club_del")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function delClubAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getCurrentUser();
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage());
        } 

        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
            
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // Controller is called by user assigned to a club - switch to club edit view
            return $this->redirect($this->generateUrl('_club_add'));
        }

        $form = $this->makeClubForm($club, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($form->isValid()) {
            $em->remove($club);
            $user->setCid(0);
            $em->flush();
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
    private function makeClubForm($club, $action) {
        $countries = array();
        foreach (array_keys($this->get('util')->getCountries()) as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort($countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUB.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUB.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, $club, $action) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NONAME', array(), 'admin')));
                return false;
            }
            if ($club->getCountry() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NOCOUNTRY', array(), 'admin')));
                return false;
            }
            $em = $this->getDoctrine()->getManager();
            $clubs = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                        ->findBy(array('name' => $club->getName(), 'country' => $club->getCountry()));
            if ($clubs != null && count($clubs) > 0 && $clubs[0]->getId() != $club->getId()) {
                $transTxt = $action == 'add' ? 'FORM.CLUB.NAMEEXIST' : 'FORM.CLUB.CANTCHANGENAME';
                $form->addError(new FormError($this->get('translator')->trans($transTxt, array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}