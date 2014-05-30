<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;

/**
 * List the matches scheduled for a group
 */
class ListMatchController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/group/list/matches/{groupid}", name="_edit_group_list_matches")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listmatches.html.twig")
     */
    public function listAction($groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $host = $this->get('entity')->getHostById($tournament->getPid());
        $mmatches = $this->get('match')->listMatchesByGroup($groupid);
        $umatches = $this->get('match')->listMatchesUnfinished($groupid);
        $sortedMatches = array_merge($umatches, $mmatches);
        usort($sortedMatches, array("ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport", "reorderMatch"));
        $matches = array();
        foreach ($sortedMatches as $match) {
            $matches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        return array('host' => $host,
                     'tournament' => $tournament,
                     'category' => $category,
                     'group' => $group,
                     'matchlist' => $matches
                );
    }
    
    /**
     * List the matches available for a playground on a given day
     * @Route("/host/list/matches", name="_edit_list_matches")
     * @Method("GET")
     */
    public function listMatchAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $utilService->getTournament();
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $date = $this->getSelectedDate($tournament->getId());
        $playgroundid = $this->getSelectedPlayground($tournament->getId());

        return $this->redirect($this->generateUrl('_edit_match_score',
                array('playgroundid' => $playgroundid, 'date' => date_format($date, "d-m-Y"))));
    }
    
    private function getSelectedDate($tournamentid) {
        /* @var $request Request */
        $request = $this->getRequest();
        $session = $request->getSession();
        $date = $session->get('icup.matchedit.date');
        if ($date == null) {
            $dates = $this->get('match')->listMatchCalendar($tournamentid);
            if ($dates != null && count($dates) > 0) {
                $date = $dates[0];
            }
            else {
                throw new ValidationException("NOTOURNAMENTDATA", "Match date missing: tournamentid=".$tournamentid);
            }
            $session->set('icup.matchedit.date', $date);
        }
        return $date;
    }
    
    private function getSelectedPlayground($tournamentid) {
        /* @var $request Request */
        $request = $this->getRequest();
        $session = $request->getSession();
        $playgroundid = $session->get('icup.matchedit.playground');
        if ($playgroundid == null) {
            $playgrounds = $this->get('logic')->listPlaygroundsByTournament($tournamentid);
            if (count($playgrounds) > 0) {
                $playgroundid = $playgrounds[0]->getId();
            }
            else {
                throw new ValidationException("NOTOURNAMENTDATA", "Match playground missing: tournamentid=".$tournamentid);
            }
            $session->set('icup.matchedit.playground', $playgroundid);
        }
        return $playgroundid;
    }
}
