<?php

namespace TMCms\Modules;

use TMCms\Admin\Messages;
use TMCms\DB\SQL;
use TMCms\HTML\BreadCrumbs;
use TMCms\HTML\Cms\CmsFormHelper;
use TMCms\HTML\Cms\CmsTableHelper;
use TMCms\Log\App;
use TMCms\Orm\Entity;
use TMCms\Orm\EntityRepository;
use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

class CmsModuleHelper implements IModule {
    use singletonInstanceTrait;

    public static function renderDefaultTable(EntityRepository $entity_repository, $action_links = []) {
        if ($action_links) {
            $bc = BreadCrumbs::getInstance();
            foreach ($action_links as $action_name => $action_do) {
                $bc->addAction(__($action_name), '?p='. P .'&do='. $action_do);
            }
        }
        return CmsTableHelper::outputTable([
            'data' => $entity_repository,
            'combine' => true,
            'columns' => [
                'order' => [
                    'type' => 'order',
                    'order_drag' => 1
                ],
            ],
            'active' => 1,
            'delete' => 1,
            'edit' => 1,
            'view' => 1,
        ]);
    }

    private static function __add_edit_form(Entity $entity) {
        $fields = SQL::getFields($entity->getDbTableName());
        unset($fields['id'], $fields['order'], $fields['active']);

        $form_params = [
            'data' => $entity,
            'action' => '?p=' . P . '&do=_add',
            'combine' => 'true',
            'button' => __('Add')
        ];

        return CmsFormHelper::outputForm($entity->getDbTableName(), $form_params);
    }

    public static function renderAddForm(Entity $entity) {
        BreadCrumbs::getInstance()
            ->addCrumb(__('Add'))
        ;

        echo self::__add_edit_form($entity);
    }

    public static function renderAddAction(Entity $entity) {
        $entity->loadDataFromArray($_POST);
        $entity->save();

        App::add('New entity '. Converter::classWithNamespaceToUnqualifiedShort($entity) .' created');
        Messages::sendGreenAlert('New entry created');

        go('?p='. P .'&highlight='. $entity->getId());
    }

    public static function renderEditForm(Entity $entity) {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity->setId($id, true);

        BreadCrumbs::getInstance()
            ->addCrumb(__('Edit'))
        ;

        echo self::__add_edit_form($entity)
            ->setAction('?p=' . P . '&do=_' . P_DO . '&id=' . $id)
            ->setButtonSubmit(__('Update'));
    }

    public static function renderEditAction(Entity $entity) {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity->setId($id, true);

        $entity->loadDataFromArray($_POST);
        $entity->save();

        App::add('Entity '. Converter::classWithNamespaceToUnqualifiedShort($entity) .' updated');
        Messages::sendGreenAlert('Entry updated');

        go('?p='. P .'&highlight='. $entity->getId());
    }

    public static function renderActiveAction(Entity $entity) {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity->setId($id, true);

        $entity->flipBoolValue('active');
        $entity->save();

        App::add('Entity '. Converter::classWithNamespaceToUnqualifiedShort($entity) .' updated');
        Messages::sendGreenAlert('Entry updated');

        if (IS_AJAX_REQUEST) {
            die('1');
        }
        back();
    }

    public static function renderOrderAction(Entity $entity) {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity->setId($id, true);

        App::add('Entity '. Converter::classWithNamespaceToUnqualifiedShort($entity) .' reordered');
        Messages::sendGreenAlert('Entries reordered');

        if (IS_AJAX_REQUEST) {
            SQL::orderMoveByStep($entity->getId(), $entity->getDbTableName(), $_GET['direct'], $_GET['step']);
            die(1);
        }

        SQL::order($entity->getId(), $entity->getDbTableName(), $_GET['direct']);
        back();
    }

    public static function renderDeleteAction(Entity $entity) {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        $entity->setId($id, true);

        $entity->deleteObject();

        App::add('Entity '. Converter::classWithNamespaceToUnqualifiedShort($entity) .' deleted');
        Messages::sendGreenAlert('Entry deleted');

        back();
    }

    public static function renderViewTable(Entity $entity) {
        $id = abs((int)$_GET['id']);
        if (!$id) {
            return;
        }

        BreadCrumbs::getInstance()
            ->addCrumb(__('View'))
        ;

        $entity->setId($id, true);

        $columns = [];
        foreach (SQL::getTableColumns($entity->getDbTableName()) as $v) {
            $column = [
                'type' => 'html'
            ];

            // Checkboxes
            if ($v['Type'] == 'tinyint(1) unsigned') {
                $column['type'] = 'checkbox';
                $column['disabled'] = 'true';
            }

            $columns[$v['Field']] = $column;
        }

        echo CmsFormHelper::outputForm($entity->getDbTableName(), [
            'data' => $entity,
            'fields' => $columns
        ]);
    }
}