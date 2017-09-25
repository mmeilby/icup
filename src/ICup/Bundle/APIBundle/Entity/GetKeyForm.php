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
class GetKeyForm
{
    protected $key;

    /**
     * @return mixed
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param mixed $key
     * @return GetKeyForm
     */
    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * GetCombinedKeyForm constructor.
     */
    public function getJsonParams(Request $request) {
        if ("json" == $request->getContentType()) {
            $content = $request->getContent();
            $params = json_decode($content, true);
            if (isset($params["key"])) {
                $this->setKey($params["key"]);
            }
        }
    }

    public function checkForm(Form $form) {
        if ($form->isValid()) {
            if ($this->getKey() == null || trim($this->getKey()) == '') {
                $form->addError(new FormError("Key is not valid"));
            }
        }
        return $form->isValid();
    }

}