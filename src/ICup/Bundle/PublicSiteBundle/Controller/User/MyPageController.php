<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Controller\User\MyPage\MyPageAdmin;
use ICup\Bundle\PublicSiteBundle\Controller\User\MyPage\MyPageEditor;
use ICup\Bundle\PublicSiteBundle\Controller\User\MyPage\MyPageUser;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use User\MyPage\MyPageInterface;

/**
 * myPage - myICup - user's home page with context dependent content
 */
class MyPageController extends Controller
{
    /**
     * Show myICup page for authenticated users
     * @Route("/user/mypage", name="_user_my_page")
     * @Method("GET")
     */
    public function myPageAction()
    {
        $user = $this->get('util')->getCurrentUser();
        $mypage = $this->getController($user);
        return $this->render($mypage->getTwig(), $mypage->getParms());
    }

    /**
     * Show myICup page for club admin users
     * @Route("/club/mypage/users", name="_user_my_page_users")
     * @Method("GET")
     */
    public function myPageUsersAction()
    {
        $user = $this->get('util')->getCurrentUser();
        /* @var $club Club */
        $club = $user->getClub();
        $this->get('util')->validateClubAdminUser($user, $club);
        $users = $club->getUsers();
        usort($users, function (User $user1, User $user2) {
            // sort users
            return 1;
        });
        // Redirect to my page users list
        return $this->render('ICupPublicSiteBundle:User:mypage_users.html.twig',
                array('club' => $club,
                      'users' => $users,
                      'currentuser' => $user));
    }

    /**
     * @param User $user
     * @return MyPageInterface
     */
    private function getController(User $user) {
        if ($user->isAdmin()) {
            return new MyPageAdmin($this, $user);
        }
        elseif ($user->isEditor()) {
            return new MyPageEditor($this, $user);
        }
        return new MyPageUser($this, $user);
    }
}
