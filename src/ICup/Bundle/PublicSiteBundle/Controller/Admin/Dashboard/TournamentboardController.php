<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Dashboard;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use DateTime;
use Symfony\Cmf\Bundle\MediaBundle\File\UploadFileHelperInterface;
use PHPCR\Util\NodeHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Admin dashboard
 */
class TournamentboardController extends Controller
{
    /**
     * Show myICup page for authenticated users
     * @Route("/edit/tournamentboard/{tournamentid}", name="_edit_tournamentboard")
     * @Template("ICupPublicSiteBundle:Edit:tournamentboard.html.twig")
     */
    public function tournamentboardAction($tournamentid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());
        $today = new DateTime();
        $tstat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
        return array('host' => $host,
                     'tournament' => $tournament,
                     'tstat' => $tstat);
    }
}
