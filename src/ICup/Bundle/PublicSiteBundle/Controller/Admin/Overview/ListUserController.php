<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ListUserController extends Controller
{
    /**
     * List the users related to a club
     * @Route("/admin/list/club/{clubid}", name="_edit_user_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listusers.html.twig")
     */
    public function listUsersAction($clubid)
    {
        /* @var $club Club */
        $club = $this->get('entity')->getClubById($clubid);
        $users = $club->getClubMembers();

        return array('club' => $club, 'users' => $users);
    }
}
