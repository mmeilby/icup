<?php
namespace ICup\Bundle\PublicSiteBundle\Exceptions;

/**
 * Description of ValidationException
 *
 * @author mm
 */
class ValidationException extends \RuntimeException {
    private $debugInfo;
    
    public function __construct($msg, $debugInfo = null)
    {
        parent::__construct($msg, 0, null);
        $this->debugInfo = $debugInfo;
    }
    
    public function setDebugInfo($debugInfo) {
        $this->debugInfo = $debugInfo;
    }
    
    public function getDebugInfo() {
        return $this->debugInfo;
    }
}
