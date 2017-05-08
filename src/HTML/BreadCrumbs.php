<?php

namespace TMCms\HTML;

use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class BreadCrumbs
 */
class BreadCrumbs
{
    use singletonInstanceTrait;

    /**
     * @var array
     */
    private $links = [];
    private $actions = [];
    private $notes = [];
    private $alerts = [];
    private $pills = [];
    private $first_action_button = false;

    /**
     * @param string $name
     * @param bool   $href
     * @param bool   $target
     *
     * @return $this
     */
    public function addCrumb($name, $href = false, $target = false)
    {
        $this->links[] = new BreadCrumbItem($name, $href, $target);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $so = count($this->links);

        ob_start();
        ?>
        <ul class="page-breadcrumb breadcrumb">
            <?php if ($this->actions): ?>
                <li class="btn-group">
                    <?php if (1 === count($this->actions)): ?>
                        <a class="btn blue" href="<?= array_values($this->actions)[0] ?>"><?= array_keys($this->actions)[0] ?></a>
                    <?php elseif ($this->first_action_button): ?>
                        <div class="btn-group">
                            <a href="<?= array_values($this->actions)[0] ?>" class="btn blue"><?= array_keys($this->actions)[0] ?></a>
                            <button type="button" class="btn blue dropdown-toggle" data-toggle="dropdown"><i class="fa fa-angle-down"></i></button>
                            <ul class="dropdown-menu" role="menu">
                                <?php
                                $i = 0;
                                foreach ($this->actions as $title => $link):
                                    if ($i++ == 0) continue; ?>
                                    <li>
                                        <a href="<?= $link ?>"><?= $title ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn blue dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">
                            <span>Actions</span><i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <?php foreach ($this->actions as $title => $link): ?>
                                <li>
                                    <a href="<?= $link ?>"><?= $title ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php foreach ($this->links as $k => $link): ?>
                <li>
                    <?php if (!$k): ?>
                        <i class="fa fa-home"></i>
                    <?php endif; ?>
                    <?= $link ?>
                    <?php if ($so > ($k + 1)): ?>
                        <i class="fa fa-angle-right"></i>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($this->pills): ?>
        <ul class="nav nav-pills">
            <?php foreach ($this->pills as $text => $content): ?>
                <li class="<?= $content['active'] ? 'active' : '' ?>">
                    <a href="<?= $content['href'] ?>">
                        <?= $text ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif; ?>

        <?php if ($this->notes): ?>
        <div class="note note-success">
            <?php foreach ($this->notes as $text): ?>
                <p><?= $text ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

        <?php if ($this->alerts): ?>
        <div class="portlet blue-hoki box">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-cogs"></i>
                    Alerts
                </div>
                <div class="tools">
                    <a href="javascript:" class="collapse"></a>
                    <a href="javascript:" class="remove"></a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="full-height-content-body">
                    <?php foreach ($this->alerts as $text): ?>
                        <p><?= $text ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

        <?php

        return ob_get_clean();
    }

    /**
     * @param bool|true $flag
     *
     * @return $this
     */
    public function setFirstActonButton($flag = true)
    {
        $this->first_action_button = $flag;

        return $this;
    }

    /**
     * @param string $title
     * @param string $link
     *
     * @return $this
     */
    public function addAction($title, $link)
    {
        $this->actions[$title] = $link;

        return $this;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function addNotes($text)
    {
        $this->notes[] = $text;

        return $this;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function addAlerts($text)
    {
        $this->alerts[] = $text;

        return $this;
    }

    /**
     * @param string $text
     * @param string $href
     * @param bool   $active
     *
     * @return $this
     */
    public function addPills($text, $href = '', $active = false)
    {
        $this->pills[$text] = ['href' => $href, 'active' => $active];

        return $this;
    }
}