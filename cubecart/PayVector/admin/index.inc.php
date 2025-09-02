<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if(!defined('CC_DS')) die('Access Denied');

require_once __DIR__.'/../util.php';

$GLOBALS['db'] -> misc(PayVectorSQL::createGEP_EntryPoints());
$GLOBALS['db'] -> misc(PayVectorSQL::createCRT_CrossReference());


$module		= new Module(__FILE__, $_GET['module'], 'admin/index.tpl', true);
$page_content = $module->display();