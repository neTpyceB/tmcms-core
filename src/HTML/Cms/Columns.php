<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

class Columns
{
    private $columns = [], $width = '100%';
    private static $columns_options_def = [
        'html'   => 1,
        'align'  => 0,
        'valign' => 'middle',
        'nowrap' => 0,
        'width'  => 0,
    ];

    /**
     * @return $this
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * @param string $w
     *
     * @return $this
     */
    public function setWidth(string $w)
    {
        $this->width = $w;

        return $this;
    }

    /**
     * @param string $content
     * @param array $options
     *
     * @return $this
     */
    public function add(string $content, array $options = [])
    {
        $options = array_intersect_key($options, self::$columns_options_def) + self::$columns_options_def;
        $options['content'] = $content;

        $this->columns[] = $options;

        return $this;
    }

    /**
     * @param string $id
     * @param string $content
     * @param array $options
     *
     * @return $this
     */
    public function set(string $id, string $content, array $options = [])
    {
        $options = array_intersect_key(self::$columns_options_def, $options) + self::$columns_options_def;
        $options['content'] = $content;
        $this->columns[$id] = $options;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function get(string $id)
    {
        return isset($this->columns[$id]) ? $this->columns[$id] : false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();

        ?>
    <table class="columns-buttons"<?= $this->width ? ' width="' . $this->width . '"' : '' ?>>
        <tr>
            <?php foreach ($this->columns as &$column): ?>
                <td<?= $column['valign'] ? ' valign="' . $column['valign'] . '"' : '' ?><?= $column['align'] ? ' align="' . $column['align'] . '"' : '' ?><?= $column['width'] ? ' width="' . $column['width'] . '"' : '' ?><?= $column['nowrap'] ? ' nowrap="nowrap"' : '' ?>>
                    <?= $column['html'] ? $column['content'] : htmlspecialchars($column['content']) ?>
                </td>
            <?php endforeach; ?>
        </tr></table>
        <?php

        return ob_get_clean();
    }
}