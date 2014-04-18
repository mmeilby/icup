<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

/**
 * List the tournaments available
 */
class HostController extends Controller
{
    /**
     * Add new host
     * - This is an ADMIN ONLY function -
     * @Route("/admin/host/add", name="_edit_host_add")
     * @Template("ICupPublicSiteBundle:Edit:edithost.html.twig")
     */
    public function addAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        if (!$utilService->isAdminUser($user)) {
            // Controller is called by a non admin user - fail...
            throw new ValidationException("mustbeadmin.html.twig");
        }

        $host = new Host();
        $form = $this->makeHostForm($host, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->getReturnPath($user));
        }
        if ($form->isValid()) {
            $em->persist($host);
            $em->flush();
            return $this->redirect($this->getReturnPath($user));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'host' => $host, 'error' => null);
    }
    
    /**
     * Change information of an existing host
     * @Route("/edit/host/chg/{hostid}", name="_edit_host_chg")
     * @Template("ICupPublicSiteBundle:Edit:edithost.html.twig")
     */
    public function chgAction($hostid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $host = $this->get('entity')->getHostById($hostid);
        $utilService->validateEditorAdminUser($user, $hostid);

        $form = $this->makeHostForm($host, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->getReturnPath($user));
        }
        if ($form->isValid()) {
            $em->persist($host);
            $em->flush();
            return $this->redirect($this->getReturnPath($user));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'host' => $host, 'error' => null);
    }
    
    /**
     * Remove host from the register - including all related tournaments and match results
     * - This is an ADMIN ONLY function -
     * @Route("/admin/host/del/{hostid}", name="_edit_host_del")
     * @Template("ICupPublicSiteBundle:Edit:edithost.html.twig")
     */
    public function delAction($hostid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $host = $this->get('entity')->getHostById($hostid);
        $utilService->validateEditorAdminUser($user, $hostid);

        $form = $this->makeHostForm($host, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->getReturnPath($user));
        }
        if ($form->isValid()) {
            $em->remove($host);
            $em->flush();
            return $this->redirect($this->getReturnPath($user));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'host' => $host, 'error' => null);
    }
    
    private function makeHostForm($host, $action) {
        $formDef = $this->createFormBuilder($host);
        $formDef->add('name', 'text', array('label' => 'FORM.HOST.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.HOST.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.HOST.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }

    private function getReturnPath(User $user) {
        if ($this->get('util')->isAdminUser($user)) {
            return $this->generateUrl('_edit_host_list');
        }
        else {
            return $this->generateUrl('_host_list_tournaments');
        }
    }
}
