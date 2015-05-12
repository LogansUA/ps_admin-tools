<?php

/**
 * Class crud Controller
 */
class CrudController extends AdminController
{
    /**
     * @var string $сommand
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
     * Construct
     */
    public function __construct()
    {
        $arguments = Tools::getValue('cli_argv', false);

        $this->setVariables($arguments);

        switch ($this->command) {
            case 'cache':
                $endResult = $this->deleteCache('cache/');

                if ($endResult) {
                    echo "Delete was success! All cache was clean.\n";
                } else {
                    echo "Something go wrong, maybe you don`t have access for cache folder\n";
                }

                break;
            case 'addhook':
                $this->createNewHook($this->firstAttribute);
                break;
            case 'domain':
                $this->changeDomain($this->firstAttribute);
                break;
            case 'linkhook':
                $endResult = $this->linkHook($this->firstAttribute, $this->secondAttribute);

                switch ($endResult) {
                    case 1:
                        echo "No such module in data base.\n";
                        break;
                    case 2:
                        echo "No such hook in data base.\n";
                        break;
                    case 3:
                        echo "Such hook with module already axist.\n";
                        break;
                    case 4:
                        echo "You link was attached to this hook.\n";
                        break;
                }
                break;
            case 'migration':

                switch ($this->firstAttribute) {
                    case 'generate':
                        Hook::exec('generateMigrations');

                        echo "New migration generated.\n";
                        break;
                    case 'migrate':
                        Hook::exec('executeMigrations');

                        echo "All migrations are executed.\n";
                        break;
                }
                break;
            default:
                echo "---------------------- Commands ------------------------\n";
                echo "cache                                 - remove cache\n";
                echo "domain [domainname]                   - change site domain\n";
                echo "addhook [hookname]                    - add hook to site\n";
                echo "linkhook [modulename] [hookname]      - add hook to site\n";
                echo "migration [action]                    - generate/migrate migrations\n";
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
                    }
                }
            }
        }
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
        foreach (glob("{$path}/*") as $file) {
            if (is_dir($file)) {
                $this->deleteCache($file);
            } else {
                unlink($file);
            }
        }

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
            echo "This hook already axist.\n";

            return;
        } else {
            Db::getInstance()->insert('hook', array(
                'name'        => $name,
                'title'       => $name,
                'description' => 'This is a custom hook!',
            ));
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

        $sql = "UPDATE `" . _DB_PREFIX_ . "configuration` SET value = '" . pSQL($newDomain) . "' WHERE name IN ('PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL', 'PS_SHOP_NAME')";
        Db::getInstance()->execute($sql);
    }

    /**
     * Link hook with module
     *
     * @param string $module
     * @param string $hookName
     *
     * @return int
     */
    private function linkHook($module, $hookName)
    {
        $sql = "SELECT `id_module` FROM " . _DB_PREFIX_ . "module WHERE name = '" . pSQL($module) . "'";
        $mod = Db::getInstance()->executeS($sql);
        if ($mod[0]['id_module'] == "") {
            return 1;
        }

        $sql  = "SELECT `id_hook` FROM " . _DB_PREFIX_ . "hook WHERE name = '" . pSQL($hookName) . "'";
        $hook = Db::getInstance()->executeS($sql);
        if ($hook[0]['id_hook'] == "") {
            return 2;
        }

        $myMode = $mod[0]['id_module'];
        $myHook = $hook[0]['id_hook'];

        $sql        = "SELECT * FROM " . _DB_PREFIX_ . "hook_module WHERE `id_module` = '" . pSQL($myMode) . "' AND  `id_hook` = '" . $myHook . "'";
        $validation = Db::getInstance()->executeS($sql);

        if ($validation[0]['id_module'] != "" && $validation[0]['id_hook'] != "") {
            return 3;
        }

        $sql = "INSERT INTO " . _DB_PREFIX_ . "hook_module VALUES ('" . pSQL($myMode) . "', 1, '" . pSQL($myHook)  . "', 1)";
        Db::getInstance()->execute($sql);

        return 4;
    }
}
