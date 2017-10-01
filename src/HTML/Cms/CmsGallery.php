<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use TMCms\Config\Configuration;

defined('INC') or exit;

class CmsGallery
{
    private $image_width = 270;
    private $image_height = 200;

    private $link_active = '_images_active';
    private $link_order = '_images_order';
    private $link_delete = '_images_delete';

    private $data;
    private $data_count;

    /**
     * @param array $source_data
     */
    public function __construct(array $source_data)
    {
        $this->data = $source_data;
        $this->data_count = count($this->data);
    }

    /**
     * @param array $source_data
     *
     * @return $this
     */
    public static function getInstance(array $source_data)
    {
        return new self($source_data);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        $i = 0;

        ?>
        <div>
            <?php foreach ($this->data as $image) : ?>
                <?= $this->getImageView($image, $i++) ?>
            <?php endforeach; ?>
        </div>
        <div style="clear:left"></div>
        <script>
            function sendActive(id, url) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'gallery_active_ajax_id=' + id;
                jQuery.get(url, sendActiveComplete);

                return false;
            }

            function sendActiveComplete(data) {
                if (data === '1') {
                    ajax_toasters.request_new_messages();
                    return;
                }

                var o = document.getElementById(data);
                if (o) {
                    o.checked = !o.checked;
                }
            }
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * @param $data
     * @param $i
     *
     * @return string
     */
    private function getImageView($data, $i)
    {
        $count = $this->data_count - 1;
        $hash = md5($data['image']);

        ob_start();

        ?>
    <table style="float:left;text-align:center;margin:0 20px 20px 0;border:1px solid #ccc; font-size: 18px;">
        <tr>
            <td style="vertical-align:top">
                <div style="position:relative;height:100%">
                    <a href="<?= $data['image'] . '" target="_blank' ?>">
                        <img src="<?= $data['image'] . '&resizefit=' . $this->image_width . 'x' . $this->image_height . '&key=' . Configuration::getInstance()->get('cms')['unique_key'] ?>">
                        </a>
                    <div style="position:absolute;bottom:-15px;right:10px;padding:0 5px;background:#fff;">
                        <label>
                            <div style="float:right"><?= __('Active') ?></div>
                            <input
                                type="checkbox"
                                id="gallery_active_<?= $hash ?>"
                                name="active"
                                value="1"<?= $data['active'] ? ' checked="checked"' : '' ?>
                                onchange="sendActive('gallery_active_<?= $hash ?>','?p=<?= P ?>&do=<?= $this->link_active ?>&id=<?= $data['id'] ?>')"
                            >
                        </label>
                    </div>
                    <table style="position:absolute;bottom:-14px;left:10px;width:80px;padding:0 5px;background:#fff;">
                        <tr>
                            <td><?= ($i ? '<a href="?p=' . P . '&do=' . $this->link_order . '&id=' . $data['id'] . '&direct=up"><</a>' : '') ?></td>
                            <td align="center" width="99%">
                                <a
                                   href="?p=<?= P ?>&do=<?= $this->link_delete ?>&id=<?= $data['id'] ?>"
                                   onclick="return confirm('<?= __('Are you sure?') ?>')"
                                >x</a>
                            </td>
                            <td><?= ($count !== $i ? '<a href="?p=' . P . '&do=' . $this->link_order . '&id=' . $data['id'] . '&direct=down">></a>' : '') ?></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        </table>
        <?php

        return ob_get_clean();
    }
}