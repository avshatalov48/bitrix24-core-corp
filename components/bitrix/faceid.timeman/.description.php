<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("FITWD_SECTION_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("FITWD_SECTION_TEMPLATE_DESCRIPTION"),
	"ICON" => "/images/cat_list.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 10,
	'PATH' => array(
		'ID' => 'intranet',
		'NAME' => GetMessage('FITWD_SERVICES_MAIN_SECTION'),
		'CHILD' => array(
			'ID' => 'timeman',
			'NAME' => GetMessage('FITWD_SERVICES_PARENT_SECTION')
		)
	)

);

?>