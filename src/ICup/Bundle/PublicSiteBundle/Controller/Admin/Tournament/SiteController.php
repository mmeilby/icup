<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * List the categories, groups and playgrounds available
 */
class SiteController extends Controller
{
    /**
     * Add new site
     * @Route("/edit/site/add/{tournamentid}", name="_edit_site_add")
     * @Template("ICupPublicSiteBundle:Edit:editsite.html.twig")
     */
    public function addAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $site = new Site();
        $site->setTournament($tournament);
        $form = $this->makeSiteForm($site, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $site)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($site);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'add', 'site' => $site, 'error' => null);
    }
    
    /**
     * Change information of an existing site
     * @Route("/edit/site/chg/{siteid}", name="_edit_site_chg")
     * @Template("ICupPublicSiteBundle:Edit:editsite.html.twig")
     */
    public function chgAction($siteid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $site = $this->get('entity')->getSiteById($siteid);
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $form = $this->makeSiteForm($site, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $site)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($site);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'site' => $site, 'error' => null);
    }
    
    /**
     * Remove site from the register - including all related playgrounds and match results
     * @Route("/edit/site/del/{siteid}", name="_edit_site_del")
     * @Template("ICupPublicSiteBundle:Edit:editsite.html.twig")
     */
    public function delAction($siteid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $site = $this->get('entity')->getSiteById($siteid);
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $form = $this->makeSiteForm($site, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->listPlaygrounds($site->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.SITE.PLAYGROUNDSEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($site);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'del', 'site' => $site, 'error' => null);
    }
    
    private function makeSiteForm($site, $action) {
        $formDef = $this->createFormBuilder($site);
        $formDef->add('name', 'text', array('label' => 'FORM.SITE.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.SITE.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.SITE.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }

    private function checkForm($form, Site $site) {
        if ($form->isValid()) {
            if ($site->getName() == null || trim($site->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.SITE.NONAME', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}
