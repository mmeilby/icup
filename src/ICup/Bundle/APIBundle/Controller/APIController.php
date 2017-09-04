<?php

namespace APIBundle\Controller;

use APIBundle\Entity\Error;
use APIBundle\Entity\GetCombinedKeyForm;
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
    /* @var $host Host */
    public $host;
    /* @var $user User */
    public $user;

    /**
     * @param Request $request
     * @return GetCombinedKeyForm
     */
    public function getKeyForm(Request $request) {
        $keyForm = new GetCombinedKeyForm();
        if ("json" == $request->getContentType()) {
            $content = $request->getContent();
            $params = json_decode($content, true);
            if (isset($params["entity"])) {
                $keyForm->setEntity($params["entity"]);
            }
            if (isset($params["key"])) {
                $keyForm->setKey($params["key"]);
            }
            if (isset($params["param"])) {
                $keyForm->setParam($params["param"]);
            }
        }
        return $keyForm;
    }

    public function executeAPImethod(Request $request, $api_function) {
        try {
            $this->user = $this->get('logic')->getUserByEmail($request->getUser());
            if ($this->user == null) {
                throw new ValidationException("EMAILNVLD", "No user found with this e-mail address.");
            }
            if (!$this->user->isAccountNonExpired()) {
                throw new ValidationException("USREXP", "User account has expired.");
            }
            if (!$this->user->isAccountNonLocked() || $this->user->getAttempts() > 2) {
                throw new ValidationException("USRLCK", "User account is locked.");
            }
            if (!$this->user->isEnabled()) {
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
            $this->host = $this->get('logic')->getHostByAPIKey($request->getPassword());
            if ($this->host == null) {
                // each attempt to login using a bad apikey counts as an failed attempt
                $this->user->setAttempts($this->user->getAttempts()+1);
                $this->getDoctrine()->getManager()->flush();
                throw new ValidationException("APIKNVLD", "APIkey is not valid for any host.");
            }
            // reset login attempts after successful login
            $this->user->setAttempts(0);
            $this->user->setLastLogin(new DateTime());
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
                $json_response = $api_function($this);
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

    public function validateEditor() {
        if (!($this->user->isAdmin() || $this->user->isEditor() && $this->user->getHost()->getId() == $this->host->getId())) {
            throw new ValidationException("ROLEEDI", "User is not approved for this API key level - editor role is required.");
        }
    }

    public function validateAdmin() {
        if (!$this->user->isAdmin()) {
            throw new ValidationException("ROLEADM", "User is not approved for this API key level - admin role is required.");
        }
    }
}