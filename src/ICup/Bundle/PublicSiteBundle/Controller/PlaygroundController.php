<?php
namespace ICup\Bundle\PublicSiteBundle\Controller;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlaygroundController extends Controller
{
    private function isScoreValid($relA, $relB) {
        return $relA['scorevalid']=='Y' && $relB['scorevalid']=='Y';
    }
    
    private function reorderMatch($match1, $match2) {
        return $match1['schedule'] > $match2['schedule'];
    }

    /**
     * @Route("/playground/{playgroundid}/{groupid}", name="_showplayground")
     * @Template("ICupPublicSiteBundle:Default:playground.html.twig")
     */
    public function listAction($playgroundid, $groupid)
    {
        $countries = DefaultController::getCountries();
        $em = $this->getDoctrine()->getManager();

        $playground = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground')
                            ->find($playgroundid);

        $group = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')
                            ->find($groupid);

        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($group->getPid());
        
        $qb = $em->createQuery("select m.matchno,m.date,m.time,r.awayteam,r.scorevalid,r.score,r.points,t.id,t.name as team,t.division,c.country ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where m.pid=:group and ".
                                     "m.playground=:playground and ".
                                     "r.pid=m.id and ".
                                     "t.id=r.cid and ".
                                     "c.id=t.pid ".
                               "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('playground', $playgroundid);
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
                                         'idA' => $relB['id'],
                                         'teamA' => $nameB,
                                         'countryA' => $relB['country'],
                                         'flagA' => $countries[$relB['country']],
                                         'idB' => $relA['id'],
                                         'teamB' => $nameA,
                                         'countryB' => $relA['country'],
                                         'flagB' => $countries[$relA['country']],
                                         'scoreA' => $valid ? $relB['score'] : '',
                                         'scoreB' => $valid ? $relA['score'] : '',
                                         'pointsA' => $valid ? $relB['points'] : '',
                                         'pointsB' => $valid ? $relA['points'] : ''
                                        );
                }
                else {
                    $matchList[] = array('matchno' => $matchno,
                                         'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
                                         'idA' => $relA['id'],
                                         'teamA' => $nameA,
                                         'countryA' => $relA['country'],
                                         'flagA' => $countries[$relA['country']],
                                         'idB' => $relB['id'],
                                         'teamB' => $nameB,
                                         'countryB' => $relB['country'],
                                         'flagB' => $countries[$relB['country']],
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
        return array('category' => $category, 'group' => $group, 'playground' => $playground, 'matchlist' => $matchList, 'imagepath' => DefaultController::getImagePath($this));
    }
    
    /**
     * @Route("/playground/{playgroundid}", name="_showplayground_full")
     * @Template("ICupPublicSiteBundle:Default:playground.full.html.twig")
     */
    public function listAllAction($playgroundid)
    {
        $countries = DefaultController::getCountries();
        $em = $this->getDoctrine()->getManager();

        $tournamentId = DefaultController::getTournament($this);
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $playground = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground')
                            ->find($playgroundid);

        $qb = $em->createQuery("select m.matchno,m.date,m.time,g.id as gid,g.name as grp,cat.name as category,r.awayteam,r.scorevalid,r.score,r.points,t.id,t.name as team,t.division,c.country ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where m.playground=:playground and ".
                                     "m.pid=g.id and ".
                                     "g.pid=cat.id and ".
                                     "r.pid=m.id and ".
                                     "t.id=r.cid and ".
                                     "c.id=t.pid ".
                               "order by m.id");
        $qb->setParameter('playground', $playgroundid);
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
                                         'category' => $match['category'],
                                         'gid' => $match['gid'],
                                         'group' => $match['grp'],
                                         'idA' => $relB['id'],
                                         'teamA' => $nameB,
                                         'countryA' => $relB['country'],
                                         'flagA' => $countries[$relB['country']],
                                         'idB' => $relA['id'],
                                         'teamB' => $nameA,
                                         'countryB' => $relA['country'],
                                         'flagB' => $countries[$relA['country']],
                                         'scoreA' => $valid ? $relB['score'] : '',
                                         'scoreB' => $valid ? $relA['score'] : '',
                                         'pointsA' => $valid ? $relB['points'] : '',
                                         'pointsB' => $valid ? $relA['points'] : ''
                                        );
                }
                else {
                    $matchList[] = array('matchno' => $matchno,
                                         'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
                                         'category' => $match['category'],
                                         'gid' => $match['gid'],
                                         'group' => $match['grp'],
                                         'idA' => $relA['id'],
                                         'teamA' => $nameA,
                                         'countryA' => $relA['country'],
                                         'flagA' => $countries[$relA['country']],
                                         'idB' => $relB['id'],
                                         'teamB' => $nameB,
                                         'countryB' => $relB['country'],
                                         'flagB' => $countries[$relB['country']],
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
        return array('tournament' => $tournament, 'playground' => $playground, 'matchlist' => $matchList, 'imagepath' => DefaultController::getImagePath($this));
    }
}
