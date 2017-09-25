<?php

namespace APIBundle\Entity;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by PhpStorm.
 * User: mm
 * Date: 17/05/2017
 * Time: 22.59
 */
class GetCombinedKeyForm
{
    protected $key;
    protected $entity;
    protected $param;

    /**
     * @return mixed
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param mixed $key
     * @return GetCombinedKeyForm
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
     * @return GetCombinedKeyForm
     */
    public function setEntity($entity) {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParam() {
        return $this->param;
    }

    /**
     * @param mixed $param
     * @return GetCombinedKeyForm
     */
    public function setParam($param) {
        $this->param = $param;
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
            if (isset($params["param"])) {
                $this->setParam($params["param"]);
            }
        }
    }

    /**
     * @param Form $form
     * @return bool
     */
    public function checkForm(Form $form) {
        if ($this->getEntity() === null || trim($this->getEntity()) == "") {
            $form->addError(new FormError("Entity is not valid"));
        }
        if ($this->getKey() === null || trim($this->getKey()) == "") {
            $this->setKey("");
        }
        if ($this->getParam() === null || trim($this->getParam()) == "") {
            $this->setParam("");
        }
        return 0 === $form->getErrors()->count();
    }
}