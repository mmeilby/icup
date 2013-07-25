<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StatisticsController extends Controller
{
    private function isScoreValid($relA, $relB) {
        return $relA->getScorevalid() && $relB->getScorevalid();
    }
    
    private function reorder($team1, $team2) {
        $p = $team1['points'] - $team2['points'];
        $d = $team1['diff'] - $team2['diff'];
        $s = $team1['score'] - $team2['score'];
        return $p < 0 || ($p==0 && $d < 0) || ($p==0 && $d==0 && $s < 0);
    }

    /**
     * @Route("/tmnt/{tournament}/wn", name="_tournament_statistics")
     * @Template("ICupPublicSiteBundle:Tournament:winners.html.twig")
     */
    public function listAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $championList = array();

        $qb = $em->createQuery("select c.id as catid,c.name as category,c.gender,c.classification as class,g.id,g.classification ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where c.pid=:tournament and g.pid=c.id and g.classification >= :finals ".
                               "order by c.gender asc, c.classification asc, g.classification desc");
        $qb->setParameter('tournament', $tournament->getId());
        $qb->setParameter('finals', 9);
        $groups = $qb->getResult();
        
        foreach ($groups as $group) {
            $qb = $em->createQuery("select t.id,t.name,t.division,c.country ".
                                   "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                                   "where o.pid=:group and ".
                                         "o.cid=t.id and ".
                                         "t.pid=c.id ".
                                   "order by o.id");
            $qb->setParameter('group', $group['id']);
            $teams = $qb->getResult();
            
            $qbr = $em->createQuery("select r ".
                                    "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                         "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ".
                                    "where r.pid=m.id and m.pid=:group ".
                                    "order by r.pid");
            $qbr->setParameter('group', $group['id']);
            $teamResults = $qbr->getResult();
            
            $teamsList = array();
            foreach ($teams as $team) {
                $id = $team['id'];
                $name = $team['name'];
                if ($team['division'] != '') {
                    $name.= ' "'.$team['division'].'"';
                }
                $country = $team['country'];

                $matches = 0;
                $points = 0;
                $score = 0;
                $goals = 0;
                
                $rel = 0;
                $relA = null;
                foreach ($teamResults as $matchRelation) {
                    if ($matchRelation->getPid() == $rel) {
                        $relB = $matchRelation;
                        $valid = $this->isScoreValid($relA, $relB);
                        if ($valid) {
                            if ($relA->getCid() == $id) {
                                $matches++;
                                $points += $relA->getPoints();
                                $score += $relA->getScore();
                                $goals += $relB->getScore();
                            }
                            else if ($relB->getCid() == $id) {
                                $matches++;
                                $points += $relB->getPoints();
                                $score += $relB->getScore();
                                $goals += $relA->getScore();
                            }
                        }
                    }
                    else {
                        $relA = $matchRelation;
                        $rel = $matchRelation->getPid();
                    }
                }
                
                $td = array('id' => $id,
                            'name' => $name,
                            'country' => $country,
                            'matches' => $matches,
                            'score' => $score,
                            'goals' => $goals,
                            'diff' => $score - $goals,
                            'points' => $points);
                $teamsList[] = $td;
            }

            $reorder = true;
            while ($reorder) {
                $reorder = false;
                for ($index = 0; $index < count($teamsList)-1; $index++) {
                    if ($this->reorder($teamsList[$index], $teamsList[$index+1])) {
                        $tmp = $teamsList[$index+1];
                        $teamsList[$index+1] = $teamsList[$index];
                        $teamsList[$index] = $tmp;
                        $reorder = true;
                    }
                }
            }

            $championList[$group['catid']][] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'championlist' => $championList);
    }
}
