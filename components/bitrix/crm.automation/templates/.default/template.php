<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arResult */

$titleView = $arResult['ENTITY_CAPTION'] ? GetMessage('CRM_AUTOMATION_CMP_TITLE_'.$arResult['ENTITY_TYPE_NAME'].'_VIEW', array(
		'#TITLE#' => $arResult['ENTITY_CAPTION']
)) : ' ';

//todo get correct phrase for spa

$titleEdit = GetMessage('CRM_AUTOMATION_CMP_TITLE_'.$arResult['ENTITY_TYPE_NAME'].'_EDIT');

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

global $APPLICATION;

$APPLICATION->IncludeComponent('bitrix:bizproc.automation', '', [
		'DOCUMENT_TYPE' => \CCrmBizProcHelper::ResolveDocumentType($arResult['ENTITY_TYPE_ID']),
		'DOCUMENT_ID' => $arResult['ENTITY_ID'] ? $arResult['ENTITY_TYPE_NAME'].'_'.$arResult['ENTITY_ID'] : null,
		'DOCUMENT_CATEGORY_ID' => $arResult['ENTITY_CATEGORY_ID'],
		'STATUSES_EDIT_URL' => $arResult['STATUSES_EDIT_URL'],
		'WORKFLOW_EDIT_URL' => $arResult['BIZPROC_EDITOR_URL'],
		'TITLE_VIEW' => $titleView,
		'TITLE_EDIT' => $titleEdit,
		'MARKETPLACE_ROBOT_CATEGORY' => 'crm_bots',
		'MARKETPLACE_TRIGGER_PLACEMENT' => 'CRM_ROBOT_TRIGGERS',
		'ROBOTS_LIMIT' => $arResult['ROBOTS_LIMIT'],
		'IS_TEMPLATES_SCHEME_SUPPORTED' => $arResult['IS_TEMPLATES_SCHEME_SUPPORTED'] ?? true,
		'CATEGORY_SELECTOR' => $arParams['~CATEGORY_SELECTOR'],
], $this);
?>
<script>
	BX.ready(function()
	{
		BX.message(<?=\Bitrix\Main\Web\Json::encode(\Bitrix\Main\Localization\Loc::loadLanguageFile(__file__))?>);

		var entityTypeId = <?=(int)$arResult['ENTITY_TYPE_ID']?>;
		var entityId = <?=(int)$arResult['ENTITY_ID']?>;
		var onStatusChange = function(progressControl, data)
		{
			if (data && data['VALUE'])
			{
				var designer = BX.getClass('BX.Bizproc.Automation.Designer') && BX.Bizproc.Automation.Designer.getInstance();
				var cmp = designer && designer.component;
				if (cmp)
				{
					cmp.setDocumentStatus(data['VALUE']);
					cmp.updateTracker();
				}
			}
		};

		var onEntityProgressChange = function(progressControl, data)
		{
			if (data.entityTypeId === entityTypeId && data.entityId === entityId && data.currentStepId)
			{
				var designer = BX.getClass('BX.Bizproc.Automation.Designer') && BX.Bizproc.Automation.Designer.getInstance();
				var cmp = designer && designer.component;
				if (cmp)
				{
					cmp.setDocumentStatus(data.currentStepId);
					//need to wait BX.Crm.EntityDetailProgressControl.save()
					setTimeout(cmp.updateTracker.bind(cmp), 300);
				}
			}
		};

		BX.addCustomEvent('CrmProgressControlAfterSaveSucces', onStatusChange);
		BX.addCustomEvent('Crm.EntityProgress.Change', onEntityProgressChange);
	});
</script>
