<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TeamController extends Controller
{
    private function isScoreValid($relA, $relB) {
        return $relA['scorevalid']=='Y' && $relB['scorevalid']=='Y';
    }
    
    private function reorderMatch($match1, $match2) {
        return $match1['schedule'] > $match2['schedule'];
    }

    /**
     * @Route("/tmnt/{tournament}/tm/{teamid}/{groupid}", name="_showteam")
     * @Template("ICupPublicSiteBundle:Tournament:team.html.twig")
     */
    public function listAction($tournament, $teamid, $groupid)
    {
        $this->get('util')->setupController($tournament);
        $tournament = $this->get('util')->getTournament();
        $team = $this->get('entity')->getTeamById($teamid);
        $name = $team->getName();
        if ($team->getDivision() != '') {
            $name.= ' "'.$team->getDivision().'"';
            $team->setName($name);
        }
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());

        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQuery("select m.matchno,m.date,m.time,p.id as playgroundid,p.no,p.name as playground,r.awayteam,r.scorevalid,r.score,r.points,t.id,t.name as team,t.division,c.country ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground p, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where m.pid=:group and ".
                                     "m.id in (select rx.pid from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation rx where rx.cid=:team) and ".
                                     "p.id=m.playground and ".
                                     "r.pid=m.id and ".
                                     "t.id=r.cid and ".
                                     "c.id=t.pid ".
                               "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        $matches = $qb->getResult();
        
        $matchList = array();

        $rel = 0;
        $relA = null;
        foreach ($matches as $match) {
            $matchno = $match['matchno'];
            if ($matchno == $rel) {
                $relB = $match;
                $valid = $this->isScoreValid($relA, $relB);
                $nameA = $relA['team'];
                if ($relA['division'] != '') {
                    $nameA.= ' "'.$relA['division'].'"';
                }
                $nameB = $relB['team'];
                if ($relB['division'] != '') {
                    $nameB.= ' "'.$relB['division'].'"';
                }
                if ($relA['awayteam'] == 'Y') {
                    $matchList[] = array('matchno' => $matchno,
                                         'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
                                         'playgroundid' => $match['playgroundid'],
                                         'playgroundno' => $match['no'],
                                         'playground' => $match['playground'],
                                         'idA' => $relB['id'],
                                         'teamA' => $nameB,
                                         'countryA' => $relB['country'],
                                         'idB' => $relA['id'],
                                         'teamB' => $nameA,
                                         'countryB' => $relA['country'],
                                         'scoreA' => $valid ? $relB['score'] : '',
                                         'scoreB' => $valid ? $relA['score'] : '',
                                         'pointsA' => $valid ? $relB['points'] : '',
                                         'pointsB' => $valid ? $relA['points'] : ''
                                        );
                }
                else {
                    $matchList[] = array('matchno' => $matchno,
                                         'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
                                         'playgroundid' => $match['playgroundid'],
                                         'playgroundno' => $match['no'],
                                         'playground' => $match['playground'],
                                         'idA' => $relA['id'],
                                         'teamA' => $nameA,
                                         'countryA' => $relA['country'],
                                         'idB' => $relB['id'],
                                         'teamB' => $nameB,
                                         'countryB' => $relB['country'],
                                         'scoreA' => $valid ? $relA['score'] : '',
                                         'scoreB' => $valid ? $relB['score'] : '',
                                         'pointsA' => $valid ? $relA['points'] : '',
                                         'pointsB' => $valid ? $relB['points'] : ''
                                        );
                }
            }
            else {
                $relA = $match;
                $rel = $matchno;
            }
        }
            
        $reorder = true;
        while ($reorder) {
            $reorder = false;
            for ($index = 0; $index < count($matchList)-1; $index++) {
                if ($this->reorderMatch($matchList[$index], $matchList[$index+1])) {
                    $tmp = $matchList[$index+1];
                    $matchList[$index+1] = $matchList[$index];
                    $matchList[$index] = $tmp;
                    $reorder = true;
                }
            }
        }
        return array('tournament' => $tournament, 'category' => $category, 'group' => $group, 'team' => $team, 'matchlist' => $matchList);
    }
}
