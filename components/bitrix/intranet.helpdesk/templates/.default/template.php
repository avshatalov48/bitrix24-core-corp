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

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
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

CJSCore::Init(['helper']);
$helpUrl = \Bitrix\UI\InfoHelper::getUrl('/widget2/', byLang: true);
$imBotIncluded = Loader::includeModule('imbot');
$frameOpenUrl = (new Main\Web\Uri($helpUrl))->addParams(['action' => 'open'])->getUri();
$frameCloseUrl = (new Main\Web\Uri($helpUrl))->addParams(['action' => 'close'])->getUri();
$notifyData = array_merge(\Bitrix\UI\InfoHelper::getParameters(), [
	'partner_link' => Option::get('bitrix24', 'partner_id', 0) ? 'Y' : 'N',
	'counter_update_date' => $arResult['COUNTER_UPDATE_DATE'] ?? 0
]);
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
		if (isset($notifyData['support_bot'], $_REQUEST['support_chat']) && ($notifyData['support_bot'] > 0))
			echo 'BX.addCustomEvent("onImInit", function(BXIM) {BXIM.openMessenger('.$notifyData['support_bot'].');});';
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