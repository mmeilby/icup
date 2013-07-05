<?php
namespace ICup\Bundle\PublicSiteBundle\Controller;

use DateTime;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

class EditMatchController extends Controller
{
    private function isScoreValid($relA, $relB) {
        return $relA['scorevalid']=='Y' && $relB['scorevalid']=='Y';
    }
    
    private function reorderMatch($match1, $match2) {
        return $match1['schedule'] > $match2['schedule'];
    }

    /**
     * @Route("/edit/{playgroundid}/{date}", name="_editmatch")
     * @Secure(roles="ROLE_ADMIN")
     * @Template("ICupPublicSiteBundle:Default:editmatch.html.twig")
     */
    public function listAction($playgroundid, $date)
    {
        $countries = DefaultController::getCountries();
        $em = $this->getDoctrine()->getManager();

        $tournamentId = DefaultController::getTournament($this);
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $playground = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground')
                            ->find($playgroundid);

        $qb = $em->createQuery("select m.id,m.matchno,m.date,m.time,g.id as gid,g.name as grp,cat.name as category,r.id as rid,r.awayteam,r.scorevalid,r.score,r.points,t.id as tid,t.name as team,t.division,c.country ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where m.playground=:playground and ".
                                     "m.date=:date and ".
                                     "m.pid=g.id and ".
                                     "g.pid=cat.id and ".
                                     "r.pid=m.id and ".
                                     "t.id=r.cid and ".
                                     "c.id=t.pid ".
                               "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        $qb->setParameter('date', str_replace('-', '/', $date));
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
                    $matchList[] = array('id' => $match['id'],
                                         'matchno' => $matchno,
                                         'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
                                         'category' => $match['category'],
                                         'gid' => $match['gid'],
                                         'group' => $match['grp'],
                                         'idA' => $relB['tid'],
                                         'teamA' => $nameB,
                                         'countryA' => $relB['country'],
                                         'flagA' => $countries[$relB['country']],
                                         'idB' => $relA['tid'],
                                         'teamB' => $nameA,
                                         'countryB' => $relA['country'],
                                         'flagB' => $countries[$relA['country']],
                                         'ridA' => $relB['rid'],
                                         'ridB' => $relA['rid'],
                                         'scoreA' => $valid ? $relB['score'] : '',
                                         'scoreB' => $valid ? $relA['score'] : '',
                                         'pointsA' => $valid ? $relB['points'] : '',
                                         'pointsB' => $valid ? $relA['points'] : ''
                                        );
                }
                else {
                    $matchList[] = array('id' => $match['id'],
                                         'matchno' => $matchno,
                                         'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
                                         'category' => $match['category'],
                                         'gid' => $match['gid'],
                                         'group' => $match['grp'],
                                         'idA' => $relA['tid'],
                                         'teamA' => $nameA,
                                         'countryA' => $relA['country'],
                                         'flagA' => $countries[$relA['country']],
                                         'idB' => $relB['tid'],
                                         'teamB' => $nameB,
                                         'countryB' => $relB['country'],
                                         'flagB' => $countries[$relB['country']],
                                         'ridA' => $relA['rid'],
                                         'ridB' => $relB['rid'],
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

    /**
     * @Route("/edit", name="_editmatchpost")
     * @Method("POST")
     * @Template("ICupPublicSiteBundle:Default:index.html.twig")
     */
    public function postAction()
    {
        $em = $this->getDoctrine()->getManager();

        $updatedRelations = array();
        foreach ($this->getRequest()->request as $key => $value) {
            $rid = str_ireplace("score_", "", $key);
            $mr = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation')
                                ->find($rid);
            if ($mr != null) {
                if ($value != "") {
                    $mr->setScorevalid(true);
                    $mr->setScore($value);
                }
                else {
                    $mr->setScorevalid(false);
                }
                $mid = $mr->getPid();
                if (key_exists($mid, $updatedRelations)) {
                    $updatedRelations[$mid][] = $mr;
                }
                else {
                    $updatedRelations[$mid] = array($mr);
                }
            }
        }
        $playgroundid = 0;
        foreach ($updatedRelations as $m => $relationslist) {
            $relA = $relationslist[0];
            $relB = $relationslist[1];
            if ($relA->getScorevalid() && $relB->getScorevalid()) {
                if ($relA->getScore() > $relB->getScore()) {
                    $relA->setPoints(3);
                    $relB->setPoints(0);
                }
                else if ($relA->getScore() < $relB->getScore()) {
                    $relA->setPoints(0);
                    $relB->setPoints(3);
                }
                else {
                    $relA->setPoints(1);
                    $relB->setPoints(1);
                }
                $em->persist($relA);
                $em->persist($relB);
            }
            else {
                $relA->setScorevalid(false);
                $relB->setScorevalid(false);
                $em->persist($relA);
                $em->persist($relB);
            }
            if ($playgroundid == 0) {
                $match = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match')
                                    ->find($m);
                if ($match != null) {
                    $playgroundid = $match->getPlayground();
                }
            }
        }
        $em->flush();
        return $this->redirect($this->generateUrl('_showplayground_full', array('playgroundid' => $playgroundid)));
    }    
    
    /**
     * @Route("/edit/login", name="_admin_login")
     * @Template("ICupPublicSiteBundle:Default:login.html.twig")
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        $tournamentId = DefaultController::getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        return array(
            'last_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME),
            'tournament'    => $tournament,
            'error'         => $error,
        );
    }

    /**
     * @Route("/edit/login_check", name="_security_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * @Route("/edit/logout", name="_admin_logout")
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }

}
