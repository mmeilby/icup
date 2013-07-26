<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StatisticsController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/stt", name="_tournament_statistics")
     * @Template("ICupPublicSiteBundle:Tournament:statistics.html.twig")
     */
    public function listAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $groupList = array();

        $qb = $em->createQuery("select c ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c ".
                               "where c.pid=:tournament ".
                               "order by c.gender asc, c.classification asc");
        $qb->setParameter('tournament', $tournament->getId());
        $categories = $qb->getResult();
        
        foreach ($categories as $category) {
            $qb = $em->createQuery("select t.id,t.name,t.division,c.country,g.id as grp ".
                                   "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                                   "where g.pid=:category and ".
                                         "o.pid=g.id and ".
                                         "o.cid=t.id and ".
                                         "t.pid=c.id ".
                                   "order by g.id asc, o.id asc");
            $qb->setParameter('category', $category->getId());
            $teams = $qb->getResult();
            
            $qbr = $em->createQuery("select r ".
                                    "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                         "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                         "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ".
                                    "where r.pid=m.id and m.pid=g.id and g.pid=:category ".
                                    "order by r.pid");
            $qbr->setParameter('category', $category->getId());
            $teamResults = $qbr->getResult();
            
            $teamsList = $this->get('orderTeams')->generateStat($teams, $teamResults);
            $teamsList = $this->get('orderTeams')->sortTeamsByMostGoals($teamsList);
            $groupList[$category->getName()] = array('category' => $category, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'grouplist' => $groupList);
    }
}
