<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use ICup\Bundle\PublicSiteBundle\Entity\Contact;
use ICup\Bundle\PublicSiteBundle\Controller\User\SelectClubController;
use Symfony\Component\HttpFoundation\Request;
use DateTime;
use Swift_Message;

class FrontpageController extends Controller
{
    /**
     * @Route("/", name="_icup")
     * @Template("ICupPublicSiteBundle:General:frontpage.html.twig")
     */
    public function rootAction(Request $request)
    {
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $keyList = array(
            TournamentSupport::$TMNT_ENROLL => 'enroll',
            TournamentSupport::$TMNT_GOING => 'active',
            TournamentSupport::$TMNT_DONE => 'done',
            TournamentSupport::$TMNT_ANNOUNCE => 'announce'
        );
        $statusList = array(
            'enroll' => array(),
            'active' => array(),
            'done' => array(),
            'announce' => array()
        );
        $club_list = $this->get('util')->getClubList();
        $today = new DateTime();
        $shortMatches = array();
        $teaserList = array();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
                $statusList[$keyList[$stat]][] = $tournament;
            }
            if ($stat == TournamentSupport::$TMNT_GOING) {
                $shortMatchList = $this->get('match')->listMatchesLimitedWithTournament($tournament->getId(), $today, 10, 3, array_keys($club_list));
                $shortMatches = array();
                foreach ($shortMatchList as $match) {
                    $shortMatches[date_format($match['schedule'], "Y/m/d")][] = $match;
                }
                $teaserList = array(
                    array(
                        'titletext' => 'FORM.TEASER.TOURNAMENT.GROUPS.TITLE',
                        'text' => 'FORM.TEASER.TOURNAMENT.GROUPS.DESC',
                        'path' => $this->generateUrl('_tournament_categories', array('tournament' => $tournament->getKey()))
                    ),
                    array(
                        'titletext' => 'FORM.TEASER.TOURNAMENT.PLAYGROUNDS.TITLE',
                        'text' => 'FORM.TEASER.TOURNAMENT.PLAYGROUNDS.DESC',
                        'path' => $this->generateUrl('_tournament_playgrounds', array('tournament' => $tournament->getKey()))
                    ),
                    array(
                        'titletext' => 'FORM.TEASER.TOURNAMENT.TEAMS.TITLE',
                        'text' => 'FORM.TEASER.TOURNAMENT.TEAMS.DESC',
                        'path' => $this->generateUrl('_tournament_clubs', array('tournament' => $tournament->getKey()))
                    ),
        /*
                    array(
                        'titletext' => 'FORM.TEASER.TOURNAMENT.WINNERS.TITLE',
                        'text' => 'FORM.TEASER.TOURNAMENT.WINNERS.DESC',
                        'path' => $this->generateUrl('_tournament_winners', array('tournament' => $tournament->getKey()))
                    ),
                    array(
                        'titletext' => 'FORM.TEASER.TOURNAMENT.STATISTICS.TITLE',
                        'text' => 'FORM.TEASER.TOURNAMENT.STATISTICS.DESC',
                        'path' => $this->generateUrl('_tournament_statistics', array('tournament' => $tournament->getKey()))
                    )
         */
                );
            }
        }

        $form = $this->makeContactForm(new Contact());
        $form->handleRequest($request);
        if ($form->isValid()) {
            if (stripos($form->getData()->getMsg(), "http:") === FALSE) {
                $this->sendMail($form->getData());
                $request->getSession()->getFlashBag()->add(
                    'msgsent',
                    'FORM.FRONTPAGE.MSGSENT'
                );
                // clear form
                $form = $this->makeContactForm(new Contact());
            }
            else {
                $request->getSession()->getFlashBag()->add(
                    'msgnotsent',
                    'FORM.FRONTPAGE.MSGINVALID'
                );
            }
        }

        $dm = $this->get('doctrine_phpcr')->getManager('default');
        $image = $dm->find(null, '/cms/media/images/Ter-amo8.png');
        
        return array(
            'form' => $form->createView(),
            'tournaments' => $tournamentList,
            'statuslist' => $statusList,
            'matchlist' => $shortMatches,
            'teaserlist' => $teaserList,
            'image' => $image,
            'club_list' => $club_list
        );
    }
    
    private function makeContactForm(Contact $contact) {
        $formDef = $this->createFormBuilder($contact);
        $formDef->add('name', 'text', array('label' => 'FORM.FRONTPAGE.NAME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('club', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('phone', 'text', array('label' => 'FORM.FRONTPAGE.PHONE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('email', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('msg', 'textarea', array('label' => 'FORM.FRONTPAGE.MSG', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.FRONTPAGE.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-envelope'));
        return $formDef->getForm();
    }
    
    private function sendMail(Contact $contact) {
        $from = array($this->container->getParameter('mailer_user') => "icup.dk support");
        $admins = $this->get('logic')->listAdminUsers();
        if (count($admins) < 1) {
            $recv = $from;
        }
        else {
            $recv = array();
            foreach ($admins as $admin) {
                if ($admin->getEmail() != '' && $admin->getName() != '') {
                    $recv[$admin->getEmail()] = $admin->getName();
                }
            }
        }
        $mailbody = $this->renderView('ICupPublicSiteBundle:Email:infomail.html.twig', $contact->getArray());
        $message = Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('FORM.INFOEMAIL.TITLE', array(), 'admin'))
            ->setFrom($from)
            ->setTo($recv)
            ->setBody($mailbody, 'text/html');
        $this->get('mailer')->send($message);
    }
}
