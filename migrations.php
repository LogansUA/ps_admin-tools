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
        $this->name      = 'migrations';
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
     * @return boolean
     */
    public function install()
    {
        if (!parent::install() || !$this->databaseUpdate('install')) {
            return false;
        }

        return true;
    }

    /**
     * Un install
     *
     * @return boolean
     */
    public function uninstall()
    {
        if (!parent::uninstall() || !$this->databaseUpdate('uninstall')) {
            return false;
        }

        return true;
    }

    /**
     * Get content
     *
     * @return mixed
     */
    public function getContent()
    {
        if (Tools::isSubmit('generate')) {
            $this->generateMigration();
        }

        if (Tools::isSubmit('migrate')) {
            $this->executeMigrations();
        }

        return $this->renderForm();
    }

    /**
     * Render custom form
     *
     * @return string
     */
    private function renderForm()
    {
        $fieldsForm = array(
            'form' => array(
                'buttons' => array(
                    'generate' => array(
                        'title' => $this->l('Generate migration'),
                        'type'  => 'submit',
                        'name'  => 'generate',
                        'class' => 'btn btn-default pull-right',
                        'icon'  => 'process-icon-save'
                    ),
                    'migrate'  => array(
                        'title' => $this->l('Migrate'),
                        'type'  => 'submit',
                        'name'  => 'migrate',
                        'class' => 'btn btn-default pull-right',
                        'icon'  => 'process-icon-refresh'
                    )
                ),
            ),
        );

        $lang   = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table        = $this->table;

        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;

        $helper->identifier    = $this->identifier;
        $helper->submit_action = 'submitModule';

        $helper->currentIndex = $this
            ->context
            ->link
            ->getAdminLink('AdminModules', false) .
                '&configure=' . $this->name .
                '&tab_module=' . $this->tab .
                '&module_name=' . $this->name;

        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm(array(
            $fieldsForm
        ));
    }

    /**
     * Update database
     *
     * @param string $action
     *
     * @return bool
     */
    private function databaseUpdate($action = null)
    {
        $sql = '';

        switch ($action) {
            case 'install':
                $sql
                    = 'CREATE TABLE IF NOT EXISTS `migration_versions`
                            (
                                `version` VARCHAR(255) NOT NULL
                            )
                        ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ';
                break;
            case 'uninstall':
                $sql = 'DROP TABLE IF EXISTS prestashop.migration_versions;';
                break;
            default:
                $this->context->controller->errors[] = 'Wrong action migrations.php';
                break;
        }

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;
    }

    /**
     * Generate migration file
     */
    private function generateMigration()
    {
        $dateTime  = new DateTime();
        $timestamp = $dateTime->format('dmYHis');

        $filename = dirname(__FILE__) . '/versions/' . $timestamp . '.sql';
        $stream   = fopen($filename, 'w');

        fclose($stream);
    }

    /**
     * Execute migrations
     */
    private function executeMigrations()
    {
        $prefix = dirname(__FILE__) . '/versions/';

        $files = scandir($prefix, 1);

        foreach ($files as $file) {
            $filename = $prefix . $file;

            $fileInfo = pathinfo($filename);

            if ($fileInfo['extension'] == 'sql') {
                $stream = fopen($filename, 'r');

                $data = file_get_contents($filename);

                try {
                    Db::getInstance()->execute($data);
                } catch (PrestaShopDatabaseException $e) {
                    $this->context->controller->errors[] = $e->getMessage();
                }

                fclose($stream);
            }
        }
    }
}
