<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
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
        /* @var $category Category */
        $category = $group->getCategory();
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $matchList = $this->get('match')->listMatchesByGroupPlayground($groupid, $playgroundid);

        $matches = array();
        foreach ($matchList as $match) {
            $matches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        
        return array('tournament' => $tournament,
                     'category' => $category,
                     'group' => $group,
                     'playground' => $playground,
                     'matchlist' => $matches);
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

        $matches = array();
        foreach ($matchList as $match) {
            $matches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        
        return array('tournament' => $tournament,
                     'playground' => $playground,
                     'matchlist' => $matches);
    }
}
