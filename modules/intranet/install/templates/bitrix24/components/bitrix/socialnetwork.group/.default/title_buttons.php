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
		if ($arResult["bChatActive"])
		{
			?>
			<span
					id="group_menu_chat_button"
					class="
						webform-small-button
						webform-small-button-transparent
						bx24-top-toolbar-button
						socialnetwork-group-chat-button
					"
					title="<?=GetMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_SGM_T_CHAT_TITLE_PROJECT" : "SONET_SGM_T_CHAT_TITLE")?>"
					onclick="BXIM.openMessenger('sg<?=$arResult["Group"]["ID"]?>');"
			>
				<span class="webform-small-button-icon"></span>
			</span>
			<?
		}
		?>
		<span
			id="group_menu_subscribe_button"
			class="
				webform-small-button
				webform-small-button-transparent
				bx24-top-toolbar-button
				socialnetwork-group-notification-button
				<?=($arResult["bSubscribed"] ? " webform-button-active" : "")?>"
			title="<?=GetMessage("SONET_SGM_T_NOTIFY_TITLE_".($arResult["bSubscribed"] ? "ON" : "OFF"))?>"
			onclick="B24SGControl.getInstance().setSubscribe(event);"
		>
			<span class="webform-small-button-icon"></span>
		</span>
	<? endif ?>
</div>