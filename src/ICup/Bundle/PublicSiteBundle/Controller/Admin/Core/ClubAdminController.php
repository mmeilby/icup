<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Core;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\SocialGroup;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\SocialRelation;
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
     * Remove social group relation from user identified by userid
     * Current user must be social group administrator assigned to the same group as the user to be disconnected
     * @Route("/club/disc/{socialgroupid}/{userid}", name="_club_user_disconnect")
     * @Method("GET")
     */
    public function disconnectAction($socialgroupid, $userid)
    {
        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $socialGroup = $this->validateClubUser($user, $socialgroupid);
        // Validate current user - is it a club administrator?
        $this->validateClubAdminUser($socialGroup);
        // Disconnect user from club - make user a verified user with no relation
        // However cid should not be cleared in order to restore the connection if in error
        $user->removeRole(User::ROLE_CLUB_ADMIN);
//        $user->setStatus(User::$PRO);
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
     * @Route("/club/connect/{socialgroupid}/{userid}", name="_club_user_connect")
     * @Method("GET")
     */
    public function connectAction($clubid, $userid, $socialgroupid)
    {
        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $socialGroup = $this->validateClubUser($user, $socialgroupid);
        // Validate user - must be a club user prospect
        if (!$user->isRelatedTo($clubid)) {
            // User is not related to the club
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId().", clubid=".$clubid);
        }
        // Validate current user - is it a club administrator?
        $this->validateClubAdminUser($socialGroup);
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
     * @Route("/club/chgrole/{socialgroupid}/{userid}", name="_club_user_chg_role")
     * @Method("GET")
     */
    public function chgRoleAction($userid, $socialgroupid)
    {
        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $socialGroup = $this->validateClubUser($user, $socialgroupid);
        // Validate current user - is it a club administrator?
        $this->validateClubAdminUser($socialGroup);
        // Switch user role
        if ($user->hasRole(User::ROLE_CLUB_ADMIN)) {
            $user->removeRole(User::ROLE_CLUB_ADMIN);
        }
        else {
            $user->addRole(User::ROLE_CLUB_ADMIN);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page_users'));
    }

    /**
     * Submit request for current user to be an attached user to club identified by clubid
     * Current user must be a non related plain user
     * This function can not promote related prospect users
     * @Route("/user/request/{socialgroupid}", name="_club_user_request", options={"expose"=true})
     * @Method("GET")
     */
    public function requestAction($clubid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        if (!$user->isClub()) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "user=".$user->__toString());
        }
        // Validate user - must be an unrelated user
        if ($user->isRelated()) {
            // User is related to the club
            throw new ValidationException("CANNOTBERELATED", "user=".$user->__toString());
        }
        // Validate club id
        $club = $this->get('entity')->getClubById($clubid);
        // Connect user to the club - make user a prospected user
        $user->setStatus(User::$PRO);
        $user->setClub($club);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    /**
     * Cancel submitted request for club relation from current user
     * Current user must be prospect user for any club
     * User will be reset to a verified user with no relation
     * @Route("/user/refuse/{socialgroupid}", name="_club_user_refuse")
     * @Method("GET")
     */
    public function refuseAction($socialgroupid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $socialGroup = $this->validateClubUser($user, $socialgroupid);
        // Disconnect user from club - make user a verified user with no relation
        // However cid should not be cleared in order to restore the connection if in error
        $user->removeRole(User::ROLE_CLUB_ADMIN);
//        $user->setStatus(User::$VER);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    /**
     * Ignore pending request for current user to be an attached user to club identified by clubid
     * Current user must be prospect user for any club
     * User will be attached to the club however relation is only for viewing
     * @Route("/user/dismiss/{socialgroupid}", name="_club_user_dismiss")
     * @Method("GET")
     */
    public function dismissAction($socialgroupid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $socialGroup = $this->validateClubUser($user, $socialgroupid);
        // Connect user to the club - make user an ignored user
        $user->removeRole(User::ROLE_CLUB_ADMIN);
//        $user->setStatus(User::$INF);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    /**
     * Ignore user identified by userid
     * Current user must be club administrator assigned to the same club as the user to be ignored
     * @Route("/club/ignore/{socialgroupid}/{userid}", name="_club_user_ignore")
     * @Method("GET")
     */
    public function ignoreAction($userid, $socialgroupid)
    {
        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $socialGroup = $this->validateClubUser($user, $socialgroupid);
        // Validate current user - is it a club administrator?
        $this->validateClubAdminUser($socialGroup);
        // Ignore user - make user an attached user with no rights other than following the club
        $user->removeRole(User::ROLE_CLUB_ADMIN);
//        $user->setStatus(User::$INF);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        // Redirect to my page
        return $this->redirect($this->generateUrl('_user_my_page_users'));
    }

    private function validateClubUser(User $user, $socialgroupid) {
        $relations = $user->getSocialRelations()->filter(function (SocialRelation $socialRelation) use ($socialgroupid) {
                        return $socialRelation->getGroup()->getId() == $socialgroupid;
                     });
        if (count($relations) == 0) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "user=".$user->__toString());
        }
        /* @var $socialRelation SocialRelation */
        $socialRelation = $relations->first();
        // Validate user - must be a prospect user
        if ($socialRelation->getStatus() != SocialRelation::$MEM) {
            // User is not related to any club
            throw new ValidationException("NEEDTOBERELATED", "user=".$user->__toString());
        }
        return $socialRelation->getGroup();
    }

    private function validateClubAdminUser(SocialGroup $socialGroup) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        // Validate current user - is it a club administrator?
        /* @var $clubadmin User */
        $clubadmin = $utilService->getCurrentUser();
        $relations = $clubadmin->getSocialRelations()->filter(function (SocialRelation $socialRelation) use ($socialGroup) {
                        return $socialRelation->getGroup()->getId() == $socialGroup->getId();
                     });
        if (count($relations) == 0) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "user=".$clubadmin->__toString());
        }
        /* @var $socialRelation SocialRelation */
        $socialRelation = $relations->first();
        if ($socialRelation->getRole() == SocialRelation::$OWNER ||
            $socialRelation->getRole() == SocialRelation::$EDITOR ||
            $socialRelation->getRole() == SocialRelation::$ADMIN) {
            return;
        }
        throw new ValidationException("NOTCLUBUSER", "user=".$clubadmin->__toString());
    }
}
