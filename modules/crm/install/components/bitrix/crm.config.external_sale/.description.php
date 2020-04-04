<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
	"NAME" => GetMessage("CRM_BPWC_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("CRM_BPWC_COMPONENT_NAME_DESCRIPTION"),
	"ICON" => "/images/comp.gif",
	"COMPLEX" => "Y",
	"PATH" => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => GetMessage('CRM_CONFIG_NAME'),
        )
	),
);
?>