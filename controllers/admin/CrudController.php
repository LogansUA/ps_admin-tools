<?php

/**
 * Class crud Controller
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 * @author Andrew Prohorovych <prohorovychua@gmail.com>
 */
class CrudController extends AdminController
{
    const COMMAND_TABLE = 'admintools_command';

    /**
     * @var bool $bootstrap
     */
    public $bootstrap = false;

    /**
     * @var array $params
     */
    protected $params = array();

    /**
     * @var string $command
     */
    private $command;

    /**
     * @var string $firstAttribute
     */
    private $firstAttribute;

    /**
     * @var string $secondAttribute
     */
    private $secondAttribute;

    /**
     * @var string $thirdAttribute
     */
    private $thirdAttribute;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->bootstrap = false;
        $this->context   = Context::getContext();
        $this->className = 'Crud';
        $this->lang      = true;

        parent::__construct();

        $arguments = Tools::getValue('cli_argv', false);
        $this->setVariables($arguments);
        $this->start();
    }

    /**
     * Start
     *
     * @param string $command Command
     */
    private function start($command = '')
    {
        $command = $command ? $command : $this->command;

        switch ($command) {
            case 'command':
                $this->commandAction();
                break;
            case 'hook':
                $this->hookAction();
                break;
            case 'cache':
                $this->deleteCache('cache/');

                echo "Delete was success! All cache was clean." . PHP_EOL;
                break;
            case 'domain':
                $this->changeDomain($this->firstAttribute);
                break;
            case 'migration':
                $this->migraionAction();
                break;
            default:
                if ($this->checkCommand($command)) {
                    $this->runCommand();
                } else {
                    $this->showInfo();
                }
                break;
        }
    }

    /**
     * Set variables
     *
     * @param array $arguments Arguments from cli
     */
    private function setVariables($arguments)
    {
        if (is_array($arguments)) {
            if (isset($arguments[1])) {
                $this->command = $arguments[1];

                if (isset($arguments[2])) {
                    $this->firstAttribute = $arguments[2];

                    if (isset($arguments[3])) {
                        $this->secondAttribute = $arguments[3];

                        if (isset($arguments[4])) {
                            $this->thirdAttribute = $arguments[4];
                        }
                    }
                }
            }
        }
    }

    /**
     * Command action
     *
     * @param string $action Action
     */
    private function commandAction($action = '')
    {
        $action = $action ? $action : $this->firstAttribute;
        $result = false;

        switch ($action) {
            case 'create':
                Hook::exec('createCommandAdminTools', array(
                    'command' => $this->secondAttribute,
                    'hook'    => $this->thirdAttribute,
                    'result'  => &$result,
                ));

                if ($result) {
                    echo "Command $this->secondAttribute created and linked with hook $this->thirdAttribute." . PHP_EOL;
                } else {
                    echo "ERROR: Something wrong!" . PHP_EOL;
                }
                break;
            case 'delete':
                Hook::exec('deleteCommandAdminTools', array(
                    'command' => $this->secondAttribute,
                    'result'  => &$result,
                ));

                if ($result) {
                    echo "Command $this->secondAttribute successfully deleted!" . PHP_EOL;
                } else {
                    echo "ERROR: Something wrond!" . PHP_EOL;
                }
                break;
            default:
                $this->listCommand();
                break;
        }
    }

    /**
     * List command
     */
    private function listCommand()
    {
        echo "List of commands: " . PHP_EOL;

        $result = array();
        Module::hookExec('listCommandAdminTools', array(
            'result' => &$result,
        ));

        foreach ($result as $row) {
            echo $row['alias_command'] . "\t\t\t" . $row['description'] . PHP_EOL;
        }

        return $result;
    }

    /**
     * Delete cache
     *
     * @param string $path
     *
     * @return bool
     */
    private function deleteCache($path)
    {
        try {
            foreach (glob("{$path}/*") as $file) {
                if (is_dir($file)) {
                    $this->deleteCache($file);
                } else {
                    unlink($file);
                }
            }
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }
    }

    /**
     * Migration action
     *
     * @param string $action Action
     */
    private function migraionAction($action = '')
    {
        $action = $action ? $action : $this->firstAttribute;

        switch ($action) {
            case 'create':
                Hook::exec('generateMigrations');

                echo "New migration generated." . PHP_EOL;
                break;
            case 'run':
                Hook::exec('executeMigrations');

                echo "All migrations are executed." . PHP_EOL;
                break;
            default:
                echo "Unused migrations: " . PHP_EOL;

                Hook::exec('showUnusedMigrations');
                break;
        }
    }

    /**
     * Hook action
     *
     * @param string $action Action
     */
    private function hookAction($action = '')
    {
        $action = $action ? $action : $this->firstAttribute;

        switch ($action) {
            case 'add':
                $this->createNewHook($this->secondAttribute);
                break;
            case 'exec':
                Hook::exec($this->secondAttribute);
                break;
            case 'link':
                $this->linkHook($this->secondAttribute, $this->thirdAttribute);
                break;
        }
    }

    /**
     * Link hook with module
     *
     * @param string $moduleName
     * @param string $hookName
     *
     * @return int
     */
    private function linkHook($moduleName, $hookName)
    {
        if (!$moduleName || !$hookName) {
            return 'Parameters mismatch';
        }

        $sql = "INSERT IGNORE INTO " . _DB_PREFIX_ . "hook_module
                SELECT
                 _m.id_module,
                 _s.id_shop,
                 _h.id_hook,
                 0
                FROM " . _DB_PREFIX_ . "shop AS _s
                INNER JOIN " . _DB_PREFIX_ . "hook AS _h
                 ON _h.name = '" . pSQL($hookName) . "'
                INNER JOIN " . _DB_PREFIX_ . "module AS _m
                 ON _m.name = '" . pSQL($moduleName) . "'";
        Db::getInstance()->execute($sql);

        return true;
    }

    /**
     * Create new hook
     *
     * @param string $name
     */
    private function createNewHook($name)
    {
        $sql = "SELECT `title` FROM " . _DB_PREFIX_ . "hook WHERE name = '" . pSQL($name) . "'";

        $result = Db::getInstance()->executeS($sql);

        if ($result[0]['title'] == $name) {
            echo "This hook already exist.\n";

            return;
        } else {
            Db::getInstance()->insert('hook', array(
                'name'        => $name,
                'title'       => $name,
                'description' => 'This is a custom hook!',
            ));

            echo $name . ' successfully added.';
        }
    }

    /**
     * Change domain
     *
     * @param string $newDomain
     */
    private function changeDomain($newDomain)
    {
        $sql = "UPDATE `" . _DB_PREFIX_ . "shop` SET name = '" . pSQL($newDomain) . "' WHERE id_shop = '1'";
        Db::getInstance()->execute($sql);

        $sql = "UPDATE `" . _DB_PREFIX_ . "shop_url` SET domain = '" . pSQL($newDomain) . "' WHERE id_shop = '1'";
        Db::getInstance()->execute($sql);

        $sql = "UPDATE `" . _DB_PREFIX_ . "shop_url` SET domain_ssl = '" . pSQL($newDomain) . "' WHERE id_shop = '1'";
        Db::getInstance()->execute($sql);

        $sql = "UPDATE `" . _DB_PREFIX_ . "configuration` SET value = '" . pSQL($newDomain)
               . "' WHERE name IN ('PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL', 'PS_SHOP_NAME')";
        Db::getInstance()->execute($sql);

        echo 'Domain successfully changed to ' . $newDomain;
    }

    /**
     * Check command for existing
     *
     * @param string $command Command
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    private function checkCommand($command)
    {
        $sql = 'SELECT * FROM ' . self::COMMAND_TABLE . ' WHERE alias_command = "' . pSQL($command) . '"';

        $result = Db::getInstance()->executeS($sql);

        $lastCommand           = end($result);
        $this->firstAttribute  = $lastCommand['alias_command'];
        $this->secondAttribute = $lastCommand['hook_command'];

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * Run command
     */
    private function runCommand()
    {
        Hook::exec($this->secondAttribute);
    }

    /**
     * Show information
     */
    private function showInfo()
    {
        echo "|--------------------------- Commands -----------------------------|" . PHP_EOL;
        echo "cache                                 - remove cache" . PHP_EOL;
        echo "domain [domainname]                   - change site domain" . PHP_EOL;
        echo "hook [add/link/exec]                  - add/link/exec hook" . PHP_EOL;
        echo "     add  [hook name]                 - add new hook" . PHP_EOL;
        echo "     link [module name] [hook name]   - link module with hook" . PHP_EOL;
        echo "     exec [hook name]                 - execute specific hook" . PHP_EOL;
        echo "migration [create/run]                - create/run migrations" . PHP_EOL;
        echo "     create                           - create migration version" . PHP_EOL;
        echo "     run                              - run new migrations" . PHP_EOL;
    }
}
