<?php
namespace ICup\Bundle\PublicSiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CategoryController extends Controller
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
     * @Route("/category/{categoryid}", name="_showcategory")
     * @Template("ICupPublicSiteBundle:Default:category.html.twig")
     */
    public function listAction($categoryid)
    {
        DefaultController::switchLanguage($this);
        $countries = DefaultController::getCountries();
        $em = $this->getDoctrine()->getManager();

        $groupList = array();

        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryid);

        $groups = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')
                            ->findBy(array('pid' => $category->getId()));
        foreach ($groups as $group) {
            $qb = $em->createQuery("select t.id,t.name,t.division,c.country ".
                                   "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                                   "where o.pid=:group and ".
                                         "o.cid=t.id and ".
                                         "t.pid=c.id ".
                                   "order by o.id");
            $qb->setParameter('group', $group->getId());
            $teams = $qb->getResult();
            
            $qbr = $em->createQuery("select r ".
                                    "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                         "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ".
                                    "where r.pid=m.id and m.pid=:group ".
                                    "order by r.pid");
            $qbr->setParameter('group', $group->getId());
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
                            'flag' => $countries[$country],
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

            $groupList[$group->getName()] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('category' => $category, 'grouplist' => $groupList, 'imagepath' => DefaultController::getImagePath($this));
    }
}
