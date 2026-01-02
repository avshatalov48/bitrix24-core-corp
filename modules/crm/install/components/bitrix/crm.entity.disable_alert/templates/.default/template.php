<?php

use Bitrix\Crm\Component\Utils\OldEntityViewDisableHelper;
use Bitrix\Main\Web\Json;

/** @var array $arResult */

Bitrix\Main\UI\Extension::load('crm.old-entity-view.disable-alert');
?>

<?php if (OldEntityViewDisableHelper::canShowAlert()): ?>
	<div id="crm-entity-disable-alert"></div>
	<script>
		BX.ready(() => {
			const alertContainer = document.getElementById('crm-entity-disable-alert');
			const alert = new BX.Crm.OldEntityView.DisableAlert({
				alertContainer,
				...<?= Json::encode($arResult['jsParams']) ?>,
			});

			alert.render();
		});
	</script>
<?php endif; ?>
