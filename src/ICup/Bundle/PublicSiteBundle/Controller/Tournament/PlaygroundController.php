<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlaygroundController extends Controller
{
    /**
     * @Route("/tmnt/pgrnd/{playgroundid}/{groupid}", name="_showplayground")
     * @Template("ICupPublicSiteBundle:Tournament:playground.html.twig")
     */
    public function listAction($playgroundid, $groupid)
    {
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        
        $matchList = $this->get('match')->listMatchesByGroupPlayground($groupid, $playgroundid);
        return array('tournament' => $tournament,
                     'category' => $category,
                     'group' => $group,
                     'playground' => $playground,
                     'matchlist' => $matchList);
    }
    
    /**
     * @Route("/tmnt/pgrnd/{playgroundid}", name="_showplayground_full")
     * @Template("ICupPublicSiteBundle:Tournament:playground.full.html.twig")
     */
    public function listAllAction($playgroundid)
    {
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());

        $matchList = $this->get('match')->listMatchesByPlayground($playgroundid);
        return array('tournament' => $tournament,
                     'playground' => $playground,
                     'matchlist' => $matchList);
    }
}
