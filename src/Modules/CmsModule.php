<?php

namespace TMCms\Modules;

use TMCms\Admin\Messages;
use TMCms\DB\SQL;
use TMCms\HTML\BreadCrumbs;
use TMCms\HTML\Cms\CmsFormHelper;
use TMCms\Log\App;
use TMCms\Orm\Entity;
use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

abstract class CmsModule implements IModule {
    use singletonInstanceTrait;

    protected $entity_class_name = ''; // This must be overwritten in extended module

    public function _default() {
        // TODO generate table using all available fields
    }

    public function __add_edit_form(Entity $data = NULL) {
        $entity = $this->getRealEntityInUse();

        $fields = SQL::getFields($entity->getDbTableName());
        unset($fields['id'], $fields['order'], $fields['active']);

        $form_params = [
            'data' => $data,
            'action' => '?p=' . P . '&do=_add',
            'combine' => 'true',
            'button' => __('Add'),
            'fields' => $fields
        ];

        return CmsFormHelper::outputForm($entity->getDbTableName(), $form_params);
    }

    public function add() {
        $entity_name = $this->getRealEntityName();

        BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P))
            ->addCrumb(__('Add '. $entity_name))
        ;

        echo $this->__add_edit_form();
    }

    public function _add() {
        $entity = $this->getRealEntityInUse();
        $entity->loadDataFromArray($_POST);
        $entity->save();

        $name = $this->getRealEntityName($entity);

        App::add($name . ' added');

        Messages::sendGreenAlert($name . ' added');

        go('?p='. P .'&highlight='. $entity->getId());
    }

    public function edit() {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity = $this->getRealEntityInUse($id);
        $entity_name = $this->getRealEntityName($entity);

        BreadCrumbs::getInstance()
            ->addCrumb(ucfirst(P), '?p='. P)
            ->addCrumb(__('Edit '. $entity_name))
        ;

        echo $this->__add_edit_form($entity)
            ->setAction('?p=' . P . '&do=_edit&id=' . $id)
            ->setSubmitButton(__('Update'));
    }

    public function _edit() {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity = $this->getRealEntityInUse($id);
        $entity->loadDataFromArray($_POST);
        $entity->save();

        $name = $this->getRealEntityName($entity);

        App::add($name . ' updated');

        Messages::sendGreenAlert($name . ' updated');

        go('?p='. P .'&highlight='. $entity->getId());
    }

    public function _active() {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity = $this->getRealEntityInUse($id);
        $entity->flipBoolValue('active');
        $entity->save();

        $name = $this->getRealEntityName($entity);

        App::add($name . ' ' . ($entity->getActive() ? '' : 'de') . 'activated');

        Messages::sendGreenAlert($name . ' updated');

        if (IS_AJAX_REQUEST) {
            die('1');
        }
        back();
    }

    public function _order() {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity = $this->getRealEntityInUse($id);

        SQL::order($entity->getId(), $entity->getDbTableName(), $_GET['direct']);

        back();
    }

    public function _delete() {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity = $this->getRealEntityInUse($id);
        $name = $this->getRealEntityName($entity);

        $entity->deleteObject();

        App::add($name . ' deleted');

        Messages::sendGreenAlert($name . ' deleted');

        back();
    }

    /**
     * @param int $id
     * @return Entity
     */
    private function getRealEntityInUse($id = 0) {
        $entity_class_name = 'TMCms\Modules\\' . ucfirst($this->entity_class_name) . '\Entity\\' . $this->entity_class_name;
        return new $entity_class_name($id);
    }

    private function getRealEntityName($entity = NULL)
    {
        if (!$entity) {
            $entity = $this->getRealEntityInUse();
        }

        return Converter::classWithNamespaceToUnqualifiedShort($entity);
    }
}