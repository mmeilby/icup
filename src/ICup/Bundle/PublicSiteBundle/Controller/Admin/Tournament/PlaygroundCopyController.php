<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PlaygroundCopyController extends Controller
{
    /**
     * Copy playground
     * @Route("/edit/playground/copy/{playgroundid}/{sourceplaygroundid}", name="_edit_playground_copy", options={"expose"=true}))
     * @Template("ICupPublicSiteBundle:Edit:copyplayground.html.twig")
     */
    public function copyAction($playgroundid, $sourceplaygroundid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $target_playground Playground */
        $target_playground = $this->get('entity')->getPlaygroundById($playgroundid);
        /* @var $source_playground Playground */
        $source_playground = $this->get('entity')->getPlaygroundById($sourceplaygroundid);

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $source_site = $source_playground->getSite();
        $target_site = $target_playground->getSite();
        if ($source_site->getTournament()->getId() != $target_site->getTournament()->getId()) {
            
        }
        /* @var $tournament Tournament */
        $tournament = $source_site->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        
        $form = $this->makeCopyForm();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->copyPlayground($source_playground, $target_playground);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'source_playground' => $source_playground, 'target_playground' => $target_playground);
    }
    
    private function makeCopyForm() {
        $formDef = $this->createFormBuilder();
        $formDef->add('cancel', 'submit', array('label' => 'FORM.COPYPLAYGROUND.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.COPYPLAYGROUND.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function copyPlayground(Playground $source_playground, Playground $target_playground) {
        $em = $this->getDoctrine()->getManager();
        $attributes = $source_playground->getPlaygroundAttributes();
        foreach ($attributes as $attr) {
            if ($this->get('logic')->getPlaygroundAttribute($target_playground->getId(), $attr->getDate(), $attr->getStart()) == null) {
                $target_attr = new PlaygroundAttribute();
                $target_attr->setPlayground($target_playground);
                $target_attr->setTimeslot($attr->getTimeslot());
                $target_attr->setDate($attr->getDate());
                $target_attr->setStart($attr->getStart());
                $target_attr->setEnd($attr->getEnd());
                $em->persist($target_attr);
            }
        }
        $em->flush();
    }
}
