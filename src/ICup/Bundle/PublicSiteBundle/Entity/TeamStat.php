<?php
namespace ICup\Bundle\PublicSiteBundle\Entity;

class TeamStat {

    public $id;
    public $club;
    public $name;
    public $country;
    public $group;

    public $matches = 0;
    public $score = 0;
    public $goals = 0;
    public $diff = 0;
    public $points = 0;
    public $tiepoints = 0;
    
    public $won = 0;
    public $maxscore = 0;
    public $maxdiff = 0;
}