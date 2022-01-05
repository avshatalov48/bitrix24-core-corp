<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/socialnetwork_group/templates/.default/util_community.php');
$APPLICATION->AddHeadScript('/bitrix/templates/bitrix24/components/bitrix/socialnetwork.user_groups.link.add/.default/script.js');

?>
<script>
	BX.ready(function() {
		B24SGControl.getInstance().init({
			groupId: <?= (int)$arParams["GROUP_ID"] ?>,
			groupType: '<?= CUtil::JSEscape($arResult['Group']['TypeCode']) ?>',
			isProject: <?= ($arResult['Group']['PROJECT'] === 'Y' ? 'true' : 'false') ?>,
			groupOpened: <?= ($arResult['Group']['OPENED'] === 'Y' ? 'true' : 'false') ?>,
			userRole: '<?= $arResult['CurrentUserPerms']['UserRole']?>',
			userIsMember: <?= ($arResult['CurrentUserPerms']['UserIsMember'] ? 'true' : 'false') ?>,
			userIsAutoMember: <?= (isset($arResult['CurrentUserPerms']['UserIsAutoMember']) && $arResult['CurrentUserPerms']['UserIsAutoMember'] ? 'true' : 'false') ?>,
			editFeaturesAllowed: <?= (\Bitrix\Socialnetwork\Helper\Workgroup::getEditFeaturesAvailability() ? 'true' : 'false') ?>,
			copyFeatureAllowed: <?= (\Bitrix\Socialnetwork\Helper\Workgroup::isGroupCopyFeatureEnabled() ? 'true' : 'false') ?>,
			favoritesValue: <?= ($arResult["FAVORITES"] ? 'true' : 'false') ?>,
			canInitiate: <?= ($arResult['CurrentUserPerms']['UserCanInitiate'] && !$arResult['HideArchiveLinks'] ? 'true' : 'false') ?>,
			canProcessRequestsIn: <?= ($arResult['CurrentUserPerms']['UserCanProcessRequestsIn'] && !$arResult['HideArchiveLinks'] ? 'true' : 'false') ?>,
			canModify: <?= ($arResult['CurrentUserPerms']['UserCanModifyGroup'] ? 'true' : 'false') ?>,
			canPickTheme: <?= (
				\Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker::isAvailable()
				&& $arResult['CurrentUserPerms']['UserCanModifyGroup']
					? 'true'
					: 'false'
			) ?>,
			urls: <?= CUtil::PhpToJSObject($arResult['Urls']) ?>,
			slider: <?= ($arResult['IS_IFRAME'] ? 'true' : 'false') ?>,
			isChatActive: <?= $arResult['bChatActive'] ? 'true' : 'false' ?>,
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

<div class="socialnetwork-group-title-buttons"><?php

	if (
		!$arResult['IS_IFRAME']
		&& in_array($arResult['CurrentUserPerms']['UserRole'], \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember(), true)
	)
	{
		if (!$arResult['isScrumProject'])
		{
			$APPLICATION->includeComponent(
				'bitrix:intranet.binding.menu',
				'',
				[
					'SECTION_CODE' => 'socialnetwork',
					'MENU_CODE' => 'group_notifications',
					'CONTEXT' => [
						'GROUP_ID' => $arResult['Group']['ID']
					]
				]
			);
		}

		if ($arResult['bChatActive'])
		{
			?>
			<button id="group_menu_chat_button" class="ui-btn ui-btn-light-border ui-btn-icon-chat ui-btn-themes"
				title="<?= Loc::getMessage($arResult['Group']['PROJECT'] === 'Y' ? 'SONET_SGM_T_CHAT_TITLE_PROJECT' : 'SONET_SGM_T_CHAT_TITLE') ?>"
				onclick="top.BXIM.openMessenger('sg<?= (int)$arResult['Group']['ID'] ?>');"
			></button>
			<?php
		}

		$classList = [
			'ui-btn',
			'ui-btn-light-border',
			'ui-btn-icon-follow',
			'ui-btn-themes',
		];

		if ($arResult['bSubscribed'])
		{
			$classList[] = 'ui-btn-active';
		}
		?><button id="group_menu_subscribe_button" class="<?= implode(' ', $classList) ?>"
				  title="<?= Loc::getMessage('SONET_SGM_T_NOTIFY_TITLE_' . ($arResult['bSubscribed'] ? 'ON' : 'OFF')) ?>"
				  onclick="B24SGControl.getInstance().setSubscribe(event);"
		></button><?php

	}
?></div>
