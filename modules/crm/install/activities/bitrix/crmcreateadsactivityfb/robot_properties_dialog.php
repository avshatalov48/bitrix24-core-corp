<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$runtimeData = $dialog->getRuntimeData();
$clientId = $runtimeData['CLIENT_ID'];
$accountId = $runtimeData['ACCOUNT_ID'];
$audienceId = $runtimeData['AUDIENCE_ID'];
$autoRemoveDayNumber = $runtimeData['AUTO_REMOVE_DAY_NUMBER'];
$provider = $runtimeData['PROVIDER'];
$type = htmlspecialcharsbx($provider['TYPE']);

$containerNodeId = 'crm-robot-ads-container-' . $type;
$destroyEventName = 'crm-robot-ads-destroy';
?>

	<script>

		BX.ready(function ()
		{
			var dialog = BX.Bizproc.Automation.Designer.getInstance().getRobotSettingsDialog();
			if (!dialog)
			{
				return;
			}

			BX.remove(BX('<?=$containerNodeId?>'));
			var containerNode = BX.create('div');
			containerNode.id = '<?=$containerNodeId?>';
			dialog.form.appendChild(containerNode);

			BX.addCustomEvent(dialog.popup, 'onPopupClose', function(){
				BX.onCustomEvent(window, '<?=$destroyEventName?>');
			});
		});

	</script>

<?
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:seo.ads.retargeting',
	'',
	array(
		'CONTAINER_NODE_ID' => $containerNodeId,
		'PROVIDER' => $provider,
		'CLIENT_ID' => $clientId,
		'ACCOUNT_ID' => $accountId,
		'AUDIENCE_ID' => $audienceId,
		'AUTO_REMOVE_DAY_NUMBER' => $autoRemoveDayNumber,
		'JS_DESTROY_EVENT_NAME' => $destroyEventName
	)
);
?>