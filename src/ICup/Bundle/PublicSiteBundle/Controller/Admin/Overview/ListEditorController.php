<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class ListEditorController extends Controller
{
    /**
     * List the editors related to logged in editor admin
     * @Route("/edit/user/list/host", name="_edit_editors_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listeditors.html.twig")
     */
    public function listEditorsAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        // Validate current user - is it an editor?
        $utilService->validateEditorUser($user);
        // Get the host from current user
        $host = $user->getHost();
        $users = $host->getEditors();

        return array('host' => $host, 'users' => $users, 'currentuser' => $user);
    }
    
    /**
     * List the editors related to a host
     * @Route("/admin/user/list/host/{hostid}", name="_edit_editor_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listeditors.html.twig")
     */
    public function listUsersAction($hostid)
    {
        /* @var $user User */
        $user = $this->get('util')->getCurrentUser();
        /* @var $host Host */
        $host = $this->get('entity')->getHostById($hostid);
        $users = $host->getEditors();

        return array('host' => $host, 'users' => $users, 'currentuser' => $user);
    }
}
