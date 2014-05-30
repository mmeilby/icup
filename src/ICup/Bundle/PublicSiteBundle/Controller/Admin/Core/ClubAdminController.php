<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Core;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

/**
 * Club administrator core functions
 */
class ClubAdminController extends Controller
{
    /**
     * Remove club relation from user identified by userid
     * Current user must be club administrator assigned to the same club as the user to be disconnected
     * @Route("/club/disc/{userid}", name="_club_user_disconnect")
     * @Method("GET")
     */
    public function disconnectAction($userid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $this->validateClubUser($user);
        // Validate current user - is it a club administrator?
        $thisuser = $utilService->getCurrentUser();
        $utilService->validateClubAdminUser($thisuser, $user->getCid());
        // Disconnect user from club - make user a verified user with no relation
        // However cid should not be cleared in order to restore the connection if in error
        $user->setRole(User::$CLUB);
        $user->setStatus(User::$PRO);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page_users'));
    }

    /**
     * Promote user identified by userid as attached user to club identified by clubid
     * Current user must be club administrator assigned to the club
     * The promoted user must be related to the club as a prospect
     * This function can not promote non related users
     * @Route("/club/connect/{clubid}/{userid}", name="_club_user_connect")
     * @Method("GET")
     */
    public function connectAction($clubid, $userid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $this->validateClubUser($user);
        // Validate user - must be a club user prospect
        if (!$user->isRelatedTo($clubid)) {
            // User is not related to the club
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId().", clubid=".$clubid);
        }
        // Validate current user - is it a club administrator?
        $thisuser = $utilService->getCurrentUser();
        $utilService->validateClubAdminUser($thisuser, $clubid);
        // Connect user to the club - make user an attached user
        $user->setStatus(User::$ATT);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page_users'));
    }

    /**
     * Switch user role for user identified by userid
     * Club users will be promoted to club administrators
     * Club administrators will be demoted to club users
     * Current user must be club administrator assigned to the same club as the user to switch role
     * @Route("/club/chgrole/{userid}", name="_club_user_chg_role")
     * @Method("GET")
     */
    public function chgRoleAction($userid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $this->validateClubUser($user);
        // Validate current user - is it a club administrator?
        $thisuser = $utilService->getCurrentUser();
        $utilService->validateClubAdminUser($thisuser, $user->getCid());
        // Switch user role
        $user->setRole($user->getRole() === User::$CLUB ? User::$CLUB_ADMIN : User::$CLUB);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page_users'));
    }

    /**
     * Submit request for current user to be an attached user to club identified by clubid
     * Current user must be a non related plain user
     * This function can not promote related prospect users
     * NOTE: this action will be requested from javascript and can not be parameterized the traditional Symfony way
     * @Route("/user/request", name="_club_user_request")
     * @Method("GET")
     */
    public function requestAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        $clubid = $this->getRequest()->get('clubid', '');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        if (!$user->isClub()) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "userid=".$user->getId().", role=".$user->getRole());
        }
        // Validate user - must be an unrelated user
        if ($user->isRelated()) {
            // User is related to the club
            throw new ValidationException("CANNOTBERELATED", "userid=".$user->getId());
        }
        // Validate club id
        $club = $this->get('entity')->getClubById($clubid);
        // Connect user to the club - make user a prospected user
        $user->setStatus(User::$PRO);
        $user->setCid($club->getId());
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    /**
     * Cancel submitted request for club relation from current user
     * Current user must be prospect user for any club
     * User will be reset to a verified user with no relation
     * @Route("/user/refuse", name="_club_user_refuse")
     * @Method("GET")
     */
    public function refuseAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $this->validateClubUser($user);
        // Disconnect user from club - make user a verified user with no relation
        // However cid should not be cleared in order to restore the connection if in error
        $user->setRole(User::$CLUB);
        $user->setStatus(User::$VER);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    private function validateClubUser(User $user) {
        if (!$user->isClub()) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "userid=".$user->getId().", role=".$user->getRole());
        }
        // Validate user - must be a prospect user
        if (!$user->isRelated()) {
            // User is not related to any club
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId());
        }
    }
}
