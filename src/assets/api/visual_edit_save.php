<?php

use TMCms\Admin\Structure\Entity\PageComponent;
use TMCms\Routing\Entity\PagesWordEntity;
use TMCms\Templates\VisualEdit;

VisualEdit::getInstance()->init();
if (!VisualEdit::getInstance()->isEnabled()) die;

if (!isset($_POST['content'], $_POST['component'], $_POST['page_id'], $_POST['type'])) return;

$_POST['content'] = trim($_POST['content']);

if ($_POST['type'] == 'component') {
    $component = new PageComponent();
    $component
        ->setPageId($_POST['page_id'])
        ->setComponent($_POST['component'])
        ->setData($_POST['content'])
        ->findAndLoadPossibleDuplicateEntityByFields(['page_id', 'component'])
        ->save();
} elseif ($_POST['type'] == 'word') {
    $word = new PagesWordEntity();
    $word
        ->setName($_POST['component'])
        ->setWord($_POST['content'])
        ->findAndLoadPossibleDuplicateEntityByFields(['name'])
        ->save();
}


echo json_encode([
    'status' => true,
], JSON_OBJECT_AS_ARRAY);