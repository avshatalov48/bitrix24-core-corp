<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle(GetMessage("WD_WEBDAV"));
?><?$APPLICATION->IncludeComponent(
	"bitrix:webdav",
	"",
	Array(
		"IBLOCK_TYPE" => "#IBLOCK_TYPE#", 
		"IBLOCK_ID" => "#IBLOCK_ID#", 
		
		"SEF_MODE" => "#SEF_MODE#", 
		"SEF_FOLDER" => "#SEF_FOLDER#", 
		"BASE_URL" => "#BASE_URL#", 
		
		"CACHE_TYPE" => "A", 
		"CACHE_TIME" => "3600", 
		"SET_TITLE" => "Y", 
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>