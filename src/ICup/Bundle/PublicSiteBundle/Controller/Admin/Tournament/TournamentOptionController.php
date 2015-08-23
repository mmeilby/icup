<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * List the tournaments available
 */
class TournamentOptionController extends Controller
{
    /**
     * Change information of an existing tournament
     * @Route("/edit/tournament/options/{tournamentid}", name="_edit_tournament_options")
     * @Template("ICupPublicSiteBundle:Edit:edittournamentoptions.html.twig")
     */
    public function chgTournamentAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $options = $tournament->getOption();
        if (!$options) {
            $options = new TournamentOption();
        }
        $form = $this->makeTournamentForm($options);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $options)) {
            $em = $this->getDoctrine()->getManager();
            if (!$tournament->getOption()) {
                $em->persist($options);
                $tournament->setOption($options);
            }
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'tournament' => $tournament);
    }
    
    private function makeTournamentForm(TournamentOption $options) {
        $formDef = $this->createFormBuilder($options);
        $formDef->add('drr', 'checkbox', array( 'label' => 'FORM.TOURNAMENTOPTIONS.DRR.PROMPT',
                                                'help' => 'FORM.TOURNAMENTOPTIONS.DRR.HELP',
                                                'required' => false,
                                                'disabled' => false,
                                                'translation_domain' => 'admin'));
        $formDef->add('wpoints', 'integer', array('label' => 'FORM.TOURNAMENTOPTIONS.WP', 'required' => false, 'disabled' => false, 'translation_domain' => 'admin'));
        $formDef->add('tpoints', 'integer', array('label' => 'FORM.TOURNAMENTOPTIONS.TP', 'required' => false, 'disabled' => false, 'translation_domain' => 'admin'));
        $formDef->add('lpoints', 'integer', array('label' => 'FORM.TOURNAMENTOPTIONS.LP', 'required' => false, 'disabled' => false, 'translation_domain' => 'admin'));
        $formDef->add('dscore', 'integer', array('label' => 'FORM.TOURNAMENTOPTIONS.DS', 'required' => false, 'disabled' => false, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TOURNAMENTOPTIONS.CANCEL.CHG',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TOURNAMENTOPTIONS.SUBMIT.CHG',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, TournamentOption $options) {
        if ($form->isValid()) {
            if ($options->getWpoints() === null || trim($options->getWpoints()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOWP', array(), 'admin')));
            }
            if ($options->getTpoints() === null || trim($options->getTpoints()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOTP', array(), 'admin')));
            }
            if ($options->getLpoints() === null || trim($options->getLpoints()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOLP', array(), 'admin')));
            }
            if ($options->getDscore() === null || trim($options->getDscore()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NODS', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}
