<?php
/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 */
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use \Bitrix\Intranet\Binding;
use \Bitrix\ImBot\Bot\Partner24;
use Bitrix\Intranet\Site\Sections\TimemanSection;
use \Bitrix\Main;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
$bitrix24Included = \Bitrix\Main\Loader::includeModule('bitrix24');

$userUrl = CComponentEngine::MakePathFromTemplate($arParams['~PATH_TO_USER_PROFILE'], array('user_id' => $arResult['User']['ID']));
Main\UI\Extension::load([
	'ui.hint',
	'qrcode',
	'ui.qrauthorization',
	'ui.fonts.opensans',
	'ui.avatar-editor',
	'avatar_editor'
]);
$themePicker = new ThemePicker($arParams['SITE_TEMPLATE_ID'] ?: (defined('SITE_TEMPLATE_ID') ? SITE_TEMPLATE_ID : 'bitrix24'));

$arResult += $APPLICATION->IncludeComponent(
	'bitrix:intranet.user.otp.connected',
	'widget',
	['USER_ID' => $arResult['User']['ID']],
	$this->getComponent(),
	[],
	true
);
$otpValue = null;
if ($arResult['OTP']['IS_ENABLED'] === 'Y' &&
	($USER->GetID() == $arResult['User']['ID'] || $arResult['OTP']['USER_HAS_EDIT_RIGHTS']))
{
	$otpValue = array_intersect_key(
		$arResult['OTP'], [
			'IS_ACTIVE' => null,
			'IS_MANDATORY' => null,
			'IS_EXIST' => null
		])
	;
	$otpValue['URL'] = $arResult['Urls']['CommonSecurity'];
}

$arResult['B24NET_WWW'] = false;
if (
	IsModuleInstalled('bitrix24')
	&& COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'
	&& Main\Loader::includeModule('socialservices')
	&& ($res = \Bitrix\Socialservices\UserTable::getList([
		'filter' => [
			'=USER_ID' => $arResult['User']['ID'],
			'=EXTERNAL_AUTH_ID' => CSocServBitrix24Net::ID
		],
		'select' => ['PERSONAL_WWW'],
		'cache' => ['ttl' => 84600]
	])->fetch())
)
{
	$arResult['B24NET_WWW'] = true;
}
$photoId = !empty($arResult['User']['PHOTO']) ? $arResult['User']['PERSONAL_PHOTO'] : null;
?>
<script type='application/javascript'>
BX.message(<?=CUtil::phpToJsObject(Main\Localization\Loc::loadLanguageFile(__FILE__))?>);
BX.ready(function() {
	BX.Intranet.UserProfile.Widget.init(
		BX('<?=\CUtil::JSEscape($arParams['TARGET_ID'])?>'),
		<?=\CUtil::PhpToJSObject([
		'component' => [
			'signedParameters' => $this->getComponent()->getSignedParameters(),
			'componentName' => $this->getComponent()->getName(),
		],
		'canEditProfile' => $arResult['Permissions']['edit'] ? 'Y' : 'N',
		'profile' => [
				'ID' => $arResult['User']['ID'],
				'FULL_NAME' => $arResult['User']['FULL_NAME'],
				'PHOTO' =>
					$photoId > 0
						? call_user_func(
							function($photoId)
							{
								if (
									$photoId > 0
									&& ($file = \CFile::GetFileArray($photoId))
									&& ($fileTmp = \CFile::ResizeImageGet(
										$file,
										['width' => 100, 'height' => 100],
										BX_RESIZE_IMAGE_PROPORTIONAL,
										false,
										false,
										true
									))
								)
								{
									return $fileTmp['src'];
								}
								return '';
							},
							$photoId
						)
						: ''
				,
				'STATUS' => ((
					!empty($arResult['User']['STATUS'])
					&& (
						!( $arResult['User']['STATUS'] === 'employee'
							&& ($arResult['IsOwnProfile'] || !$arResult['Permissions']['edit'])
						)
						|| $arResult['User']['SHOW_SONET_ADMIN']
					)) ? $arResult['User']['STATUS'] : ''
				),
				'WORK_POSITION' => $arResult['User']['WORK_POSITION'],
				'URL' => $userUrl
			]
			+ (class_exists(Bitrix\UI\Avatar\Mask\Helper::class)
				&& \Bitrix\Main\Config\Option::get('ui', 'avatar-editor-availability-delete-after-10.2022', 'N') === 'Y' ?
			[
				'MASK' => Bitrix\UI\Avatar\Mask\Helper::getData($photoId)
			] : []),
			'features' => [
				'themePicker' => array_intersect_key(
					($themePicker->getCurrentTheme() ?? []),
					['title' => '', 'previewColor' => '', 'previewImage' => '', 'id' => '']
				),
				'adminPanel' => (!$bitrix24Included && $USER->isAdmin()) ? 'Y' : 'N',
				'b24netPanel' => $arResult['B24NET_WWW'] ? 'Y' : 'N',
				'pulse' => !$arResult['isExtranetSite'] ? 'Y' : 'N',
				'appInstalled' => [
					'APP_WINDOWS_INSTALLED' => $arResult['User']['APP_WINDOWS_INSTALLED'] ? 'Y' : 'N',
					'APP_MAC_INSTALLED' => $arResult['User']['APP_MAC_INSTALLED'] ? 'Y' : 'N',
					'APP_IOS_INSTALLED' => $arResult['User']['APP_IOS_INSTALLED'] ? 'Y' : 'N',
					'APP_ANDROID_INSTALLED' => $arResult['User']['APP_ANDROID_INSTALLED'] ? 'Y' : 'N',
					'APP_LINUX_INSTALLED' => $arResult['User']['APP_LINUX_INSTALLED'] ? 'Y' : 'N',
				],
				'loginHistory' => [
					'url' => TimemanSection::getUserLoginHistoryUrl(),
					'isCloud' => $bitrix24Included,
					'isHide' => $bitrix24Included && (\CBitrix24::getPortalZone() === 'ua'),
					'isAvailableUserLoginHistory' => isset($arResult['isAvailableUserLoginHistory']) && $arResult['isAvailableUserLoginHistory'],
					'isConfiguredUserLoginHistory' => isset($arResult['isConfiguredUserLoginHistory']) && $arResult['isConfiguredUserLoginHistory']
				],
				'stressLevel' => Main\Config\Option::get('intranet', 'stresslevel_available', 'Y') === 'Y' && (
					!$bitrix24Included || \Bitrix\Bitrix24\Release::isAvailable('stresslevel')
				) && !in_array($arResult['User']['STATUS'], ['email', 'extranet']) && !$arResult['isExtranetSite'] ? 'Y' : 'N',
				'otp' => $otpValue,
				'bindings' =>
					!$arResult['isExtranetSite']
					&& !in_array($arResult['User']['STATUS'], ['email', 'extranet']) ? Binding\Menu::getMenuItems(
						'top_panel',
						'user_menu',
						['inline' => true, 'context' => ['USER_ID' => $USER->GetID()]]) : [],
				'im' => IsModuleInstalled('im') ? 'Y' : 'N'
			]
		])?>);
});
</script>
<?php
if (!$arResult['isExtranetSite'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.ustat.status',
		'lite',
		[]
	);
}
