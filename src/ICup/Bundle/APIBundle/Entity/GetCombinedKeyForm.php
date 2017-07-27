<?php

namespace APIBundle\Entity;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

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
     * @param mixed $ident
     * @return GetCombinedKeyForm
     */
    public function setEntity($entity) {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @param Form $form
     * @return bool
     */
    public function checkForm(Form $form) {
        if ($form->isValid()) {
            if ($this->getKey() == null || trim($this->getKey()) == '') {
                $form->addError(new FormError("Key is not valid"));
            }
            if ($this->getEntity() == null || trim($this->getEntity()) == '') {
                $form->addError(new FormError("Entity is not valid"));
            }
        }
        return $form->isValid();
    }

}