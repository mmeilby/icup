<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Core;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

class ClubController extends Controller
{
    /**
     * Add new club
     * @Route("/admin/club/add", name="_edit_club_add")
     * @Template("ICupPublicSiteBundle:Edit:editclub.html.twig")
     */
    public function addAction() {
        $returnUrl = $this->get('util')->getReferer();
        
        $country = $this->getRequest()->get('country');
        if ($country == null) {
            $globals = $this->get('twig')->getGlobals();
            $country = $globals['countries'][$this->getRequest()->getLocale()];
        }

        $club = new Club();
        // If country is a part of the request parameters - use it
        $club->setCountry($country);
        $form = $this->makeClubForm($club, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $club)) {
            $otherclub = $this->get('logic')->getClubByName($club->getName(), $club->getCountry());
            if ($otherclub != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NAMEEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->persist($club);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
    }
    
   /**
     * Change club information
     * @Route("/admin/club/chg/{clubid}", name="_edit_club_chg")
     * @Template("ICupPublicSiteBundle:Edit:editclub.html.twig")
     */
    public function chgAction($clubid) {
        $returnUrl = $this->get('util')->getReferer();

        /* @var $club Club */
        $club = $this->get('entity')->getClubById($clubid);

        $form = $this->makeClubForm($club, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $club)) {
            $otherclub = $this->get('logic')->getClubByName($club->getName(), $club->getCountry());
            if ($otherclub != null && $otherclub->getId() != $club->getId()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.CANTCHANGENAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
   /**
     * Delete club information
     * @Route("/admin/club/del/{clubid}", name="_edit_club_del")
     * @Template("ICupPublicSiteBundle:Edit:editclub.html.twig")
     */
    public function delAction($clubid) {
        $returnUrl = $this->get('util')->getReferer();

        /* @var $club Club */
        $club = $this->get('entity')->getClubById($clubid);

        $form = $this->makeClubForm($club, 'del');
        $teams = $this->get('logic')->listTeamsByClub($clubid);
        if ($teams != null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.TEAMSEXIST', array(), 'admin')));
        }
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid() && $teams == null) {
            $users = $this->get('logic')->listUsersByClub($clubid);
            foreach ($users as $usr) {
                if ($usr->isClub() && $usr->isRelated()) {
                    $usr->setRole(User::$CLUB);
                    $usr->setStatus(User::$VER);
                }
                $usr->setCid(0);
            }
            $em = $this->getDoctrine()->getManager();
            $em->remove($club);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'del', 'club' => $club);
    }
    
    private function makeClubForm($club, $action) {
        $countries = array();
        foreach ($this->get('util')->getCountries() as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort($countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUB.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUB.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, $club) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NONAME', array(), 'admin')));
                return false;
            }
            if ($club->getCountry() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NOCOUNTRY', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}
