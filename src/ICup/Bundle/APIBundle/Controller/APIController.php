<?php

namespace APIBundle\Controller;

use APIBundle\Entity\Error;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use Exception;

/**
 * Created by PhpStorm.
 * User: mm
 * Date: 17/07/2017
 * Time: 14.52
 */
class APIController extends Controller
{
    public function executeAPImethod(Request $request, $api_function) {
        try {
            /* @var $user User */
            $user = $this->get('logic')->getUserByEmail($request->getUser());
            if ($user == null) {
                throw new ValidationException("EMAILNVLD", "No user found with this e-mail address.");
            }
            if (!$user->isAccountNonExpired()) {
                throw new ValidationException("USREXP", "User account has expired.");
            }
            if (!$user->isAccountNonLocked() || $user->getAttempts() > 2) {
                throw new ValidationException("USRLCK", "User account is locked.");
            }
            if (!$user->isEnabled()) {
                throw new ValidationException("USRDIS", "User account has not been enabled.");
            }
        }
        catch (ValidationException $e) {
            return $this->makeErrorObject($e->getMessage(), $e->getDebugInfo(), Response::HTTP_FORBIDDEN);
        }
        catch (Exception $e) {
            return $this->makeErrorObject("INTERNALERROR", $e->getMessage());
        }
        try {
            /* @var $host Host */
            $host = $this->get('logic')->getHostByAPIKey($request->getPassword());
            if ($host == null) {
                // each attempt to login using a bad apikey counts as an failed attempt
                $user->setAttempts($user->getAttempts()+1);
                $this->getDoctrine()->getManager()->flush();
                throw new ValidationException("APIKNVLD", "APIkey is not valid for any host.");
            }
            // reset login attempts after successful login
            $user->setAttempts(0);
            $user->setLastLogin(new DateTime());
            $this->getDoctrine()->getManager()->flush();
        }
        catch (ValidationException $e) {
            return $this->makeErrorObject($e->getMessage(), $e->getDebugInfo(), Response::HTTP_FORBIDDEN);
        }
        catch (Exception $e) {
            return $this->makeErrorObject("INTERNALERROR", $e->getMessage());
        }
        try {
            if (is_callable($api_function)) {
                $json_response = $api_function($user, $host);
                $this->getDoctrine()->getManager()->flush();
                return $json_response;
            }
            else {
                return $this->makeErrorObject("INTERNALERROR", "API method is not implemented or deprecated for this version.");
            }
        }
        catch (ValidationException $e) {
            return $this->makeErrorObject($e->getMessage(), $e->getDebugInfo());
        }
        catch (Exception $e) {
            return $this->makeErrorObject("INTERNALERROR", $e->getMessage());
        }
    }

    public function makeErrorObject($id, $message, $response = Response::HTTP_INTERNAL_SERVER_ERROR) {
        $error = new Error();
        $error->setErrorID($id);
        $error->setErrorMessage($message);
        return new JsonResponse($error, $response);
    }

    public function validateEditor(User $user, Host $host) {
        if (!($user->isAdmin() || $user->isEditor() && $user->getHost()->getId() == $host->getId())) {
            throw new ValidationException("ROLEEDI", "User is not approved for this API key level - editor role is required.");
        }
    }

    public function validateAdmin(User $user) {
        if (!$user->isAdmin()) {
            throw new ValidationException("ROLEADM", "User is not approved for this API key level - admin role is required.");
        }
    }
}