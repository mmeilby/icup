<?php

namespace ICup\Bundle\PublicSiteBundle\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Exception\ParseException;

class DefaultController extends Controller
{
    /**
     * @Route("/init")
     * @Template()
     */
    public function indexAction()
    {
        try {
            $dbConfig = file_get_contents(dirname(__DIR__) . '/Tests/Data/InteramniaWorldCup2013.xml');
        } catch (ParseException $e) {
            echo dirname(__DIR__) . '/Tests/Data/InteramniaWorldCup2013.xml<br />';
            echo 'Could not parse the query form config file: ' . $e->getMessage();
            throw new ParseException('Could not parse the query form config file: ' . $e->getMessage());
        }
        try {
            $xml = simplexml_load_string($dbConfig, null, LIBXML_NOWARNING);
            $this->drillDownXml($xml->host);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return array('name' => 'JohnDoe');
    }

    /**
     * @Route("/init2")
     * @Template()
     */
    public function index2Action()
    {
        try {
            $dbConfig = file_get_contents(dirname(__DIR__) . '/Tests/Data/InteramniaWorldCup2013.xml');
        } catch (ParseException $e) {
            throw new ParseException('Could not parse the query form config file: ' . $e->getMessage());
        }
        $xml = simplexml_load_string($dbConfig, null, LIBXML_NOWARNING);
        $this->drillDownXml2($xml->host);
        return array('name' => 'JohnDoe');
    }

    private function drillDownXml($host) {
        $em = $this->getDoctrine()->getManager();

        $hostrec = new Host();
        $hostrec->setName((String)$host->name);
        $em->persist($hostrec);
        $em->flush();
    
        $tournament = $host->tournament;

        $tournamentrec = new Tournament();
        $tournamentrec->setPid($hostrec->getId());
        $tournamentrec->setName((String)$tournament->name);
        $em->persist($tournamentrec);
        $em->flush();

        foreach ($tournament->sites->site as $site) {
            $siterec = new Site();
            $siterec->setPid($tournamentrec->getId());
            $siterec->setName((String)$site->name);
            $em->persist($siterec);
            $em->flush();

            foreach ($site->playground as $playground) {
                $playgroundrec = new Playground();
                $playgroundrec->setPid($siterec->getId());
                $playgroundrec->setName((String)$playground->name);
                $playgroundrec->setNo((String)$playground->no);
                $em->persist($playgroundrec);
            }
        }
        $em->flush();

        foreach ($tournament->categories->category as $category) {
            $categoryrec = new Category();
            $categoryrec->setPid($tournamentrec->getId());
            $categoryrec->setName((String)$category->name);
            $categoryrec->setGender((String)$category->gender);
            $categoryrec->setClassification((String)$category->classification);
            $em->persist($categoryrec);
            $em->flush();
            
            foreach ($category->group as $group) {
                $grouprec = new Group();
                $grouprec->setPid($categoryrec->getId());
                $grouprec->setName((String)$group->name);
                $grouprec->setPlayingtime((String)$category->playing_time);
                $grouprec->setClassification(0);
                $em->persist($grouprec);
                $em->flush();
                
                foreach ($group->team as $team) {
                    $this->addTeam($em, $grouprec, $team);
                }
            }
        }
    }
 
    private function drillDownXml2($host) {
        $em = $this->getDoctrine()->getManager();
        echo 'Drill2<br />';
        
        foreach ($host->tournament->results as $result) {
            $res = array();
            $keywords = preg_split("/[\s]+/", $result->result);
            $c = 0;
            foreach ($keywords as $token) {
                if ($token != '') {
                    switch ($c) {
                        case 0:
                            $res['id'] = $token;
                            $c++;
                            break;
                        case 1:
                            $res['playground'] = $token;
                            $c++;
                            break;
                        case 2:
                            $res['time'] = $token;
                            $c++;
                            break;
                        case 3:
                            $res['category'] = $token;
                            $c++;
                            break;
                        case 4:
                            $res['group'] = $token;
                            $c++;
                            break;
                        case 5:
                            $res['teamA'] = $token;
                            $res['teamAPart'] = '';
                            $c++;
                            break;
                        case 6:
                            if (preg_match('/\([\w]+\)/', $token)) {
                                $res['teamACountry'] = substr($token, 1, 3);
                                $c++;
                            }
                            elseif (preg_match('/\"[\w]+\"/', $token)) {
                                $res['teamAPart'] = substr($token, 1, 1);
                            }
                            else {
                                $res['teamA'] .= ' ' . $token;
                            }
                            break;
                        case 7:
                            $res['teamB'] = $token;
                            $res['teamBPart'] = '';
                            $c++;
                            break;
                        case 8:
                            if (preg_match('/\([\w]+\)/', $token)) {
                                $res['teamBCountry'] = substr($token, 1, 3);
                                $c = 0;

                                $scoreA = rand(0, 30);
                                if (rand(0, 10) == 5) {
                                    $scoreB = $scoreA;
                                }
                                else {
                                    $scoreB = rand(0, 30);
                                }
                                if ($scoreA > $scoreB) {
                                    $pointsA = 3;
                                    $pointsB = 0;
                                }
                                else if ($scoreB > $scoreA) {
                                    $pointsA = 0;
                                    $pointsB = 3;
                                }
                                else {
                                    $pointsA = 1;
                                    $pointsB = 1;
                                }
                                $matchrec = new Match();
                                $matchrec->setDate((String)$result->date);
                                $matchrec->setMatchno($res['id']);
                                $matchrec->setPid($this->getGroup($em, $res['category'], $res['group']));
                                $matchrec->setPlayground($this->getPlayground($em, $res['playground']));
                                $matchrec->setTime($res['time']);
                                $em->persist($matchrec);
                                $em->flush();

                                $teamA = $this->getTeam($em, $matchrec->getPid(), $res['teamA'], $res['teamACountry'], $res['teamAPart']);
                                $resultreq = new MatchRelation();
                                $resultreq->setPid($matchrec->getId());
                                $resultreq->setCid($teamA->getId());
                                $resultreq->setAwayteam(false);
                                $resultreq->setScorevalid(false);
                                $resultreq->setScore($scoreA);
                                $resultreq->setPoints($pointsA);
                                $em->persist($resultreq);

                                $teamB = $this->getTeam($em, $matchrec->getPid(), $res['teamB'], $res['teamBCountry'], $res['teamBPart']);
                                $resultreq = new MatchRelation();
                                $resultreq->setPid($matchrec->getId());
                                $resultreq->setCid($teamB->getId());
                                $resultreq->setAwayteam(true);
                                $resultreq->setScorevalid(false);
                                $resultreq->setScore($scoreB);
                                $resultreq->setPoints($pointsB);
                                $em->persist($resultreq);
                                
                                echo 'Update '.$res['id'].'<br />';
                                $res = array();
                            }
                            elseif (preg_match('/\"[\w]+\"/', $token)) {
                                $res['teamBPart'] = substr($token, 1, 1);
                            }
                            else {
                                $res['teamB'] .= ' ' . $token;
                            }
                            break;
                    }
                }
            }
        }
        $em->flush();
    }
 
    private function addTeam($em, $grouprec, $team) {
        $keywords = preg_split("/[\s]+/", $team->name);
        $teamPart = '';
        $c = 0;
        foreach ($keywords as $token) {
            if ($token != '') {
                switch ($c) {
                    case 0:
                        $teamName = $token;
                        $c++;
                        break;
                    case 1:
                        if (preg_match('/\([\w]+\)/', $token)) {
                            $teamCountry = substr($token, 1, 3);
                            $c++;
                        }
                        elseif (preg_match('/\"[\w]+\"/', $token)) {
                            $teamPart = substr($token, 1, 1);
                        }
                        else {
                            $teamName .= ' ' . $token;
                        }
                        break;
                }
            }
        }
        $clubrec = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                        ->findOneBy(array('name' => $teamName, 'country' => $teamCountry));
        if (!$clubrec) {
            $clubrec = new Club();
            $clubrec->setName($teamName);
            $clubrec->setCountry($teamCountry);
            $em->persist($clubrec);
            $em->flush();
        }
        
        $teamrec = new Team();
        $teamrec->setPid($clubrec->getId());
        $teamrec->setName($teamName);
        $teamrec->setDivision($teamPart);
        $teamrec->setColor('N/A');
        $em->persist($teamrec);
        $em->flush();
        
        $grouporderrec = new GroupOrder();
        $grouporderrec->setPid($grouprec->getId());
        $grouporderrec->setCid($teamrec->getId());
        $em->persist($grouporderrec);
        $em->flush();
    }
    
    private function getTeam($em, $group, $name, $country, $division) {
        $qb = $em->createQuery("select t ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where o.pid=:group and o.cid=t.id and t.pid=c.id and t.name=:name and t.division=:division and c.country=:country");
        $qb->setParameter('group', $group);
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        $qb->setParameter('country', $country);
        foreach ($qb->getResult() as $teamrec) {
            return $teamrec;
        }
        echo 'Can not find team '.$name.'<br />';
        return new Team();
    }
    
    private function getGroup($em, $category, $group) {
        $categoryrec = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                        ->findOneBy(array('name' => $category));
        if (!$categoryrec) {
            echo 'Can not find category '.$category.'<br />';
            return 0;
        }
        $grouprec = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')
                        ->findOneBy(array('pid' => $categoryrec->getId(), 'name' => $group));
        if (!$grouprec) {
            echo 'Can not find group '.$group.'<br />';
            return 0;
        }
        return $grouprec->getId();
    }
    
    private function getPlayground($em, $playground) {
        $playgroundrec = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground')
                        ->findOneBy(array('no' => $playground));
        if (!$playgroundrec) {
            echo 'Can not find playground '.$playground.'<br />';
            return 0;
        }
        return $playgroundrec->getId();
    }

    public function switchAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $session->set('_locale', 'en_US');
        return $this->render('ICupPublicSiteBundle:Default:index.html.twig');
    }
    
    public static function getCountries()
    {
        try {
            $dbConfig = file_get_contents(dirname(__DIR__) . '/Resources/config/countries.xml');
        } catch (ParseException $e) {
            throw new ParseException('Could not parse the query form config file: ' . $e->getMessage());
        }
        $xml = simplexml_load_string($dbConfig, null, LIBXML_NOWARNING);
        $countries = array();
        foreach ($xml as $country) {
            $countries[(String)$country->ccode] = (String)$country->cflag;
        }
        return $countries;
    }
    
    public static function getTournament($controller) {
        return 1;
    }
    
    public static function getImagePath($controller) {
        return '/icup/web/bundles/icuppublicsite/images';
    }
}
