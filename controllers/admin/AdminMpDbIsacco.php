<?php
/**
 * 2017 mpSOFT
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
 *  @author    Massimiliano Palermo <info@mpsoft.it>
 *  @copyright 2019 Digital Solutions®
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */

require_once _PS_MODULE_DIR_ . "mpdbisacco/classes/IsaccoDb.php";
require_once _PS_MODULE_DIR_ . "mpdbisacco/classes/form.php";

class AdminMpDbIsaccoController extends ModuleAdminController
{
    private $doChanges;
    private $out_of_stock;

    public function __construct()
    {
        $this->id_lang = (int)ContextCore::getContext()->language->id;
        $this->id_shop = (int)ContextCore::getContext()->shop->id;
        $this->link = new LinkCore();

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'MpDbIsacco';
        $this->token = Tools::getAdminTokenLite($this->className);
        $this->table = "isacco_db";
        
        $this->fields_list = array(
            'thumb' => array(
                'title' => $this->l('Image'),
                'align' => 'text-center',
                'width' => 64,
                'type' => 'bool',
                'float' => true,
                'search' => false,
            ),
            'id_isacco_db' => array(
                'title' => $this->l('Id'),
                'align' => 'text-right',
                'width' => 48,
                'type' => 'text',
                'float' => true,
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
                'align' => 'text-left',
                'width' => 'auto',
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'text-left',
                'width' => 'auto',
                'filter_key' => 'name',
            ),
            'color' => array(
                'title' => $this->l('Color'),
                'align' => 'text-left',
                'width' => 'auto',
                'filter_key' => 'color',
                'type' => 'text',
                'float' => true,
            ),
            'material' => array(
                'title' => $this->l('Material'),
                'align' => 'text-left',
                'width' => 'auto',
                'filter_key' => 'material',
            ),
            'size' => array(
                'title' => $this->l('Size'),
                'align' => 'text-center',
                'width' => 'auto',
                'filter_key' => 'size',
            ),
            'wholesale_price' => array(
                'title' => $this->l('W. Price'),
                'align' => 'text-right',
                'width' => 64,
                'type' => 'price',
                'filter_key' => 'wholesale_price',
                'filter_type' => 'decimal',
                'search' => true,
            ),
            'price' => array(
                'title' => $this->l('Price'),
                'align' => 'text-right',
                'width' => 64,
                'type' => 'price',
                'filter_key' => 'price',
                'filter_type' => 'decimal',
                'search' => true,
            ),
            'sell_price' => array(
                'title' => $this->l('S. Price'),
                'align' => 'text-right',
                'width' => 64,
                'type' => 'price',
                'filter_key' => 'sell_price',
                'filter_type' => 'decimal',
                'search' => true,
            ),
            'new' => array(
                'title' => $this->l('New'),
                'align' => 'text-center',
                'width' => 48,
                'type' => 'bool',
                'float' => 'true',
                'filter_key' => 'new',
            ),
            'year' => array(
                'title' => $this->l('Year'),
                'align' => 'text-center',
                'width' => 48,
                'filter_key' => 'year',
            ),
        );
        $this->addRowAction('Import');
        $this->bulk_actions = array(
            'import' => array(
                'text' => $this->l('Import Products'),
                'confirm' => $this->l('Import selected items?'),
                'icon' => 'icon-download'
            ),
        );
        parent::__construct();
    }

    public function getListManufacturers()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_manufacturer')
            ->select('UPPER(name) as manuf')
            ->from('manufacturer')
            ->orderBy('name');
        $res = $db->executeS($sql);
        $output = array();
        foreach($res as $row) {
            $output[$row['id_manufacturer']] = $row['manuf'];
        }
        return $output;
    }

    public function initToolbar()
    {
        /*
        [new] => Array
        (
            [href] => index.php?controller=AdminMpOutOfStock&addproduct&token=abe225d70a7555cfd65f980ceeddc561
            [desc] => Aggiungi nuovo
        )
        */
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
        $this->toolbar_btn = array(
            'new' => array(
                'href' => $this->link->getAdminLink("AdminMpDbIsacco")."&importIsaccoDb",
                'desc' => $this->l('Import Isacco Json Db')
            ),
            'download' => array(
                'href' => $this->link->getAdminLink("AdminMpDbIsacco")."&importPrices",
                'desc' => $this->l('Import Prices')
            )
        );
    }

    public function processSubmitPrices()
    {
        $file = Tools::fileAttachment('price_file');
        $rows = explode("\n", $file['content']);
        $csv = array();
        foreach ($rows as $row) {
            $row = explode(";", $row);
            $csv[] = $row;
        }
        $header = array_shift($csv);

        $rows = array();
        foreach ($csv as $row) {
            if (count($row) == count($header)) {
                $row = array_combine($header, $row);
                $rows[] = $row;
            }
        }
        
        //Insert Prices:
        $db = Db::getInstance();
        $year = date("Y");
        foreach ($rows as $row) {
            $reference = $row['Articolo'];
            $db->update(
                'isacco_db',
                array(
                    'wholesale_price' => $row['Acquisto'],
                    'price' => $row['Listino'],
                    'sell_price' => $row['Vendita'],
                ),
                'reference = \''.pSQL($reference).'\' and year='.(int)$year
            );
        }

        $this->confirmations = $this->l('Prices updated.');
    }

    public function initContent()
    {
        $tpl = $this->context->smarty->fetch(
            _PS_MODULE_DIR_.$this->module->name."/views/templates/admin/script.tpl"
        );
        $form = new MpIsaccoDbForm();
        if (Tools::isSubmit('submitBulkimportisacco_db')) {
            $this->importProducts();
        }
        if (Tools::isSubmit('importIsaccoDb')) {
            $this->fields_list = array();
            $this->content = $this->renderFormProducts();
        }
        if (Tools::isSubmit('importPrices')) {
            $this->fields_list = array();
            $this->content = $this->renderFormPrices();
        }
        if (Tools::isSubmit('submitPrices')) {
            $this->processSubmitPrices();
        }
        parent::initContent();
    }

    public function readDb()
    {
        $json = $this->getUrl();
        $output = array();
        foreach ($json as $row) {
            $list = array();
            foreach ($row as $id=>$value) {
                if ($id == "colori" || $id == 'materiali' || $id == 'dimensioni' || $id == 'cat') {
                    $list[$id] = implode(",", $value);
                } elseif ($id == 'image' || $id == 'thumb') {
                    $list[$id] = 'http://www.isacco.it'.$value;
                } else {
                    $list[$id] = $value; 
                }
            }
            $list['year'] = date("Y");
            $output[] = $list;
        }

        $db = Db::getInstance();
        $db->delete(
            $this->table,
            'year='.date("Y")
        );
        foreach ($output as $row) {
            $db->insert(
                $this->table,
                array(
                    'year' => (int)Date("Y"),
                    'reference' => pSQL($row['id']),
                    'name' => pSQL($row['product']),
                    'color' => pSQL($row['colori']),
                    'material' => pSQL($row['materiali']),
                    'size' => pSQL($row['dimensioni']),
                    'image' => pSQL($row['image']),
                    'thumb' => PSQL($row['thumb']),
                    'price' => 0,
                    'sell_price' => 0,
                    'new' => $this->isNewProduct($row['id']),
                )
            );
        }

        return $output;
    }

    public function isNewProduct($reference)
    {
        $db = Db::getInstance();
        $sql = "select count(*) from "._DB_PREFIX_."product where reference='ISA".pSQL($reference)."'";
        $value = (int)$db->getValue($sql);
        if ($value) {
            return 0;
        }
        return 1;
    }

    /**
     * Function used to render the list to display for this controller
     *
     * @return string|false
     * @throws PrestaShopException
     */
    public function renderList()
    {   
        if (!($this->fields_list && is_array($this->fields_list))) {
            return false;
        }
        $this->getList($this->context->language->id);

        // If list has 'active' field, we automatically create bulk action
        if (isset($this->fields_list) && is_array($this->fields_list) && array_key_exists('active', $this->fields_list)
            && !empty($this->fields_list['active'])) {
            if (!is_array($this->bulk_actions)) {
                $this->bulk_actions = array();
            }

            $this->bulk_actions = array_merge(array(
                'enableSelection' => array(
                    'text' => $this->l('Enable selection'),
                    'icon' => 'icon-power-off text-success'
                ),
                'disableSelection' => array(
                    'text' => $this->l('Disable selection'),
                    'icon' => 'icon-power-off text-danger'
                ),
                'divider' => array(
                    'text' => 'divider'
                )
            ), $this->bulk_actions);
        }

        $helper = new HelperList();

        // Empty list is ok
        if (!is_array($this->_list)) {
            $this->displayWarning($this->l('Bad SQL query', 'Helper').'<br />'.htmlspecialchars($this->_list_error));
            return false;
        }

        $this->setHelperDisplay($helper);
        $helper->_default_pagination = $this->_default_pagination;
        $helper->_pagination = $this->_pagination;
        $helper->tpl_vars = $this->getTemplateListVars();
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

        // For compatibility reasons, we have to check standard actions in class attributes
        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }

        $helper->is_cms = $this->is_cms;
        $helper->sql = $this->_listsql;

        foreach ($this->_list as &$row) {
            $row['thumb'] = "<img src='".$row['thumb']."' style='width: 48px;'>";
            $row['name'] = html_entity_decode($row['name']);
            $row['color'] = html_entity_decode($row['color']);
            $row['material'] = html_entity_decode($row['material']);
            $row['size'] = $this->sortAttributes(html_entity_decode($row['size']));
            if ($row['new'] == 1) {
                $row['new'] = "<i class='icon icon-check text-success'></i>";
                $row['id_isacco_db'] = "<span class='badge badge-info'>".$row['id_isacco_db']."</span>";
            } else {
                $row['new'] = '';
            }
        }

        $list = $helper->generateList($this->_list, $this->fields_list);
        return $list;
    }

    public function processToggleStatus()
    {
        $boxes = Tools::getValue('productBox');
        if ($boxes) {
            foreach ($boxes as $box) {
                $product = new Product($box);
                $product->toggleStatus();
            }
        }
        return true;
    }

    public function getUrl()
    {
        $url= Tools::getValue('ISACCO_URL', '');
        Configuration::updateValue('ISACCO_URL', $url);
        $username=Tools::getValue('ISACCO_USER', '');
        Configuration::updateValue('ISACCO_USER', $username);
        $password=Tools::getValue('ISACCO_PWD', '');
        Configuration::updateValue('ISACCO_PWD', $password);
        
        $stream = stream_context_create(array(
            'http' => array(
                'header'  => "Authorization: Basic " . base64_encode("$username:$password")
            )
        ));
        $file = trim(file_get_contents($url, false, $stream));
        $pos = strpos($file, "{");
        $file = substr($file,$pos);
        $this->json_var = trim($file);
        $this->json = Tools::jsonDecode($this->json_var);
        return $this->json;
    }

    public function getJson()
    {
        $this->getUrl();
        foreach ($this->json as $row) {
            
        }
    }

    public function renderFormPrices()
    {
        $this->fields_form = array( 
            'form' => array(
                //'tinymce' => true,
                'legend' => array(
                    'title' => $this->module->l('Import Prices'),
                    'icon' => 'icon-euro',
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->module->l('Prices CSV'),
                        'name' => 'price_file',
                        'class' => 'fixed-width-xl',
                        'lang' => false,
                        'hint' => $this->module->l('File CSV with ; separator.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Import'),
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-download',
                ),
            )
        ); 
        
        $this->helper = new HelperForm();
        $this->helper->show_toolbar = true;
        $this->helper->table = "isacco_db";
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $this->helper->default_form_language = $lang->id;
        $this->helper->allow_employee_form_lang =
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->helper->identifier = "id_isacco_db";
        $this->helper->submit_action = 'submitPrices';
        $this->helper->currentIndex = $this->context->link->getAdminLink('AdminMpDbIsacco', false);
        $this->helper->token = Tools::getAdminTokenLite('AdminMpDbIsacco');
        $this->helper->tpl_vars = array(
            'fields_value' => array(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
    
        return $this->helper->generateForm(array($this->fields_form));
    }

    public function displayImportLink($token = null, $id = 0, $name = null)
    {
        /*
        $token will contain token variable
        $id will hold id_identifier value
        $name will hold display name
        */
        $smarty = $this->context->smarty;
        $path = _PS_MODULE_DIR_.$this->module->name.'/views/templates/hook/rowaction.tpl';
        $smarty->assign(
            array(
                'id' => $id,
                'href' => 'javascript:importProduct("'.$id.'");',
                'title' => $this->l('Import'),
                'icon' => 'icon-download',
                'name' => $name,
                'token' => $token,
            )
        );
        return $smarty->fetch($path);
    }

    public function sortAttributes($attr)
    {
        $arrSize = array(
            'XXS',
            'XS',
            'S',
            'M',
            'L',
            'XL',
            'XXL',
            '3XL',
            '4XL',
            '5XL',
        );

        $attributes = explode(",", Tools::strtoupper($attr));
        if (!$attributes) {
            return "";
        }
        sort($attributes);
        $attrArray = array();
        $sortArray = array();
        foreach ($attributes as $attribute) {
            $attrArray[$attribute] = $attribute;
        }
        
        foreach ($arrSize as $value) {
            if (in_array($value, $attributes)) {
                $sortArray[] = $value;
            }
        }

        if (count($sortArray)) {
            return implode(",", $sortArray);
        } else {
            $output = implode(",",$attributes);
            if ($output) {
                return $output;
            } else {
                return "Regolabile";
            }
        }
    }

    public function importProducts()
    {
        $box = Tools::getValue("isacco_dbBox");
        if (!$box) {
            $this->errors[] = $this->l('You have to select at least one product!');
        }

        $ids = implode(",", $box);
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select("*")
            ->from($this->table)
            ->where("id_isacco_db in (".pSQL($ids).")");
        $products = $db->executeS($sql);

        $totProducts = count($products);

        foreach($products as &$product) {
            $reference = "ISA".$product['reference'];
            if ($this->isNewProduct($reference)) {
                $product['new'] = 1;
            } else {
                $product['new'] = 0;
            }
            $product['size'] = $this->sortAttributes($product['size']);
            $this->importProduct($product);
        }

        $this->confirmations = sprintf($this->l('Imported %d products'), $totProducts);
    }

    public function importProduct($product)
    {
        $id_product = $this->getIdProductByReference("ISA".$product['reference']);
        if (!$id_product) {

            $id_product = (int)$this->createProduct($product);

            $color = explode('+',$product['color']);
            $size = explode(',', $product['size']);

            $combinations = $this->createCombinations($color,$size);
            $combinations = $this->createCombinationList($combinations);
                
            foreach ($combinations as $comb) {
                $productAttribute[] = array(
                    'id_product' => $id_product,
                    'price' => number_format($product['sell_price'] / 1.22, 6),
                    'weight' => 0,
                    'ecotax' => 0,
                    'quantity' => 0,
                    'reference' => $product['reference'],
                    'default_on' => 0,
                    'available_date' => '0000-00-00',
                );
            }

            $product = new Product($id_product);
            $product->generateMultipleCombinations($productAttribute, $combinations);
        } else {
            $prodObj = new Product($id_product);
            $prodObj->wholesale_price = number_format($product['wholesale_price'] / 1.22, 6);
            $prodObj->price = number_format($product['sell_price'] / 1.22, 6);
            $prodObj->update();
        }
    }

    public function createCombinations($color, $size)
    {
        $idGroup = array(
            14,
            36
        );
        $colors = array();
        foreach($color as $key=>$value) {
            $id_attribute = $this->getAttribute($idGroup[$key], $value);
            $colors[$idGroup[$key]] = array($id_attribute => $id_attribute);
        }

        $sizes = array();
        foreach($size as $key=>$value) {
            $id_attribute = $this->getAttribute(13, $value);
            $sizes[] = array($id_attribute => $id_attribute);
        }

        $output = array();
        foreach($colors as $col) {
            $output[] = $col;
        }
        $output[] = $sizes;
        return $output;
    }

    public function getAttribute($id_attribute_group, $name)
    {
        $id_lang = (int)Context::getContext()->language->id;
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('a.id_attribute')
            ->from('attribute_lang', 'al')
            ->innerJoin('attribute', 'a', 'a.id_attribute=al.id_attribute')
            ->where('name = \''.pSQL($name).'\'')
            ->where('a.id_attribute_group='.(int)$id_attribute_group)
            ->where('id_lang='.(int)$id_lang);
        $id_attribute = (int)$db->getValue($sql);
        if (!$id_attribute) {
            $attribute = new AttributeCore();
            $attribute->id_attribute_group = (int)$id_attribute_group;
            $attribute->color = "";
            $attribute->position = 0;
            $attribute->name[$id_lang] = $name;
            $attribute->add();
            return (int)$attribute->id;
        } else {
            return $id_attribute;
        }
    }

    public function createProduct($product)
    {
        $id_lang = (int)Context::getContext()->language->id;
        //PRODUCT
        $prod = new Product();
        $prod->id_supplier = 3;
        $prod->id_manufacturer = 3;
        $prod->id_category_default = 0;
        $prod->id_shop_default = 1;
        $prod->on_sale = 1;
        $prod->online_only = 1;
        $prod->ean13 = "";
        $prod->upc = "";
        $prod->ecotax = 0;
        $prod->quantity = 0;
        $prod->minimal_quantity = 0;
        $prod->price = number_format($product['sell_price'] /1.22, 6);
        $prod->wholesale_price = number_format($product['wholesale_price'] /1.22, 6);
        $prod->unity = "PZ";
        $prod->unit_price_ratio = 0;
        $prod->additional_shipping_cost = 0;
        $prod->reference = "ISA" . $product['reference'];
        $prod->supplier_reference = $product['reference'];
        $prod->location = "";
        $prod->width = 0;
        $prod->height = 0;
        $prod->depth = 0;
        $prod->weight = 0;
        $prod->out_of_stock = 2;
        $prod->quantity_discount = 0;
        $prod->customizable = 0;
        $prod->uploadable_files = 0;
        $prod->text_fields = 0;
        $prod->active = 1;
        $prod->redirect_type = '404';
        $prod->id_product_redirected = 0;
        $prod->available_for_order = 1;
        $prod->available_date = "0000-00-00";
        $prod->condition = "new";
        $prod->show_price = 1;
        $prod->indexed = 1;
        $prod->visibility = "both";
        $prod->cache_is_pack = 0;
        $prod->cache_has_attachments = 0;
        $prod->is_virtual = 0;
        $prod->cache_default_attribute = 0;
        $prod->date_add = date("Y-m-d H:i:s");
        $prod->date_upd = date("Y-m-d H:i:s");
        $prod->advanced_stock_management = 0;
        $prod->id_tax_rules_group = 108;
        $prod->pack_stock_type = 3;
        //LANGUAGE
        $prod->description[$id_lang] = "";
        $prod->description_short[$id_lang] = $this->setDescriptionShort($product);
        $prod->link_rewrite[$id_lang] = $this->setLinkRewrite($product);
        $prod->name[$id_lang] = $this->setNameProduct($product);
        //ADD
        $res = $prod->add();
        if ($res) {
            return (int)$prod->id;
        } else {
            return false;
        }
    }

    public function setDescriptionShort($product)
    {
        $desc = array(
            "<li>Riferimento: ".$product['reference']."</li>",
            "<li>Nome: ".$this->setNameProduct($product, false)."</li>",
            "<li>Colore: ".$product['color']."</li>",
            "<li>Materiale: ".$product['material']."</li>",
            "<li>Taglie: ".$product['size']."</li>",
        );
        return "<ul>".implode("", $desc)."</ul>";
    }

    public function setLinkRewrite($product)
    {
        $name = $this->setNameProduct($product);
        $url = $this->toURI($name);
        return $url;
    }

    public function setNameProduct($product, $addReference = true)
    {
        $name = explode(" - ", $product['name']);
        $reference = $product['reference'];
        if ($addReference) {
            return Tools::strtoupper($name[0] . " ISACCO " . $reference);
        } else {
            return $name[0];
        }
    }

    public function getIdProductByReference($reference)
    {
        $db = Db::getInstance();
        $sql = "select id_product from "._DB_PREFIX_."product where reference = '".pSQL($reference)."'";
        return (int)$db->getValue($sql);
    }

    function replace_accent($str) 
    { 
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ'); 
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o'); 
        return str_replace($a, $b, $str); 
    }

    function toURI($str, $replace = array(), $delimiter = '-')
    {
        if(!empty($replace))
        {
            $str = str_replace((array) $replace, ' ', $str);
        }

        $clean=$this->replace_accent($str);
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $clean);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    public function createCombinationList($list)
    {
        if (count($list) <= 1) {
            return count($list) ? array_map(create_function('$v', 'return (array($v));'), $list[0]) : $list;
        }
        $res = array();
        $first = array_pop($list);
        foreach ($first as $attribute) {
            $tab = $this->createCombinationList($list);
            foreach ($tab as $to_add) {
                $res[] = is_array($to_add) ? array_merge($to_add, array($attribute)) : array($to_add, $attribute);
            }
        }
        return $res;
    }

    private function importImage($imagePath, $product_id, $legend)
    {
        //import image
        $chunks = explode(".",$imagePath);
        $format = end($chunks); //file extension

        $image = new ImageCore();
        $image->cover=true;
        $image->force_id=false;
        $image->id=0;
        $image->id_image=0;
        $image->id_product = $product_id;
        $image->image_format = $format;
        $image->legend = $legend;
        $image->position=0;
        $image->source_index='';
        try {
            $image->save();
        } catch (Exception $exc) {
            PrestaShopLoggerCore::addLog('Error during image add: error ' . $exc->getCode() . ' ' . $exc->getMessage());
            $image = new ImageCore();
            $image->cover=false;
            $image->force_id=false;
            $image->id=0;
            $image->id_image=0;
            $image->id_product = $product_id;
            $image->image_format = $format;
            $image->legend = $legend;
            $image->position=0;
            $image->source_index='';
            $image->save();
        }
        
        if (!(int)$image->id) {
            PrestaShopLoggerCore::addLog('Error: imported image has not a valid id.');
            return false;
        }
        
        $imageTargetFolder = _PS_PROD_IMG_DIR_ . ImageCore::getImgFolderStatic((int)$image->id);
        if (!file_exists($imageTargetFolder)) {
            mkdir($imageTargetFolder, 0777, true);
        }
        $target = $imageTargetFolder . $image->id . '.' . $image->image_format;
        $copy = copy($imagePath, $target);
        MpImportProducts::addLog('copy image from ' . $imagePath . ' to ' . $target . ": " . (int)$copy);
        
        return (int)$copy;
    }
}
