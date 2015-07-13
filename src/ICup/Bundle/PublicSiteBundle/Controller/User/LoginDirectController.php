<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

class LoginDirectController extends Controller
{
    /**
     * Login users and administrators
     * @Route("/login/d/{username}/{securekey}", name="_admin_direct_login")
     * @Method("GET")
     */
    public function loginDirectAction($username, $securekey, Request $request)
    {
        if ($this->get('logic')->isUserKnown($username)) {
            /* @var $user User */
            $user = $this->get('logic')->getUserByName($username);
            if ($user->getSecret() && strlen(trim($user->getSecret())) > 20 && $securekey == $user->getSecret()) {
                $user->setAttempts(0);
                $user->setSecret("");
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                $token = new UsernamePasswordToken($user, null, 'direct_login', $user->getRoles());
                $this->get('security.context')->setToken($token);
                $this->get('event_dispatcher')->dispatch(AuthenticationEvents::AUTHENTICATION_SUCCESS, new AuthenticationEvent($token));
                return $this->redirect($this->generateUrl('_user_chg_pass'));
            }
            else {
                $this->get('logger')->addWarning("Invalid attempt to login directly: " . $user->getUsername() . " - id=" . $user->getId());
            }
        }
        else {
            $this->get('logger')->addWarning("Invalid attempt to login directly - unknown name: " . $username);
        }
        return $this->redirect($this->generateUrl('_admin_login'));
    }
}
