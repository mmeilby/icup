<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Twig_Error_Loader;
use Twig_ExistsLoaderInterface;
use Twig_LoaderInterface;

/**
 * Description of DatabaseTwigLoader
 *
 * @author mm
 */
class DatabaseTwigLoader implements Twig_LoaderInterface, Twig_ExistsLoaderInterface
{
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;

    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function getSource($name)
    {
        /* @var $template Template */
        $template = $this->getTemplate($name);
        if (null == $template) {
            throw new Twig_Error_Loader(sprintf('Template "%s" does not exist.', $name));
        }

        return $template->getSource();
    }

    public function exists($name)
    {
        /* @var $template Template */
        $template = $this->getTemplate($name);
        return null != $template;
    }

    public function getCacheKey($name)
    {
        return $name;
    }

    public function isFresh($name, $time)
    {
        /* @var $template Template */
        $template = $this->getTemplate($name);
        if (null == $template) {
            return false;
        }

        return $template->getLastModified() <= $time;
    }

    /*
     * @return Template
     */
    protected function getTemplate($name)
    {
        $tags = explode(":", $name);
        if (count($tags) < 2 || $tags[0] != "@db") {
            return null;
        }
        
        $tournamentId = $tags[1];
        
        /* @var $template Template */
        $template = $this->em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Template')
                            ->findOneBy(array('pid' => $tournamentId));
        if ($template != null) {
            $this->logger->addDebug("Matched template - template=".var_export($template, true));
        }
        return $template;
    }
}