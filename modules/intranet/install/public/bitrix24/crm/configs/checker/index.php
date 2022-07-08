<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper", "", [
		"POPUP_COMPONENT_NAME" => "bitrix:crm.config.checker",
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"POPUP_COMPONENT_PARAMS" => [
			"DISABLE_TOP_MENU" => "Y"
		],
		'USE_PADDING' => false,
	]);


?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
