<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("FIT1C_SECTION_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("FIT1C_SECTION_TEMPLATE_DESCRIPTION"),
	"ICON" => "/images/cat_list.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 10,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('FIT1C_SERVICES_MAIN_SECTION'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => GetMessage('FIT1C_SERVICES_PARENT_SECTION')
		)
	)

);

?>