<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global \CMain $APPLICATION
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

use Bitrix\ImBot\Bot\Partner24;
use Bitrix\ImBot\Bot\Support24;
use Bitrix\ImBot\Bot\SupportBox;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet;
use Bitrix\Main\Web\Json;

Main\UI\Extension::load(['ui.icons.b24', 'sidepanel']);

$imBarExists = $arResult['IM_BAR_EXISTS'];
?>

<?php if ($imBarExists): ?>
	<?php $this->setViewTarget('im', 200); ?>
<?php else: ?>
	<div class="help-block" id="bx-help-block" title="<?= Loc::getMessage('AUTH_HELP') ?>">
		<div class="help-icon-border"></div>
		<div class="help-block-icon"></div>
		<div class="help-block-counter-wrap" id="bx-help-notify">
		</div>
	</div>
<?php endif; ?>
<?php

$frame = $this->createFrame('b24_helper')->begin('');

$supportBotId = 0;

if (Loader::includeModule('imbot'))
{
	if (
		class_exists('\\Bitrix\\ImBot\\Bot\\Support24')
		&& (Support24::getSupportLevel() === Support24::SUPPORT_LEVEL_PAID)
		&& Support24::isEnabled()
	)
	{
		$supportBotId = (int)Support24::getBotId();
	}
	else if (
		method_exists('\\Bitrix\\ImBot\\Bot\\SupportBox', 'isEnabled')
		&& SupportBox::isEnabled()
	)
	{
		$supportBotId = SupportBox::getBotId();
	}
}

$isAdmin = Intranet\CurrentUser::get()->isAdmin() ? 1 : 0;
$isCloud = Main\ModuleManager::isModuleInstalled('bitrix24');
$userId = Main\Engine\CurrentUser::get()->getId();
$userEmail = Main\Engine\CurrentUser::get()->getEmail();
$tariff = Option::get('main', '~controller_group_name', '');

CJSCore::Init(array('helper'));

$helpUrl = $arResult['HELPDESK_URL'] . '/widget2/';
$helpUrlParameters = [
	'url' => 'https://' . $_SERVER['HTTP_HOST'] . $APPLICATION->GetCurPageParam(),
	'is_admin' => $isAdmin,
	'user_id' => $userId,
	'user_email' => $userEmail,
	'tariff' => $tariff,
	'is_cloud' => $isCloud ? '1' : '0',
	'support_bot' => $supportBotId,
];

$imBotIncluded = Loader::includeModule('imbot');

if ($imBotIncluded)
{
	$helpUrlParameters['support_partner_code'] = Partner24::getBotCode();
	$supportPartnerName = Main\Text\Encoding::convertEncoding(Partner24::getPartnerName(), SITE_CHARSET, 'utf-8');
	$helpUrlParameters['support_partner_name'] = $supportPartnerName;
}

$helpUrl = (new Main\Web\Uri($helpUrl))->addParams($helpUrlParameters)->getUri();
$frameOpenUrl = (new Main\Web\Uri($helpUrl))->addParams(['action' => 'open'])->getUri();
$frameCloseUrl = (new Main\Web\Uri($helpUrl))->addParams(['action' => 'close'])->getUri();

$host = $isCloud && defined('BX24_HOST_NAME') ? BX24_HOST_NAME : CIntranetUtils::getHostName();
$notifyData = [
	'support_bot' => $supportBotId,
	'is_admin' => $isAdmin,
	'user_id' => $userId,
	'user_email' => $userEmail,
	'tariff' => $tariff,
	'host' => $host,
	'key' => $isCloud ? CBitrix24::RequestSign($host . $userId) : md5($host . $userId .'BX_USER_CHECK'),
	'is_cloud' => $isCloud ? '1' : '0',
	'user_date_register' => Intranet\CurrentUser::get()->getDateRegister()?->getTimestamp(),
	'portal_date_register' => $isCloud ? Option::get('main', '~controller_date_create', '') : '',
	'partner_link' => Option::get('bitrix24', 'partner_id', 0) ? 'Y' : 'N',
	'counter_update_date' => $arResult['COUNTER_UPDATE_DATE'] ?? 0,
];

if ($imBotIncluded)
{
	$notifyData['support_partner_code'] = $helpUrlParameters['support_partner_code'];
	$notifyData['support_partner_name'] = $helpUrlParameters['support_partner_name'];
}
?>
	<script>
		BX.Helper.init({
			frameOpenUrl : '<?= CUtil::JSEscape($frameOpenUrl) ?>',
			helpBtn : BX('bx-help-block'),
			notifyBlock : BX('bx-help-notify'),
			langId: '<?= LANGUAGE_ID ?>',
			ajaxUrl: '<?= $this->GetFolder() . "/ajax.php" ?>',
			needCheckNotify: '<?=($arResult["NEED_CHECK_HELP_NOTIFICATION"] === "Y" ? "Y" : "N")?>',
			notifyNum: '<?= CUtil::JSEscape($arResult["HELP_NOTIFY_NUM"]) ?>',
			notifyData: <?= CUtil::PhpToJSObject($notifyData) ?>,
			notifyUrl: '<?= $arResult["HELPDESK_URL"] . "/widget2/notify.php" ?>',
			helpUrl: '<?= $arResult["HELPDESK_URL"] ?>',
			runtimeUrl: '//helpdesk.bitrix24.ru/widget/hero/runtime.js'
		});

		<?php if ($arResult['OPEN_HELPER_AFTER_PAGE_LOADING']): ?>
			BX.ready(function() {
				BX.Helper.show();
			});
		<?php endif;?>
		<?php
		if ($supportBotId && isset($_REQUEST['support_chat']))
			echo 'BX.addCustomEvent("onImInit", function(BXIM) {BXIM.openMessenger('.$supportBotId.');});';
		?>
	</script>
<?php
if ($arResult['CAN_HAVE_HELP_NOTIFICATIONS'] === 'Y')
{
	//if something getting wrong in JS - this parameter can actualize script
	$scriptCacheTime = 259200; //script lifetime 60 * 60 * 24 * 3 = 72h
	$managerScriptUrl = $arResult['HELPDESK_URL'].
		'/bitrix/js/update_actual/help/notification/manager.js?'.
		(floor(time() / $scriptCacheTime));

	$jsNotificationParams = [
		'lastCheckNotificationsTime' => $arResult['LAST_CHECK_NOTIFICATIONS_TIME'],
		'currentNotificationsString' => $arResult['CURRENT_HELP_NOTIFICATIONS'],
		'managerScriptUrl' => $managerScriptUrl,
		'maxScriptCacheTime' => $scriptCacheTime,
		'timeNow' => time(),
	];
	\CJSCore::Init(['intranet.helper.notification']);
	?>
	<script>
		BX.ready(function() {
			BX.Intranet.Helper.Notification.Kernel.initLoader(<?= Json::encode($jsNotificationParams) ?>);
		});
	</script>
	<?php
}

$frame->end();

if ($imBarExists)
{
	$this->endViewTarget();
}