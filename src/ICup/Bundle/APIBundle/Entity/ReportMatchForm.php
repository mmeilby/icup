<?php

namespace APIBundle\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\ResultForm;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by PhpStorm.
 * User: mm
 * Date: 17/05/2017
 * Time: 22.59
 */
class ReportMatchForm
{
    protected $key;
    protected $entity;
    protected $home_score;
    protected $away_score;
    protected $event;

    /* Event type - match is played */
    public static $EVENT_MATCH_PLAYED = "MP";
    /* Event type - home team was disqualified - did not show up/used illegal players */
    public static $EVENT_HOME_DISQ = "HD";
    /* Event type - away team was disqualified - did not show up/used illegal players */
    public static $EVENT_AWAY_DISQ = "AD";
    /* Event type - the match was not played */
    public static $EVENT_NOT_PLAYED = "NP";

    /**
     * @return mixed
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param mixed $key
     * @return ReportMatchForm
     */
    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * @param $entity
     * @return ReportMatchForm
     */
    public function setEntity($entity) {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHomeScore() {
        return $this->home_score;
    }

    /**
     * @param mixed $home_score
     * @return ReportMatchForm
     */
    public function setHomeScore($home_score) {
        $this->home_score = $home_score;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAwayScore() {
        return $this->away_score;
    }

    /**
     * @param mixed $away_score
     * @return ReportMatchForm
     */
    public function setAwayScore($away_score) {
        $this->away_score = $away_score;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEvent() {
        return $this->event;
    }

    /**
     * @param mixed $event
     * @return ReportMatchForm
     */
    public function setEvent($event) {
        $this->event = $event;
        return $this;
    }

    /**
     * GetCombinedKeyForm constructor.
     */
    public function getJsonParams(Request $request) {
        if ("json" == $request->getContentType()) {
            $content = $request->getContent();
            $params = json_decode($content, true);
            if (isset($params["entity"])) {
                $this->setEntity($params["entity"]);
            }
            if (isset($params["key"])) {
                $this->setKey($params["key"]);
            }
            if (isset($params["home_score"])) {
                $this->setHomeScore($params["home_score"]);
            }
            if (isset($params["away_score"])) {
                $this->setAwayScore($params["away_score"]);
            }
            if (isset($params["event"])) {
                $this->setEvent($params["event"]);
            }
        }
    }

    /**
     * @param Form $form
     * @return bool
     */
    public function checkForm(Form $form) {
        if ($this->getEntity() === null || trim($this->getEntity()) == "") {
            $form->addError(new FormError("KEYMISS"));  // Entity is not valid
        }
        if ($this->getKey() === null || trim($this->getKey()) == "") {
            $form->addError(new FormError("KEYINV"));   // Entity key is not valid
        }
        switch (strtoupper($this->getEvent())) {
            case ReportMatchForm::$EVENT_MATCH_PLAYED:
                if ($this->getHomeScore() === null || trim($this->getHomeScore()) == "") {
                    $form->addError(new FormError("NO_HOME_SCORE"));    // The home score is required
                }
                if ($this->getAwayScore() === null || trim($this->getAwayScore()) == "") {
                    $form->addError(new FormError("NO_AWAY_SCORE"));    // The away score is required
                }
                $this->setEvent(ReportMatchForm::$EVENT_MATCH_PLAYED);
                break;
            case ReportMatchForm::$EVENT_HOME_DISQ:
                $this->setHomeScore("");
                $this->setAwayScore("");
                $this->setEvent(ReportMatchForm::$EVENT_HOME_DISQ);
                break;
            case ReportMatchForm::$EVENT_AWAY_DISQ:
                $this->setHomeScore("");
                $this->setAwayScore("");
                $this->setEvent(ReportMatchForm::$EVENT_AWAY_DISQ);
                break;
            case ReportMatchForm::$EVENT_NOT_PLAYED:
                $this->setHomeScore("");
                $this->setAwayScore("");
                $this->setEvent(ReportMatchForm::$EVENT_NOT_PLAYED);
                break;
            default:
                $form->addError(new FormError("INVALID_EVENT"));    // The event code is not valid
        }
        if (0 < $form->getErrors()->count()) {
            return false;
        }
        /*
         * Check for valid contents
         */
        if ($this->getEvent() == ReportMatchForm::$EVENT_MATCH_PLAYED) {
            if ($this->getHomeScore() > 100 || $this->getHomeScore() < 0) {
                $form->addError(new FormError("INVALID_HOME_SCORE"));   // The home score is not valid
            }
            if ($this->getAwayScore() > 100 || $this->getAwayScore() < 0) {
                $form->addError(new FormError("INVALID_AWAY_SCORE"));   // The away score is not valid
            }
        }

        return 0 === $form->getErrors()->count();
    }
}