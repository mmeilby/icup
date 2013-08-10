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
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_icup'));
        }

        $qb = $em->createQuery("select count(distinct t.id) as teams, ".
                                      "count(distinct c.id) as clubs, ".
                                      "count(distinct c.country) as countries, ".
                                      "count(distinct cat.id) as categories, ".
                                      "count(distinct g.id) as groups ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where cat.pid=:tournament and ".
                                     "g.pid=cat.id and ".
                                     "g.classification=0 and ".
                                     "o.pid=g.id and ".
                                     "o.cid=t.id and ".
                                     "t.pid=c.id");
        $qb->setParameter('tournament', $tournament->getId());
        $counts = $qb->getResult();

        $qbp = $em->createQuery("select count(distinct s.id) as sites, ".
                                       "count(distinct p.id) as playgrounds ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site s, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground p ".
                                "where s.pid=:tournament and ".
                                      "p.pid=s.id");
        $qbp->setParameter('tournament', $tournamentId);
        $playgroundCount = $qbp->getResult();

        $qbt = $em->createQuery("select count(distinct t.id) as femaleteams ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                                "where cat.pid=:tournament and ".
                                      "cat.gender='F' and ".
                                      "g.pid=cat.id and ".
                                      "o.pid=g.id and ".
                                      "o.cid=t.id");
        $qbt->setParameter('tournament', $tournament->getId());
        $teamCounts = $qbt->getResult();

        $qbc = $em->createQuery("select count(distinct t.id) as childteams ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                                "where cat.pid=:tournament and ".
                                      "cat.classification<'U19' and ".
                                      "g.pid=cat.id and ".
                                      "o.pid=g.id and ".
                                      "o.cid=t.id");
        $qbc->setParameter('tournament', $tournament->getId());
        $teamCounts2 = $qbc->getResult();

        $qbr = $em->createQuery("select count(distinct m.id) as matches, ".
                                       "sum(r.score) as goals, ".
                                       "max(r.score) as mostgoals, ".
                                       "count(distinct m.date) as days ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ".
                                "where cat.pid=:tournament and r.pid=m.id and m.pid=g.id and g.pid=cat.id");
        $qbr->setParameter('tournament', $tournament->getId());
        $matchCounts = $qbr->getResult();
        $statmap = array_merge($counts[0], $playgroundCount[0], $teamCounts[0], $teamCounts2[0], $matchCounts[0]);

        $statmap['adultteams'] = $statmap['teams'] - $statmap['childteams'];
        $statmap['maleteams'] = $statmap['teams'] - $statmap['femaleteams'];

        $qbw = $em->createQuery("select t.id,t.name,t.division,c.country ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                                "where cat.pid=:tournament and ".
                                      "g.pid=cat.id and ".
                                      "g.classification>8 and ".
                                      "o.pid=g.id and ".
                                      "o.cid=t.id and ".
                                      "t.pid=c.id ".
                                "order by o.id");
        $qbw->setParameter('tournament', $tournament->getId());
        $teams = $qbw->getResult();

        $qbm = $em->createQuery("select r ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ".
                                "where cat.pid=:tournament and r.pid=m.id and m.pid=g.id and g.pid=cat.id and g.classification>8 ".
                                "order by r.pid");
        $qbm->setParameter('tournament', $tournament->getId());
        $teamResults = $qbm->getResult();
        $teamsList = $this->get('orderTeams')->generateStat($teams, $teamResults);

        $maxTrophy = null;
        $countries = array();
        foreach ($teamsList as $teamStat) {
            if (key_exists($teamStat->country, $countries)) {
                $countries[$teamStat->country]++;
            }
            else {
                $countries[$teamStat->country] = 1;
            }
            if ($maxTrophy == null) {
                $maxTrophy = $teamStat->country;
            }
            else {
                if ($countries[$maxTrophy] < $countries[$teamStat->country]) {
                    $maxTrophy = $teamStat->country;
                }
            }
        }

        $statmap['mosttrophys'] = $countries[$maxTrophy];
        
        return array(
            'tournament' => $tournament,
            'statistics' => $statmap,
            'order' => array(
                'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                'tournament' => array('categories','groups','sites','playgrounds','matches','days'),
                'top' => array('goals','mostgoals','mosttrophys')));
    }
}
