<?php
namespace ICup\Bundle\PublicSiteBundle\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ValidationException
 *
 * @author mm
 */
class RedirectException extends RuntimeException {
    private $response;
    
    public function setResponse(Response $response) {
        $this->response = $response;
    }
    
    public function getResponse() {
        return $this->response;
    }
}
