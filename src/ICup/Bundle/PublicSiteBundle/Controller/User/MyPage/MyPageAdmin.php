<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User\MyPage;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use DateTime;

/**
 * myPage - myICup - user's home page with context dependent content
 */
class MyPageAdmin implements MyPageInterface
{
    /* @var $container Controller */
    protected $container;
    /* @var $user User */
    private $user;

    /**
     * Show myICup page for authenticated users
     */
    public function getTwig() {
        return 'ICupPublicSiteBundle:User:mypage.html.twig';
    }

    // getMyPageParameters
    public function getParms() {
        return array_merge(array('currentuser' => $this->user), $this->getTournaments());
    }

    public function __construct(Controller $container, User $user) {
        $this->container = $container;
        $this->user = $user;
    }

    private function getTournaments() {
        $tournaments = $this->container->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $keyList = array(
            TournamentSupport::$TMNT_ENROLL => 'enroll',
            TournamentSupport::$TMNT_GOING => 'active',
            TournamentSupport::$TMNT_DONE => 'done',
            TournamentSupport::$TMNT_ANNOUNCE => 'announce'
        );
        $statusList = array();
        foreach ($keyList as $keylabel) {
            $statusList[$keylabel] = array();
        }
        $today = new DateTime();
        foreach ($tournaments as $tournament) {
            $stat = $this->container->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE && $stat != TournamentSupport::$TMNT_DONE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
                $statusList[$keyList[$stat]][] = $tournament;
            }
        }
        return array('tournaments' => $tournamentList, 'statuslist' => $statusList);
    }
}
