<?php

use TMCms\Templates\VisualEdit;

VisualEdit::getInstance()->init();
if (!VisualEdit::getInstance()->isEnabled()) return;

if (!isset($_POST['content'], $_POST['component'], $_POST['page_id'])) return;

q('UPDATE `cms_pages_components`
SET `data` = "'. sql_prepare($_POST['content']) .'"
WHERE `page_id` = "'. (int)$_POST['page_id'] .'"
AND `component` = "'. sql_prepare($_POST['component']) .'"
');

echo json_encode(
    ['status' => true]
);