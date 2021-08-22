<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page;

$APPLICATION->SetTitle("");
Page\Asset::getInstance()->addJs($templateFolder.'/js/form-entity.js');
?>
<script>
	new BX.Intranet.UserProfile.Password.EntityEditor({});
</script>
<div class="js-intranet-password">
	<?
	$APPLICATION->IncludeComponent(
		"bitrix:ui.form",
		"",
		array(
			"GUID" => "INTRANET_USER_PROFILE_PASSWORD",
			"INITIAL_MODE" => "edit",
			"ENTITY_TYPE_NAME" => "USER",
			"ENTITY_ID" => $arParams["USER_ID"],
			"ENTITY_FIELDS" => $arResult["FormFields"],
			"ENTITY_CONFIG" => $arResult["FormConfig"],
			"ENABLE_PERSONAL_CONFIGURATION_UPDATE" => false,
			"ENABLE_COMMON_CONFIGURATION_UPDATE" => false,
			"ENABLE_SETTINGS_FOR_ALL" => false,
			"ENABLE_SECTION_EDIT" => false,
			"ENABLE_SECTION_CREATION" => false,
			"ENABLE_SECTION_DRAG_DROP" => false,
			"ENABLE_CONFIG_CONTROL" => false,
			"COMPONENT_AJAX_DATA" => array(
				"COMPONENT_NAME" => $this->getComponent()->getName(),
				"ACTION_NAME" => "save",
				"SIGNED_PARAMETERS" => $this->getComponent()->getSignedParameters()
			)
		)
	);

	if ($arResult["IsOwnProfile"]):?>
	<div style="margin-left: 20px">
		<span class="ui-btn ui-btn-light-border" data-role="intranet-pass-logout"><?=GetMessage("INTRANET_USER_PROFILE_PASSWORD_LOGOUT")?></span>
	</div>
	<?endif; ?>
</div>

<script>
	BX.message({
		"INTRANET_USER_PROFILE_PASSWORD_BUTTON_CONTINUE" : "<?=CUtil::JSEscape(GetMessage("INTRANET_USER_PROFILE_PASSWORD_BUTTON_CONTINUE"))?>",
		"INTRANET_USER_PROFILE_PASSWORD_BUTTON_CANCEL" : "<?=CUtil::JSEscape(GetMessage("INTRANET_USER_PROFILE_PASSWORD_BUTTON_CANCEL"))?>",
		"INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TEXT" : "<?=CUtil::JSEscape(GetMessage("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TEXT"))?>",
		"INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TITLE" : "<?=CUtil::JSEscape(GetMessage("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_TITLE"))?>",
		"INTRANET_USER_PROFILE_PASSWORD_LOGOUT_SUCCESS" : "<?=CUtil::JSEscape(GetMessage("INTRANET_USER_PROFILE_PASSWORD_LOGOUT_SUCCESS"))?>",
		"INTRANET_USER_PROFILE_PASSWORD_CLOSE" : "<?=CUtil::JSEscape(GetMessage("INTRANET_USER_PROFILE_PASSWORD_CLOSE"))?>"
	});
	
	BX.ready(function () {
		new BX.Intranet.UserProfile.Password({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			userId: '<?=CUtil::JSEscape($arParams["USER_ID"])?>'
		});
	});

</script>
