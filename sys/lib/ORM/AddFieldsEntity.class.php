<?php
/**
* @project    DarsiPro CMS
* @package    AddFields Entity
* @url        https://darsi.pro
*/


namespace ORM;

class AddFieldsEntity extends \OrmEntity
{
    protected $id;
    protected $field_id;
    protected $module;
    protected $type;
    protected $name;
    protected $label;
    protected $size;
    protected $params;
    protected $content;

}