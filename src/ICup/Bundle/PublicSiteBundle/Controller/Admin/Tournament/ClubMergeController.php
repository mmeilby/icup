<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

class ClubMergeController extends Controller
{
    /**
     * Merge clubs
     * @Route("/admin/club/merge/{clubid}/{sourceclubid}", name="_edit_club_merge", options={"expose"=true}))
     * @Template("ICupPublicSiteBundle:Edit:mergeclub.html.twig")
     */
    public function mergeAction($clubid, $sourceclubid) {
        $returnUrl = $this->get('util')->getReferer();
        
        $target_club = $this->get('entity')->getClubById($clubid);
        $source_club = $this->get('entity')->getClubById($sourceclubid);
        
        $form = $this->makeClubForm();
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'source_club' => $source_club, 'target_club' => $target_club);
    }
    
    private function makeClubForm() {
        $formDef = $this->createFormBuilder();
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUBMERGE.CANCEL', 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUBMERGE.SUBMIT', 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
}
