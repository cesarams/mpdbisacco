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

class IsaccoDb extends ObjectModel
{
    public $year;
    public $reference;
    public $name;
    public $color;
    public $material;
    public $size;
    public $image;
    public $thumb;
    public $wholesale_price;
    public $price;
    public $sell_price;
    public $new;
    
    public static $definition = array(
        'table' => "isacco_db",
        'primary' => 'id_isacco_db',
        'multilang' => TRUE,
        'fields' => array(
            'year' => array('type' => self::TYPE_INT, 'validate' => 'isUnignedInt', 'required' => true),
            'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'color' => array('type' => self::TYPE_STRING),
            'material' => array('type' => self::TYPE_STRING),
            'size' => array('type' => self::TYPE_STRING),
            'image' => array('type' => self::TYPE_STRING),
            'thumb' => array('type' => self::TYPE_STRING),
            'wholesale_price' => array('type' => self::TYPE_FLOAT),
            'price' => array('type' => self::TYPE_FLOAT),
            'sell_price' => array('type' => self::TYPE_FLOAT),
            'new' => array('type' => self::TYPE_BOOL),
        )
    );
}
