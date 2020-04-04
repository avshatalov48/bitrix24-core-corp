<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>
<?$APPLICATION->IncludeComponent(
	"bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:app.layout",
		"POPUP_COMPONENT_TEMPLATE_NAME" => ".default",
		"POPUP_COMPONENT_PARAMS" => array(
			"COMPONENT_TEMPLATE" => ".default",
			"DETAIL_URL" => SITE_DIR."marketplace/detail/#code#/",
			"SEF_MODE" => "Y",
			"SEF_FOLDER" => SITE_DIR."marketplace/app/",
			"SEF_URL_TEMPLATES" => array(
				"application" => "#id#/",
			)
		),
		"USE_PADDING" => true,
		"PAGE_MODE" => true,
		"USE_UI_TOOLBAR" => "N",
		"PLAIN_VIEW" => (\CRestUtil::isSlider() ? "Y" : "N")
	),
	$component
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>