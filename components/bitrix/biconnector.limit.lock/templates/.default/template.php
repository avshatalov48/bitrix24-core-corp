<?php
/**
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.banner-dispatcher',
]);

?>
<script>
	BX.ready(() => {
		new BX.BIConnector.LimitLockPopup({
			title: '<?= CUtil::JSEscape($arResult['TITLE']) ?>',
			content: '<?= CUtil::JSEscape($arResult['CONTENT']) ?>',
			licenseButtonText: '<?= CUtil::JSEscape($arResult['LICENSE_BUTTON_TEXT']) ?>',
			laterButtonText: '<?= CUtil::JSEscape($arResult['LATER_BUTTON_TEXT']) ?>',
			licenseUrl: '<?= CUtil::JSEscape($arResult['LICENSE_URL']) ?>',
			popupClassName: 'biconnector-limit-lock',
			fullLock: '<?= CUtil::JSEscape($arResult['FULL_LOCK']) ?>',
		});
	})
</script>
