<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/timeman/meeting/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));?>
<?
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}

if (IsModuleInstalled("meeting"))
{
	GetGlobalID();
	$APPLICATION->IncludeComponent("bitrix:meetings", ".default", array(
			"SEF_MODE"          => "Y",
			"SEF_FOLDER"        => "/timeman/meeting/",
			"SEF_URL_TEMPLATES" => array(
				"list"         => "",
				"meeting"      => "meeting/#MEETING_ID#/",
				"meeting_edit" => "meeting/#MEETING_ID#/edit/",
				"meeting_copy" => "meeting/#MEETING_ID#/copy/",
				"item"         => "item/#ITEM_ID#/",
			)
		),
		false
	);
}
elseif (
	!\Bitrix\Intranet\Settings\Tools\ToolsManager::getInstance()->checkAvailabilityByToolId('meetings')
	&& (!\Bitrix\Main\Loader::includeModule('bitrix24') || \Bitrix\Bitrix24\Feature::isFeatureEnabled('meeting'))
)
{
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.settings.tool.stub',
		'',
		[
			'LIMIT_CODE' => 'limit_office_meetings_off',
			'MODULE' => 'meeting',
			'SOURCE' => 'meeting',
		],
	);
}
elseif (!(!IsModuleInstalled("meeting") && in_array($licenseType, array("company", "edu", "nfr"))))
{
	?>
	<script>
		BX.ready(() => {
			BX.UI.InfoHelper.show("limit_office_meetings");
		});
	</script>
	<?php
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>