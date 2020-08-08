<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

\Bitrix\Main\Localization\Loc::loadMessages(
	$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork_group/templates/.default/util_community.php"
);
$APPLICATION->AddHeadScript('/bitrix/templates/bitrix24/components/bitrix/socialnetwork.user_groups.link.add/.default/script.js');
?>
<script>
	BX.ready(function() {
		B24SGControl.getInstance().init({
			groupId: <?=$arParams["GROUP_ID"]?>,
			groupOpened: <?=($arResult['Group']['OPENED'] == 'Y' ? 'true' : 'false')?>,
			favoritesValue: <?=($arResult["FAVORITES"] ? 'true' : 'false')?>
		});
	});

	BX.message({
		SGMErrorSessionWrong: '<?=GetMessageJS("SONET_SGM_T_SESSION_WRONG")?>',
		SGMErrorCurrentUserNotAuthorized: '<?=GetMessageJS("SONET_SGM_T_NOT_ATHORIZED")?>',
		SGMErrorModuleNotInstalled: '<?=GetMessageJS("SONET_SGM_T_MODULE_NOT_INSTALLED")?>',
		SGMWaitTitle: '<?=GetMessageJS("SONET_SGM_T_WAIT")?>',
		SGMSubscribeButtonHintOn: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_HINT_ON")?>',
		SGMSubscribeButtonHintOff: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_HINT_OFF")?>',
		SGMSubscribeButtonTitleOn: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_TITLE_ON")?>',
		SGMSubscribeButtonTitleOff: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_TITLE_OFF")?>'
	});
</script>

<div class="socialnetwork-group-title-buttons">
	<? if (in_array($arResult["CurrentUserPerms"]["UserRole"], array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER))):
		$APPLICATION->includeComponent(
			'bitrix:intranet.binding.menu',
			'',
			array(
				'SECTION_CODE' => 'socialnetwork',
				'MENU_CODE' => 'group_notifications',
				'CONTEXT' => [
					'GROUP_ID' => $arResult['Group']['ID']
				]
			)
		);
		if ($arResult["bChatActive"])
		{
			?>
			<button id="group_menu_chat_button" class="ui-btn ui-btn-light-border ui-btn-icon-chat ui-btn-themes"
					title="<?=GetMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_SGM_T_CHAT_TITLE_PROJECT" : "SONET_SGM_T_CHAT_TITLE")?>"
					onclick="BXIM.openMessenger('sg<?=$arResult["Group"]["ID"]?>');"
			></button>
			<?
		}
		?>
		<button id="group_menu_subscribe_button" class="ui-btn ui-btn-light-border ui-btn-icon-follow ui-btn-themes
				<?=($arResult["bSubscribed"] ? " ui-btn-active" : "")?>"
				title="<?=GetMessage("SONET_SGM_T_NOTIFY_TITLE_".($arResult["bSubscribed"] ? "ON" : "OFF"))?>"
				onclick="B24SGControl.getInstance().setSubscribe(event);"
		></button>
	<? endif ?>
</div>
