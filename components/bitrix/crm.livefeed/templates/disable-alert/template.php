<?php

use Bitrix\Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult */

if (!is_null($arResult['LOG_ID'])):?>
<script>
	BX.ready(() => {
		const alertContainer = BX.Tag.render`<div class="<?=AvailabilityHelper::ALERT_SELECTOR_CLASS?>"></div>`;
		const log = document.querySelector('[data-livefeed-id="<?= $arResult['LOG_ID'] ?>"]');
		if (log)
		{
			BX.Dom.insertBefore(alertContainer, log);

			BX.Event.EventEmitter.emit('crm:disableLFAlertContainerRendered', {
				container: alertContainer,
			});
		}
	});
</script>
<?php endif; ?>
