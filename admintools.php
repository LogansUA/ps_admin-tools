<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Admin tools
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class AdminTools extends Module
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
        $this->name      = 'admintools';
        $this->version   = '1.1';
        $this->author    = 'Oleg Kachinsky';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Admin tools');
        $this->description = $this->l('Module for PrestaShop CMS which provides tools such as CLI and Migrations');
    }

    /**
     * Install
     *
     * @return bool
     */
    public function install()
    {
        if (
            !parent::install()
            || !$this->registerHook('generateMigrations')
            || !$this->registerHook('executeMigrations')
            || !$this->registerHook('showUnusedMigrations')
            || !$this->createTab()
            || !$this->databaseUpdate('install')
            || !@symlink(__DIR__ . '/console.php', _PS_ROOT_DIR_ . '/console')
        ) {
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
        if (
            !parent::uninstall()
            || !$this->registerHook('generateMigrations')
            || !$this->registerHook('executeMigrations')
            || !$this->registerHook('showUnusedMigrations')
            || !$this->removeTab()
            || !$this->databaseUpdate('uninstall')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Create tab
     *
     * @return bool
     */
    public function createTab()
    {
        $tab = new Tab();

        $tab->active = 1;
        $languages   = Language::getLanguages(false);
        if (is_array($languages)) {
            foreach ($languages as $language) {
                $tab->name[$language['id_lang']] = 'admintools';
            }
        }
        $tab->class_name = 'Crud';
        $tab->module     = $this->name;
        $tab->id_parent  = -1;

        return (bool) $tab->add();
    }

    /**
     * @return bool
     */
    private function removeTab()
    {
        $tabId = (int) Tab::getIdFromClassName('Crud');
        if ($tabId) {
            $tab = new Tab($tabId);
            $tab->delete();
        }

        return true;
    }

    /**
     * Hook generate migrations
     */
    public function hookGenerateMigrations()
    {
        $this->generateMigration();
    }

    /**
     * Hook execute migrations
     */
    public function hookExecuteMigrations()
    {
        $this->executeMigrations();
    }

    /**
     * Show unused migrations
     */
    public function hookShowUnusedMigrations()
    {
        $prefix = dirname(__FILE__) . '/versions/';

        $files = scandir($prefix, 1);

        $unusedVersions = $this->findUnusedVersions($prefix, $files);

        foreach ($unusedVersions as $version) {
            echo $version['basename'] . PHP_EOL;
        }
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('rollbar_submit')) {
            if (Tools::getValue('access_token')) {
                Configuration::updateValue('PS_ROLLBAR_ACCESS_TOKEN', Tools::getValue('access_token'));
                Configuration::updateValue('PS_ROLLBAR_ENABLER_VALUE', Tools::getValue('rollbar_enabler'));
            }
        }

        if (Tools::isSubmit('generate')) {
            Hook::exec('generateMigrations');
        }

        if (Tools::isSubmit('migrate')) {
            Hook::exec('executeMigrations');
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
        $html = $this->rollbarForm() . $this->migrationsForm();

        return $html;
    }

    /**
     * @return mixed
     */
    private function rollbarForm()
    {
        $fieldsForm = array(
            'general' => array(
                'title'  => $this->l('Rollbar'),
                'fields' => array(
                    'rollbar_enabler' => array(
                        'title' => $this->l('Choose one'),
                        'desc'  => $this->l('Choose between Yes and No.'),
                        'cast'  => 'boolval',
                        'type'  => 'bool'
                    ),
                    'access_token'    => array(
                        'title'        => $this->l('Access Token'),
                        'cast'         => 'strval',
                        'type'         => 'text',
                        'defaultValue' => Configuration::get('PS_ROLLBAR_ACCESS_TOKEN')
                    ),

                ),
                'submit' => array(
                    'title' => 'Submit',
                    'class' => 'button',
                    'name'  => 'rollbar_submit'
                ),
            )
        );

        $helperOptions = new HelperOptions($this);

        $helperOptions->id              = $this->id;
        $helperOptions->module          = $this;
        $helperOptions->name_controller = $this->name;
        $helperOptions->token           = Tools::getAdminTokenLite('AdminModules');
        $helperOptions->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;

        $helperOptions->required = true;

        return $helperOptions->generateOptions($fieldsForm);
    }

    /**
     * @return mixed
     */
    private function migrationsForm()
    {
        $fieldsForm = array(
            'form' => array(
                'legend'  => array(
                    'title' => 'Migrations service'
                ),
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

        $helperForm = new HelperForm();

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helperForm->show_toolbar = false;
        $helperForm->table        = $this->table;

        $helperForm->default_form_language    = $lang->id;
        $helperForm->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;

        $helperForm->identifier    = $this->identifier;
        $helperForm->submit_action = 'submitModule';

        $helperForm->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

        $helperForm->token = Tools::getAdminTokenLite('AdminModules');

        $helperForm->fields_value['access_token'] = Configuration::get('PS_ROLLBAR_ACCESS_TOKEN');

        return $helperForm->generateForm(array($fieldsForm));
    }

    /**
     * Update database
     *
     * @param string $action Action
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
                        ENGINE = InnoDB DEFAULT CHARSET = utf8;
                    ';
                break;
            case 'uninstall':
                $sql = 'DROP TABLE IF EXISTS prestashop.migration_versions;';
                break;
            default:
                $this->context->controller->errors[] = 'Wrong action in admintools.php: ' . $action;
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
     *
     * @return bool
     */
    private function executeMigrations()
    {
        $prefix = dirname(__FILE__) . '/versions/';

        $files = scandir($prefix, 1);

        $unusedVersions = $this->findUnusedVersions($prefix, $files);

        foreach ($unusedVersions as $version) {
            $filename = $prefix . $version['basename'];

            $stream = fopen($filename, 'r');
            $data   = explode("\n", file_get_contents(str_replace("\r", '', $filename)));

            foreach ($data as $query) {
                try {
                    Db::getInstance()->execute($query);
                } catch (PrestaShopDatabaseException $e) {
                    $this->context->controller->errors[] = $e->getMessage();

                    return false;
                }
            }

            fclose($stream);

            $fileVersion = $version['filename'];

            $insertQuery
                = "INSERT INTO
                        `migration_versions`
                    VALUES
                        ('" . pSQL($fileVersion) . "')
                    ";

            Db::getInstance()->execute($insertQuery);
        }

        return true;
    }

    /**
     * Find unused versions
     *
     * @param string $prefix Prefix
     * @param array  $files  Files
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     */
    private function findUnusedVersions($prefix, $files)
    {
        $sql    = "SELECT version FROM `migration_versions`";
        $result = Db::getInstance()->executeS($sql);

        $unusedVersions = array();
        foreach ($files as $file) {
            $filename = $prefix . $file;
            $fileInfo = pathinfo($filename);

            if ($fileInfo['extension'] == 'sql') {
                $isEqual = false;

                foreach ($result as $version) {
                    if ($fileInfo['filename'] == $version['version']) {
                        $isEqual = true;
                        break;
                    }
                }

                if (!$isEqual) {
                    array_push($unusedVersions, $fileInfo);
                }
            }
        }

        return $unusedVersions;
    }
}
