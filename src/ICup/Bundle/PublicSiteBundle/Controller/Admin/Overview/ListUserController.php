<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ListUserController extends Controller
{
    /**
     * List the users related to a club
     * @Route("/user/list/club/{clubid}", name="_edit_user_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listusers.html.twig")
     */
    public function listUsersAction($clubid)
    {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();

        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }
        
        $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                ->findBy(array('cid' => $clubid));

        return array('club' => $club, 'users' => $users);
    }
}
