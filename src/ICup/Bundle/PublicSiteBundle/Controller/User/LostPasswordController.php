<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Swift_Message;

class LostPasswordController extends Controller
{
    /**
     * Login users and administrators
     * @Route("/login/lostpass", name="_admin_lost_password")
     * @Template("ICupPublicSiteBundle:Edit:lost_pass.html.twig")
     */
    public function lostPasswordAction(Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        $form = $this->makeLoginForm($request);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_admin_login'));
        }
        if ($this->checkForm($form)) {
            $formData = $form->getData();
            $username = $formData['username'];
            if ($this->get('logic')->isUserKnown($username)) {
                $user = $this->get('logic')->getUserByName($username);
                $this->sendMail($user);
            }
            $request->getSession()->getFlashBag()->add(
                'lostmsgsent',
                'FORM.LOSTPASS.MSGSENT'
            );
        }

        return array(
            'form'          => $form->createView()
        );
    }

    private function makeLoginForm(Request $request) {
        $formDef = $this->createFormBuilder(array('username' => $request->getSession()->get(SecurityContext::LAST_USERNAME)));
        $formDef->add('username', 'text', array('label' => 'FORM.LOSTPASS.USERNAME',
                                                'translation_domain' => 'club',
                                                'required' => false,
                                                'help' => 'FORM.LOSTPASS.HELP.USERNAME',
                                                'icon' => 'fa fa-user'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.LOSTPASS.CANCEL',
                                                'translation_domain' => 'club',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('send', 'submit', array('label' => 'FORM.LOSTPASS.SEND',
                                              'translation_domain' => 'club',
                                              'icon' => 'fa fa-sign-in'));
        return $formDef->getForm();
    }

    private function checkForm($form) {
        if ($form->isValid()) {
            $formData = $form->getData();
            $username = $formData['username'];
            if ($username == null || trim($username) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.LOSTPASS.NOUSERNAME', array(), 'club')));
                return false;
            }
            return true;
        }
        return false;
    }

    private function sendMail(User $user) {
        if ($user->getEmail() != '' && $user->getName() != '') {
            $recv[$user->getEmail()] = $user->getName();
        }
        else {
            $this->get('logger')->addError("E-mail or Name not defined for user: " . $user->getUsername() . " - id=" . $user->getId() . " - can not send reset password instructions.");
            return;
        }

        $key = $this->generateToken($user->getEmail());
        $user->setSecret($key);
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $from = array($this->container->getParameter('mailer_user') => "icup.dk support");
        $url = $this->generateUrl('_admin_direct_login', array('username' => $user->getUsername(), 'securekey' => $key), UrlGeneratorInterface::ABSOLUTE_URL);
        $mailbody = $this->renderView('ICupPublicSiteBundle:Email:login_direct.html.twig',
            array(
                'url' => $url,
                'username' => $user->getName(),
                'email' => $user->getEmail()
            ));
        $message = Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('FORM.LOSTPASS.MESSAGE.TITLE', array(), 'club'))
            ->setFrom($from)
            ->setTo($recv)
            ->setBody($mailbody, 'text/html');
        $this->get('mailer')->send($message);
    }

    /**
     * Generate token for User
     * @param $name
     * @return string Token (32 hexdigits + 4 dashes) on the following form: 55a38954-3919-b6aa-8888-69616e787063
     */
    private function generateToken($name) {
        $str = dechex(time()).'-'.
            dechex(rand(4096, 65535)).'-'.
            dechex(rand(4096, 65535)).'-'.
            dechex(rand(4096, 65535)).'-'.
            substr(bin2hex(str_shuffle(str_pad($name, 6))), 0, 12);
        return $str;
    }
}
