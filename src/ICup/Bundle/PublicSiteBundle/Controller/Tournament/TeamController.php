<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TeamController extends Controller
{
    /**
     * @Route("/tmnt/tm/{teamid}/{groupid}", name="_showteam")
     * @Template("ICupPublicSiteBundle:Tournament:team.html.twig")
     */
    public function listAction($teamid, $groupid)
    {
        $team = $this->get('entity')->getTeamById($teamid);
        $name = $this->get('logic')->getTeamName($team->getName(), $team->getDivision());
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $matchList = $this->get('match')->listMatchesByGroupTeam($groupid, $teamid);

        $matches = array();
        foreach ($matchList as $match) {
            $matches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        
        return array('tournament' => $tournament,
                     'category' => $category,
                     'group' => $group,
                     'team' => $team,
                     'teamname' => $name,
                     'matchlist' => $matches);
    }
}
