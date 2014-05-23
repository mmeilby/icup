<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TeamController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/tm/{teamid}/{groupid}", name="_showteam")
     * @Template("ICupPublicSiteBundle:Tournament:team.html.twig")
     */
    public function listAction($tournament, $teamid, $groupid)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $team = $this->get('entity')->getTeamById($teamid);
        $name = $this->get('logic')->getTeamName($team->getName(), $team->getDivision());
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $matchList = $this->get('match')->listMatchesByGroupTeam($groupid, $teamid);
        
        return array('tournament' => $tournament,
                     'category' => $category,
                     'group' => $group,
                     'team' => $team,
                     'teamname' => $name,
                     'matchlist' => $matchList);
    }
}
