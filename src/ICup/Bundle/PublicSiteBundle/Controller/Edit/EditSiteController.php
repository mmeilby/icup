<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the categories, groups and playgrounds available
 */
class EditSiteController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/site/list/{tournamentid}", name="_edit_site_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listsites.html.twig")
     */
    public function listAction($tournamentid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournamentid));
        
        $sites = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site')
                            ->findBy(array('pid' => $tournamentid));
        
        $qb = $em->createQuery("select p ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground p, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site s ".
                               "where s.pid=:tournament and p.pid=s.id ".
                               "order by p.no asc");
        $qb->setParameter('tournament', $tournamentid);
        $playgrounds = $qb->getResult();

        $siteList = array();
        foreach ($playgrounds as $playground) {
            $siteList[$playground->getPid()][] = $playground;
        }
        
        return array('tournament' => $tournament, 'playgrounds' => $siteList, 'sites' => $sites, 'categories' => $categories, 'error' => isset($error) ? $error : null);
    }

    /**
     * Add new site
     * @Route("/edit/site/add/{tournamentid}", name="_edit_site_add")
     * @Template("ICupPublicSiteBundle:Edit:editsite.html.twig")
     */
    public function addAction($tournamentid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }
        
        $site = new Site();
        $site->setPid($tournament != null ? $tournament->getId() : 0);
        $form = $this->makeSiteForm($site, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        if ($form->isValid()) {
            $em->persist($site);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'site' => $site, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Change information of an existing site
     * @Route("/edit/site/chg/{siteid}", name="_edit_site_chg")
     * @Template("ICupPublicSiteBundle:Edit:editsite.html.twig")
     */
    public function chgAction($siteid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $site = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site')->find($siteid);
        if ($site == null) {
            $error = "FORM.ERROR.BADSITE";
        }
     
        $form = $this->makeSiteForm($site, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($site);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'site' => $site, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Remove site from the register - including all related playgrounds and match results
     * @Route("/edit/site/del/{siteid}", name="_edit_site_del")
     * @Template("ICupPublicSiteBundle:Edit:editsite.html.twig")
     */
    public function delAction($siteid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $site = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site')->find($siteid);
        if ($site == null) {
            $error = "FORM.ERROR.BADSITE";
        }
                
        $form = $this->makeSiteForm($site, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->remove($site);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'site' => $site, 'error' => isset($error) ? $error : null);
    }
    
    private function makeSiteForm($site, $action) {
        $formDef = $this->createFormBuilder($site);
        $formDef->add('name', 'text', array('label' => 'FORM.SITE.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.SITE.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.SITE.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * Add new playground to a site
     * @Route("/edit/playground/add/{siteid}", name="_edit_playground_add")
     * @Template("ICupPublicSiteBundle:Edit:editplayground.html.twig")
     */
    public function addPlaygroundAction($siteid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $site = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site')->find($siteid);
        if ($site == null) {
            $error = "FORM.ERROR.BADSITE";
        }
                
        $playground = new Playground();
        $playground->setPid($site != null ? $site->getId() : 0);
        $form = $this->makePlaygroundForm($playground, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($playground);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'playground' => $playground, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Change information of an existing playground
     * @Route("/edit/playground/chg/{playgroundid}", name="_edit_playground_chg")
     * @Template("ICupPublicSiteBundle:Edit:editplayground.html.twig")
     */
    public function chgPlaygroundAction($playgroundid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $playground = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground')->find($playgroundid);
        if ($playground == null) {
            $error = "FORM.ERROR.BADPLAYGROUND";
        }
        else {
            $site = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site')->find($playground->getPid());
            if ($site == null) {
                $error = "FORM.ERROR.BADSITE";
            }
        }

        $form = $this->makePlaygroundForm($playground, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($playground);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'playground' => $playground, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Remove playground from the register - all match results and playground information is lost
     * @Route("/edit/playground/del/{playgroundid}", name="_edit_playground_del")
     * @Template("ICupPublicSiteBundle:Edit:editplayground.html.twig")
     */
    public function delPlaygroundAction($playgroundid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $playground = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground')->find($playgroundid);
        if ($playground == null) {
            $error = "FORM.ERROR.BADPLAYGROUND";
        }
        else {
            $site = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site')->find($playground->getPid());
            if ($site == null) {
                $error = "FORM.ERROR.BADSITE";
            }
        }
        
        $form = $this->makePlaygroundForm($playground, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->remove($playground);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_site_list', array('tournamentid' => $site->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'playground' => $playground, 'error' => isset($error) ? $error : null);
    }
    
    private function makePlaygroundForm($playground, $action) {
        $formDef = $this->createFormBuilder($playground);
        $formDef->add('name', 'text', array('label' => 'FORM.PLAYGROUND.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('no', 'text', array('label' => 'FORM.PLAYGROUND.NO', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.PLAYGROUND.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.PLAYGROUND.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
}
