<?php

namespace neTpyceB\TMCms\Modules;

use neTpyceB\TMCms\Orm\Entity;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;

class CmsModule implements IModule {
    use singletonInstanceTrait;

    protected $entity_name = ''; // This must be overwritten in extended module

    public function _default() {
        // TODO generate table using all available fields
    }

    protected function __add_edit_form(Entity $data = NULL) {

    }

    public function add() {

    }

    public function _add() {

    }

    public function edit() {

    }

    public function _edit() {

    }

    public function _active() {

    }

    public function _order() {

    }

    public function _delete() {

    }
}