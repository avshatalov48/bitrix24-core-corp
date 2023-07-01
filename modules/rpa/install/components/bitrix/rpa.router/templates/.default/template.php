<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if ($errors = $this->getComponent()->getErrors()):
	ShowError(reset($errors)->getMessage());
	return;
endif;

\Bitrix\Main\UI\Extension::load(['rpa.manager']);

?>
<script>
	BX.ready(function()
	{
		BX.Rpa.Manager.Instance.setUrlTemplates(<?=CUtil::PhpToJSObject($arResult['urlTemplates'])?>);
		<?php
		if (empty(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME')))
		{
		\Bitrix\Main\UI\Extension::load(['sidepanel']);
		?>
		BX.SidePanel.Instance.bindAnchors({
			rules:
				[
					{
						condition: [
							"/rpa/automation/(\\d+)/addrobot/"
						],
						options: {
							width: 556,
							cacheable: false,
							allowChangeHistory: false
						}
					},
					{
						condition: [
							"/rpa/automation/(\\d+)/editrobot/"
						],
						options: {
							cacheable: false,
							allowChangeHistory: false
						}
					},
					{
						condition: [
							"/rpa/automation",
						],
						options: {
							cacheable: false,
							customLeftBoundary: 0,
							loader: 'bizproc:automation-loader',
						}
					},
					{
						condition: [
							"/rpa/type/(\\d+)/fields/",
						],
						options: {
							cacheable: false
						}
					},
					{
						condition: [
							"/rpa/type/(\\d+)/field/(\\d+)/",
						],
						options: {
							cacheable: false,
							width: 900,
						}
					},
					{
						condition: [
							"/rpa/type",
						],
						options: {
							width: 702,
							cacheable: false
						}
					},
					{
						condition: [
							"/rpa/stages/(\\d+)",
						],
						options: {
							cacheable: false
						}
					},
					{
						condition: [
							"/rpa/item",
						],
						options: {
							cacheable: false
						},
					},
					{
						condition: [
							"/rpa/task/",
						],
						options: {
							width: 580,
							cacheable: false,
							allowChangeHistory: false
						},
					},
					{
						condition: [
							"/rpa/feedback/",
						],
						options: {
							width: 735
						},
					}
				]
		});
		<?php
		}
		?>
		var taskCountersPullTag = '<?=CUtil::JSEscape($arResult['taskCountersPullTag']);?>';
		if(taskCountersPullTag && BX.PULL)
		{
			BX.PULL.subscribe({
				moduleId: 'rpa',
				command: taskCountersPullTag,
				callback: function(params)
				{
					var update = params.counter;
					var topCounterNode = document.querySelector('#rpa_rpa_top_panel_tasks .main-buttons-item-counter');
					if(topCounterNode)
					{
						var counter = parseInt(topCounterNode.innerText);
						if(!BX.type.isNumber(counter))
						{
							counter = 0;
						}
						if(update === '+1')
						{
							counter++;
						}
						else if(update === '-1')
						{
							counter--;
						}
						topCounterNode.innerText = counter;
						if(counter <= 0)
						{
							topCounterNode.style.display = 'none';
						}
						else
						{
							topCounterNode.style.display = 'flex';
						}
					}
				}
			});

			BX.PULL.extendWatch(taskCountersPullTag);
		}
	});
</script>
<?php
global $APPLICATION;

$wrapperParameters = [
	'POPUP_COMPONENT_NAME' => $arResult['componentName'],
	'POPUP_COMPONENT_TEMPLATE_NAME' => $arResult['templateName'],
	'POPUP_COMPONENT_PARAMS' => $arResult['componentParameters'],
	"USE_PADDING" => false,
];

if ($arResult['componentName'] === 'bitrix:rpa.automation')
{
	$wrapperParameters['USE_BACKGROUND_CONTENT'] = false;
	$wrapperParameters['POPUP_COMPONENT_USE_BITRIX24_THEME'] = 'Y';
	$wrapperParameters['DEFAULT_THEME_ID'] = 'light:robots';
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	$wrapperParameters,
	$this->getComponent()
);?>