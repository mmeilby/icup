<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

class EditClubController extends Controller
{
    /**
     * List the clubs available
     * @Route("/edit/club/list", name="_edit_club_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listclubs.html.twig")
     */
    public function listClubsAction()
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $clubs = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                        ->findBy(array(), array('country' => 'asc', 'name' => 'asc'));

        $teamList = array();
        foreach ($clubs as $club) {
            $country = $club->getCountry();
            $teamList[$country][$club->getId()] = $club;
        }

        $teamcount = count($teamList, COUNT_RECURSIVE)/2;
        $teamColumns = array();
        $ccount = 0;
        $column = 0;
        foreach ($teamList as $country => $clubs) {
            $teamColumns[$column][] = array($country => $clubs);
            $ccount += count($clubs) + 1;
            if ($ccount > $teamcount && $column < 1) {
                $column++;
                $ccount = 0;
            }
        }
        return array('teams' => $teamColumns);
    }
    
    /**
     * Add new club
     * @Route("/edit/club/add", name="_edit_club_add")
     * @Template("ICupPublicSiteBundle:Edit:editclub.html.twig")
     */
    public function addAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $country = $this->getRequest()->get('country');
        if ($country == null) {
            $map = array('en'=>'GBR', 'da'=>'DNK', 'it'=>'ITA', 'fr'=>'FRA', 'de'=>'DEU', 'es'=>'ESP', 'po'=>'POL');
            $country = $map[$this->getRequest()->getLocale()];
        }

        $club = new Club();
        // If country is a part of the request parameters - use it
        $club->setCountry($country);
        $form = $this->makeClubForm($club, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if ($this->checkForm($form, $club, 'add')) {
            $em->persist($club);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
    }
    
   /**
     * Change club information
     * @Route("/edit/club/chg/{clubid}", name="_edit_club_chg")
     * @Template("ICupPublicSiteBundle:Edit:editclub.html.twig")
     */
    public function chgAction($clubid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
             return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }

        $form = $this->makeClubForm($club, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if ($this->checkForm($form, $club, 'chg')) {
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
   /**
     * Delete club information
     * @Route("/edit/club/del/{clubid}", name="_edit_club_del")
     * @Template("ICupPublicSiteBundle:Edit:editclub.html.twig")
     */
    public function delAction($clubid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
             return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }

        $form = $this->makeClubForm($club, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if ($form->isValid()) {
            $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                    ->findBy(array('cid' => $clubid));
            foreach ($users as $usr) {
                $usr->setCid(0);
            }
            $em->remove($club);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'club' => $club);
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
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
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
                if ($action == 'add') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NAMEEXIST', array(), 'admin')));
                }
                else {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.CANTCHANGENAME', array(), 'admin')));
                }
                return false;
            }
            return true;
        }
        return false;
    }
}
