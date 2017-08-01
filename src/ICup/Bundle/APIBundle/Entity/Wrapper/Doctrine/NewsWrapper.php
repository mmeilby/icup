<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;

class NewsWrapper extends ObjectWrapper
{
    public function getData($news) {
        if ($news instanceof News) {
            /* @var $site News */
            return array(
                "entity" => "News",
                "date" => Date::jsonDateSerialize($news->getDate()),
                "title" => $news->getTitle(),
                "context" => $news->getContext(),
                "language" => $news->getLanguage(),
                "no" => $news->getNewsno(),
                "type" => $news->getNewstype(),
                "team" => new TeamWrapper($news->getTeam()),
                "match" => new MatchWrapper($news->getMatch())
            );
        }
        return null;
    }
}