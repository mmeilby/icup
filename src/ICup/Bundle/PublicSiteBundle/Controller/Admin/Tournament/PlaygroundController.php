<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\FormError;

/**
 * Maintain playgrounds for a tournament
 */
class PlaygroundController extends Controller
{
    /**
     * Add new playground to a site
     * @Route("/edit/playground/add/{siteid}", name="_edit_playground_add")
     * @Template("ICupPublicSiteBundle:Edit:editplayground.html.twig")
     */
    public function addPlaygroundAction($siteid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $site = $this->get('entity')->getSiteById($siteid);
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $playground = new Playground();
        $playground->setPid($site->getId());
        $form = $this->makePlaygroundForm($playground, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $playground)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($playground);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'add', 'playground' => $playground, 'error' => null);
    }
    
    /**
     * Change information of an existing playground
     * @Route("/edit/playground/chg/{playgroundid}", name="_edit_playground_chg")
     * @Template("ICupPublicSiteBundle:Edit:editplayground.html.twig")
     */
    public function chgPlaygroundAction($playgroundid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makePlaygroundForm($playground, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $playground)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($playground);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'playground' => $playground, 'error' => null);
    }
    
    /**
     * Remove playground from the register - all match results and playground information is lost
     * @Route("/edit/playground/del/{playgroundid}", name="_edit_playground_del")
     * @Template("ICupPublicSiteBundle:Edit:editplayground.html.twig")
     */
    public function delPlaygroundAction($playgroundid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makePlaygroundForm($playground, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if (count($this->get('match')->listMatchesByPlayground($playground->getId())) > 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.MATCHESEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($playground);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'del', 'playground' => $playground, 'error' => null);
    }
    
    private function makePlaygroundForm($playground, $action) {
        $formDef = $this->createFormBuilder($playground);
        $formDef->add('name', 'text', array('label' => 'FORM.PLAYGROUND.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('no', 'text', array('label' => 'FORM.PLAYGROUND.NO', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.PLAYGROUND.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.PLAYGROUND.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, Playground $playground) {
        if ($form->isValid()) {
            if ($playground->getName() == null || trim($playground->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.NONAME', array(), 'admin')));
                return false;
            }
            if ($playground->getNo() == null || trim($playground->getNo()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.NONO', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}
