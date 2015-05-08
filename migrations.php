<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Migrations
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class Migrations extends Module
{
    /**
     * @var boolean $_errors error
     */
    protected $_errors = false;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->name      = 'Migrations';
        $this->tab       = 'migrations';
        $this->version   = '1.0';
        $this->author    = 'Oleg Kachinsky';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Migrations');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        return true;
    }

    /**
     * Un install
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }
}
