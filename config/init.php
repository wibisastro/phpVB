<?php

/********************************************************************
*	Date		: Sep, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

session_start();

//--------load configuration

include(__DIR__.'/config.php');

//--------load classes

require_once __DIR__."/../vendor/autoload.php";

$api = new Gov2lib\api\api;
$dsn = new Gov2lib\env\dbConnect;
$doc = new Gov2lib\env\document;
$ses = new Gov2lib\env\session;
$exc = new Gov2lib\env\customException;
$frm = new Gov2lib\helper\formage;
$scf = new Gov2lib\helper\scaffold;

//--------routing

//---edit this file to register web address in the routing table
$router=__DIR__.'/routingTable.xml';
include("initRoute.php");

//--------templating

//---fill with all required template directories in the array 
$templates=array(
__DIR__.'/../template/base',
$page->templateDir,
$scf->templateDir
);
include("initTemplate.php");

//---default template, re-call to replace with your own template
$template = $twig->load($page->baseName.'Body.html');
