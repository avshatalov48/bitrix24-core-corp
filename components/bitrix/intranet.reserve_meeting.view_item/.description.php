<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
	"NAME" => GetMessage("INTRANET_RESMITVI_ITEM"),
	"DESCRIPTION" => GetMessage("INTRANET_RESMITVI_ITEM_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"COMPLEX" => "N",
	"PATH" => array(
		"ID" => "intranet",
		'NAME' => GetMessage('INTR_GROUP_NAME'),
		"CHILD" => array(
			"ID" => "resmit",
			"NAME" => GetMessage("INTRANET_RESMIT")
		)
	),
);
?>