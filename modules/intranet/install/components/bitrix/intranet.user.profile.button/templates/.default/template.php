<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 * @global \CMain $APPLICATION
 */

use Bitrix\Main;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Uri;

Main\UI\Extension::load([
	'ui.icons.b24',
	'sidepanel',
	'ui.hint',
	'ui.fonts.opensans',
]);

$this->setFrameMode(true);

//region Profile popup
?>
	<div class="user-block" id="user-block" data-user-id="<?= $arResult['USER_ID'] ?>">
	<span class="ui-icon ui-icon-common-user user-img" id="user-block-icon"><?php
		$style = (
		($arResult['USER_PERSONAL_PHOTO_SRC'] ?? null)
			? "background: url('"
			. Uri::urnEncode($arResult['USER_PERSONAL_PHOTO_SRC'])
			. "') no-repeat center; background-size: cover;"
			: ''
		);
		?><i style="<?= $style ?>"></i>
	</span>
		<span class="user-name" id="user-name"><?= $arResult['USER_NAME'] ?></span>
	</div>
	<script>
		BX.ready(() => {
			BX.message(<?=CUtil::phpToJsObject(Main\Localization\Loc::loadLanguageFile(__FILE__))?>);
			BX.Event.EventEmitter.subscribe(
				'BX.Intranet.UserProfile:Avatar:changed',
				(event) => {
					const data = event.getData()[0];
					const block = BX('user-block');
					const url = data && data['url'] ? data['url'] : '';
					const userId = data && data['userId'] ? data['userId'] : 0;
					if (block && block.dataset.userId === userId.toString()) {
						const avatarNode = BX('user-block').querySelector('i');
						avatarNode.style =
							BX.Type.isStringFilled(url)
								? "background-size: cover; background-image: url('" + encodeURI(url) + "')"
								: ''
						;
					}

				});

			BX.Intranet.UserProfile.Widget.init(
				BX('user-block'),
				<?=\CUtil::PhpToJSObject([
					'component' => [
						'signedParameters' => $this->getComponent()->getSignedParameters(),
						'componentName' => $this->getComponent()->getName(),
					],
					'profile' => [
						'ID' => $arResult['USER_ID'],
						'FULL_NAME' => $arResult['USER_NAME'],
						'PHOTO' => $arResult['USER_PERSONAL_PHOTO_SRC'],
						'STATUS' => (
						!empty($arResult['USER_STATUS'])
						&& (
							$arResult['USER_STATUS'] !== 'employee'
							|| $arResult['IS_SOCIALNETWORK_ADMIN']
						) ? $arResult['USER_STATUS'] : ''
						),
						'WORK_POSITION' => $arResult['USER_WORK_POSITION'],
						'URL' => $arResult['USER_URL'],
						'MASK' => $arResult['MASK'],
					],
					'features' => [
						'adminPanel' => (!$arResult['IS_CLOUD'] && $arResult['IS_ADMIN']) ? 'Y' : 'N',
						'b24netPanel' => $arResult['B24NET_PANEL_AVAILABLE'] ? 'Y' : 'N',
						'pulse' => !$arResult['IS_EXTRANET'] ? 'Y' : 'N',
						'appInstalled' => [
							'APP_WINDOWS_INSTALLED' => $arResult['APP_WINDOWS_INSTALLED'] ? 'Y' : 'N',
							'APP_MAC_INSTALLED' => $arResult['APP_MAC_INSTALLED'] ? 'Y' : 'N',
							'APP_IOS_INSTALLED' => $arResult['APP_IOS_INSTALLED'] ? 'Y' : 'N',
							'APP_ANDROID_INSTALLED' => $arResult['APP_ANDROID_INSTALLED'] ? 'Y' : 'N',
							'APP_LINUX_INSTALLED' => $arResult['APP_LINUX_INSTALLED'] ? 'Y' : 'N',
						],
						'loginHistory' => $arResult['LOGIN_HISTORY'],
						'stressLevel' => $arResult['IS_STRESSLEVEL_AVAILABLE'] ? 'Y' : 'N',
						'otp' => $arResult['OTP'],
						'bindings' => $arResult['BINDINGS'],
						'im' => ModuleManager::isModuleInstalled('im') ? 'Y' : 'N',
						'signDocument' => ($arResult['IS_SIGN_DOCUMENT_AVAILABLE'] ?? false) ? 'Y' : 'N',
					],
					'desktopDownloadLinks' => $arResult['DESKTOP_DOWNLOAD_LINKS'],
					'networkProfileUrl' => $arResult['NETWORK_PROFILE_URL'],
				])?>);
		});
	</script>
<?php
