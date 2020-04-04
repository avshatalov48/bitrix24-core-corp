<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

$titleView = $arResult['ENTITY_CAPTION'] ? GetMessage('CRM_AUTOMATION_CMP_TITLE_'.$arResult['ENTITY_TYPE_NAME'].'_VIEW', array(
		'#TITLE#' => $arResult['ENTITY_CAPTION']
)) : ' ';
$titleEdit = GetMessage('CRM_AUTOMATION_CMP_TITLE_'.$arResult['ENTITY_TYPE_NAME'].'_EDIT');

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
		'MARKETPLACE_TRIGGER_PLACEMENT' => 'CRM_ROBOT_TRIGGERS'
], $this);
?>
<script>
	BX.ready(function()
	{
		var entityTypeId = <?=(int)$arResult['ENTITY_TYPE_ID']?>;
		var entityId = <?=(int)$arResult['ENTITY_ID']?>;
		var onStatusChange = function(progressControl, data)
		{
			if (data && data['VALUE'])
			{
				var cmp = BX.getClass('BX.Bizproc.Automation.Designer.component');
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
				var cmp = BX.getClass('BX.Bizproc.Automation.Designer.component');
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

		var DocumentTriggerDialogHandler = function(trigger, form)
		{
			var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);

			if (triggerData && triggerData['TEMPLATE_LIST'])
			{
				var select = BX.create('select', {
					attrs: {className: 'bizproc-automation-popup-settings-dropdown'},
					props: {
						name: 'TEMPLATE_ID',
						value: ''
					},
					children: [BX.create('option', {
						props: {value: ''},
						text: BX.message('BIZPROC_AUTOMATION_TRIGGER_WEBFORM_ANY')
					})]
				});

				for (var i = 0; i < triggerData['TEMPLATE_LIST'].length; ++i)
				{
					var item = triggerData['TEMPLATE_LIST'][i];
					select.appendChild(BX.create('option', {
						props: {value: item['ID']},
						text: item['NAME']
					}));
				}
				if (BX.type.isPlainObject(trigger.data['APPLY_RULES']) && trigger.data['APPLY_RULES']['TEMPLATE_ID'])
				{
					select.value = trigger.data['APPLY_RULES']['TEMPLATE_ID'];
				}

				var div = BX.create('div', {attrs: {className: 'bizproc-automation-popup-settings'},
					children: [BX.create('span', {attrs: {
							className: 'bizproc-automation-popup-settings-title'
						}, text: triggerData['TEMPLATE_LABEL'] + ':'}), select]
				});
				form.appendChild(div);
			}
		};

		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-DOCUMENT_VIEW', DocumentTriggerDialogHandler);
		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-DOCUMENT_CREATE', DocumentTriggerDialogHandler);

		var DocumentTriggerSaveHandler = function(trigger, formData)
		{
			trigger.data['APPLY_RULES'] = {
				TEMPLATE_ID:  formData['data']['TEMPLATE_ID']
			}
		};

		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-DOCUMENT_VIEW', DocumentTriggerSaveHandler);
		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-DOCUMENT_CREATE', DocumentTriggerSaveHandler);

		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-MISSED_CALL', function(trigger, form)
			{
				var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);
				if (triggerData && triggerData['LINES'])
				{
					var select = BX.create('select', {
						attrs: {className: 'bizproc-automation-popup-settings-dropdown'},
						props: {
							name: 'LINE_NUMBER',
							value: ''
						},
						children: [BX.create('option', {
							props: {value: ''},
							text: BX.message('BIZPROC_AUTOMATION_TRIGGER_WEBFORM_ANY')
						})]
					});

					for (var i = 0; i < triggerData['LINES'].length; ++i)
					{
						var item = triggerData['LINES'][i];
						select.appendChild(BX.create('option', {
							props: {value: item['LINE_NUMBER']},
							text: item['SHORT_NAME']
						}));
					}
					if (trigger.data['APPLY_RULES']['LINE_NUMBER'])
					{
						select.value = trigger.data['APPLY_RULES']['LINE_NUMBER'];
					}

					var div = BX.create('div', {attrs: {className: 'bizproc-automation-popup-settings'},
						children: [BX.create('span', {attrs: {
								className: 'bizproc-automation-popup-settings-title'
							}, text: BX.message('BIZPROC_AUTOMATION_TRIGGER_CALL_LABEL') + ':'}), select]
					});
					form.appendChild(div);
				}
			}
		);

		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-MISSED_CALL', function(trigger, formData)
		{
			trigger.data['APPLY_RULES'] = {
				LINE_NUMBER:  formData['data']['LINE_NUMBER']
			}
		});
	});
</script>