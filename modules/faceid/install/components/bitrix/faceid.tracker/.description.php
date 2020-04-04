<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("FIT_SECTION_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("FIT_SECTION_TEMPLATE_DESCRIPTION"),
	"ICON" => "/images/cat_list.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 10,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('FIT_SERVICES_MAIN_SECTION'),
		'CHILD' => array(
			'ID' => 'lead',
			'NAME' => GetMessage('FIT_SERVICES_PARENT_SECTION')
		)
	)

);

?>