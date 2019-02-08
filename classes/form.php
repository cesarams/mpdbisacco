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

class MpIsaccoDbForm
{
    public $helper;
    
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->link = $this->context->link;
        $this->controller = $this->context->controller;
        $this->module = $this->controller->module;
    }
    
    public function renderForm()
    {
        $this->fields_form = array( 
            'form' => array(
                //'tinymce' => true,
                'legend' => array(
                    'title' => $this->module->l('Isacco DB'),
                    'icon' => 'icon-db',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('URL'),
                        'name' => 'ISACCO_URL',
                        'class' => 'fixed-width-xl',
                        'lang' => false,
                        'size' => 64,
                        'hint' => $this->module->l('Insert url for Isacco Database'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('User'),
                        'name' => 'ISACCO_USER',
                        'class' => 'fixed-width-xl',
                        'lang' => false,
                        'size' => 64,
                        'hint' => $this->module->l('Insert Username for Isacco Database'),
                    ),
                    array(
                        'type' => 'password',
                        'label' => $this->module->l('Password'),
                        'name' => 'ISACCO_PWD',
                        'lang' => false,
                        'size' => 64,
                        'hint' => $this->module->l('Insert password for Isacco Database'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Compute price'),
                        'name' => 'ISACCO_COMPUTE',
                        'class' => 'fixed-width-xl',
                        'lang' => false,
                        'size' => 64,
                        'hint' => $this->module->l('Insert formula ROUND:[0.00],ADD:[0.00%]'),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'ISACCO_ACTION',
                    ),
                ),
                'buttons' => array(
                    'updateOptions' => array(
                        'name' => 'submitOptions',
                        'title' => $this->module->l('Read'),
                        'class' => 'pull-right',
                        'icon' => 'process-icon-preview',
                        'href' => 'javascript:submitOptions();'
                    ),
                    'readJson' => array(
                        'name' => 'submitJson',
                        'title' => $this->module->l('Import'),
                        'class' => 'pull-right',
                        'icon' => 'process-icon-download',
                        'href' => 'javascript:submitJson();'
                    )
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
        $this->helper->submit_action = 'submitOptions';
        $this->helper->currentIndex = $this->context->link->getAdminLink('AdminMpDbIsacco', false);
        $this->helper->token = Tools::getAdminTokenLite('AdminMpDbIsacco');
        $this->helper->tpl_vars = array(
            'fields_value' => $this->getOptions(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
    
        return $this->helper->generateForm(array($this->fields_form));
    }

    public function saveOptions()
    {

    }

    public function getOptions()
    {
        return array(
            'ISACCO_URL' => Configuration::get('ISACCO_URL'),
            'ISACCO_USER' => Configuration::get('ISACCO_USER'),
            'ISACCO_PWD' => Configuration::get('ISACCO_PWD'),
            'ISACCO_COMPUTE' => Configuration::get('ISACCO_COMPUTE'),
            'ISACCO_ACTION' => '',
        );
    }
}
