<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @var $arResult array
 */

\Bitrix\Main\UI\Extension::load([
	'intranet.widget-loader',
]);

if ($arResult['invitationCounter'] > 0)
{
	\Bitrix\Main\UI\Extension::load(['intranet.invitation-widget-counter']);
}
?>
<button data-id='invitationButton' class="ui-btn ui-btn-round license-btn license-btn-primary">
	<span class="invitation-widget-counter"></span>
	<?=Loc::getMessage('INTRANET_INVITATION_WIDGET_INVITE')?>
</button>
<?php
$frame = $this->createFrame()->begin('');
?>

<script>
	BX.ready(() => {
		const button  = document.querySelector("[data-id='invitationButton']");
		<?php if ($arResult['invitationCounter'] > 0): ?>
			BX.Intranet.InvitationWidgetCounter.getInstance().setOptions({
				button: button,
				counterId: '<?= $arResult['counterId'] ?? '' ?>',
				invitationCounter: <?= $arResult['invitationCounter'] ?? 0 ?>,
				shouldShowStructureCounter: <?= $arResult['shouldShowStructureCounter'] ? 'true' : 'false' ?>,
			}).show();
		<?php endif; ?>
		BX.Event.EventEmitter.subscribe('onPullEvent-main', (event) => {
			const [command, params] = event.getCompatData();

			if (command === 'user_counter' && params[BX.message('SITE_ID')])
			{
				const counters = BX.clone(params[BX.message('SITE_ID')]);
				let value = counters['<?= $arResult['counterId'] ?>'];

				if (value > 0)
				{
					BX.Runtime.loadExtension('intranet.invitation-widget-counter').then(() => {
						BX.Intranet.InvitationWidgetCounter.getInstance().setOptions({
							button: button,
							counterId: '<?= $arResult['counterId'] ?? '' ?>',
							invitationCounter: <?= $arResult['invitationCounter'] ?? 0 ?>,
							shouldShowStructureCounter: <?= $arResult['shouldShowStructureCounter'] ? 'true' : 'false' ?>,
						}).onReceiveCounterValue(value);
					});
				}
			}
		});
		BX.Event.bind(button, 'click', () => {
			BX.unbindAll(button);
			const widgetLoader = new BX.Intranet.WidgetLoader({
				bindElement: button,
				width: 350,
				id: 'bx-invitation-header-popup',
			});
			widgetLoader.createSkeletonFromConfig(<?= Json::encode($arResult['skeleton']) ?>)
				.show();
			const dataPromise = BX.ajax.runAction("intranet.invitationwidget.getData", {
				data: {},
				analyticsLabel: {
					headerPopup: "Y"
				}
			});
			BX.Runtime.loadExtension(['intranet.invitation-widget']).then(() => {
				dataPromise.then((response) => {
					widgetLoader.clearBeforeInsertContent();
					BX.Intranet.InvitationWidget.getInstance().setOptions({
						loader: widgetLoader.getPopup(),
						button: document.querySelector("[data-id='invitationButton']"),
						...response.data,
					}).show();
				});
			});
		});
	});
</script>

<?php $frame->end(); ?>
