<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;

/**
 * List the events scheduled for a tournament
 */
class ListNewsController extends Controller
{
    /**
     * List the events available for a tournament
     * @Route("/edit/news/list/{tournamentid}", name="_edit_news_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Admin:tournamentnews.html.twig")
     */
    public function listAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
/*
        $news = $this->get('tmnt')->listNewsByTournament($tournamentid);
        usort($news,
            function ($news1, $news2) {
                if ($news1['schedule'] == $news2['schedule']) {
                    return 0;
                }
                elseif ($news1['schedule'] > $news2['schedule']) {
                    return 1;
                }
                else {
                    return -1;
                }
            }
        );
*/
        return array('host' => $host,
                     'tournament' => $tournament,
//                     'newslist' => $news
                );
    }
}
