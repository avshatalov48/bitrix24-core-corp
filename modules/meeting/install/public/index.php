<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("#TITLE#");?>
<?
$APPLICATION->IncludeComponent("bitrix:meetings", ".default", array(
	"RESERVE_MEETING_IBLOCK_TYPE" => "events",
	"RESERVE_MEETING_IBLOCK_ID" => "#RESERVE_MEETING_IBLOCK_ID#",
	"RESERVE_VMEETING_IBLOCK_TYPE" => "events",
	"RESERVE_VMEETING_IBLOCK_ID" => "#RESERVE_VMEETING_IBLOCK_ID#",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "#PATH#",
	"SEF_URL_TEMPLATES" => array(
		"list" => "",
		"meeting" => "meeting/#MEETING_ID#/",
		"meeting_edit" => "meeting/#MEETING_ID#/edit/",
		"meeting_copy" => "meeting/#MEETING_ID#/copy/",
		"item" => "item/#ITEM_ID#/",
	)
	),
	false
);

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>