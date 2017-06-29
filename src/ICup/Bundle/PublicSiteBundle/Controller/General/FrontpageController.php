<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
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
        $domain = $request->getHost();
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
        $newsscroll = array();
        foreach ($tournaments as $tournament) {
            /* @var $tournament Tournament */
            if (strcmp($tournament->getHost()->getDomain(), $domain) == 0 ||
                strcmp("www.".$tournament->getHost()->getDomain(), $domain) == 0) {
                // only select tournaments for the host that matches current url
                $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
                if ($stat != TournamentSupport::$TMNT_HIDE) {
                    $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
                    $statusList[$keyList[$stat]][] = $tournament;

                    $newsStream = $this->get('tmnt')->listNewsByTournament($tournament->getId());
                    foreach ($newsStream as $news) {
                        if ($news['newstype'] == News::$TYPE_FRONTPAGE_PERMANENT || $news['newstype'] == News::$TYPE_FRONTPAGE_TIMELIMITED) {
                            $news['newsdate'] = Date::getDateTime($news['date']);
                            if ($news['id'] > 0) {
                                $news['flag'] = $this->get('util')->getFlag($news['country']);
                                $newsscroll[$news['newsno']][$news['language']] = $news;
                            }
                            else if ($news['mid'] > 0) {
                                $newsscroll[$news['newsno']][$news['language']] = $news;
                            }
                            else {
                                /* @var $diff \DateInterval */
                                $diff = $today->diff($news['newsdate']);
                                if ($news['newstype'] == News::$TYPE_FRONTPAGE_PERMANENT || $diff->days < 2) {
                                    $newsscroll[$news['newsno']][$news['language']] = $news;
                                }
                            }
                        }
                    }
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
                    if (count($club_list) > 0) {
                        array_unshift($teaserList,
                            array(
                                'titletext' => 'FORM.TEASER.TOURNAMENT.MYMATCHES.TITLE',
                                'text' => 'FORM.TEASER.TOURNAMENT.MYMATCHES.DESC',
                                'path' => $this->generateUrl('_show_matches', array('tournament' => $tournament->getKey()))
                            )
                        );
                    }
                }
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

        return array(
            'form' => $form->createView(),
            'tournaments' => $tournamentList,
            'statuslist' => $statusList,
            'matchlist' => $shortMatches,
            'teaserlist' => $teaserList,
            'club_list' => $club_list,
            'newsscroll' => $this->getNews($newsscroll, $request)
        );
    }
    
    private function makeContactForm(Contact $contact) {
        $formDef = $this->createFormBuilder($contact);
        $formDef->add('name', 'text', array('label' => 'FORM.FRONTPAGE.NAME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'frontpage'));
        $formDef->add('club', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'frontpage'));
        $formDef->add('phone', 'text', array('label' => 'FORM.FRONTPAGE.PHONE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'frontpage'));
        $formDef->add('email', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'frontpage'));
        $formDef->add('msg', 'textarea', array('label' => 'FORM.FRONTPAGE.MSG', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'frontpage'));
        $formDef->add('send', 'submit', array('label' => 'FORM.FRONTPAGE.SUBMIT',
                                                'translation_domain' => 'frontpage',
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

    private function getNews($newsList, Request $request) {
        $locale = $request->getLocale();
        $defaultLocale = $request->getDefaultLocale();
        $newsForLocale = array();
        foreach ($newsList as $news) {
            if (array_key_exists($locale, $news)) {
                $newsForLocale[] = $news[$locale];
            }
            elseif (array_key_exists($defaultLocale, $news)) {
                $newsForLocale[] = $news[$defaultLocale];
            }
        }
        return $newsForLocale;
    }
}
