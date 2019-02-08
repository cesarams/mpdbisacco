<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class MpDbIsacco extends Module
{
    protected $config_form = false;
    public $adminClassName;

    public function __construct()
    {
        $this->name = 'mpdbisacco';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Digital Solutions®';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MP Isacco Db Importer');
        $this->description = $this->l('Import Isacco products from his online json DB');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->adminClassName = "AdminMpDbIsacco";
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return
            parent::install() &&
            $this->installTab() &&
            $this->installTable() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        return
            $this->uninstallTab() &&
            parent::uninstall();
    }

    public function installTable()
    {
        $sql = array();
        $sql[] = "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."isacco_db (";
        $sql[] = "`id_isacco_db` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,";
        $sql[] = "`year` INT NOT NULL,";
        $sql[] = "`reference` VARCHAR(255) NOT NULL,";
        $sql[] = "`name` VARCHAR(255) NOT NULL,";
        $sql[] = "`color` VARCHAR(255) NULL,";
        $sql[] = "`material` VARCHAR(255) NULL,";
        $sql[] = "`size` VARCHAR(255) NULL,";
        $sql[] = "`image` VARCHAR(255) NULL,";
        $sql[] = "`thumb` VARCHAR(255) NULL,";
        $sql[] = "`wholesale_price` DECIMAL(20,6) NULL,";
        $sql[] = "`price` DECIMAL(20,6) NULL,";
        $sql[] = "`sell_price` DECIMAL(20,6) NULL,";
        $sql[] = "`new` TINYINT NULL";
        $sql[] = ") ENGINE = InnoDB;";

        return Db::getInstance()->execute(implode(" ", $sql));
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminTools');
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $id_lang = (int)$lang['id_lang'];
            $tab->name[$id_lang] = $this->l('MP Db Isacco import');
        }
        $tab->class_name = $this->adminClassName;
        $tab->module = $this->name;
        $tab->active = 1;
        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminMpDbIsacco');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    public function toggleStatus()
    {
        $id_shop = (int)Context::getContext()->shop->id;
        $id_product = (int)Tools::getValue('id_product');
        $status = (int)Tools::getValue('statusproduct');

        $product = new Product($id_product);
        return $product->toggleStatus();
    }
}
