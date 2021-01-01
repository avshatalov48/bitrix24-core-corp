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
		'MARKETPLACE_TRIGGER_PLACEMENT' => 'CRM_ROBOT_TRIGGERS',
		'ROBOTS_LIMIT' => $arResult['ROBOTS_LIMIT'],
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

		var OLTriggerDialogHandler = function(trigger, form)
		{
			form.appendChild(BX.create("span", {
				attrs: { className: "bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete" },
				text: BX.message('CRM_AUTOMATION_CMP_OPENLINE_MESSAGE_TEXT_CONDITION') + ':'
			}));

			form.appendChild(BX.create("div", {
				attrs: { className: "bizproc-automation-popup-settings" },
				children: [BX.create("input", {
					attrs: {
						className: "bizproc-automation-popup-input",
						name: 'msg_text',
						value: trigger.data['APPLY_RULES']['msg_text'] || ''
					}
				})]
			}));
		};

		var OLTriggerSaveHandler = function(trigger, formData)
		{
			trigger.data['APPLY_RULES'] = {
				msg_text:  formData['data']['msg_text'],
				config_id:  formData['data']['config_id']
			}
		};

		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-OPENLINE_MSG', OLTriggerDialogHandler);
		BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-OPENLINE_MSG', OLTriggerSaveHandler);

		(function()//OPENLINE_ANSWER
		{
			var triggerDialogHandler = function(trigger, form)
			{
				var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);

				if (triggerData && triggerData['CONFIG_LIST'])
				{
					var select = BX.create('select', {
						attrs: {className: 'bizproc-automation-popup-settings-dropdown'},
						props: {
							name: 'config_id',
							value: ''
						},
						children: [BX.create('option', {
							props: {value: ''},
							text: BX.message('BIZPROC_AUTOMATION_TRIGGER_WEBFORM_ANY')
						})]
					});

					for (var i = 0; i < triggerData['CONFIG_LIST'].length; ++i)
					{
						var item = triggerData['CONFIG_LIST'][i];
						select.appendChild(BX.create('option', {
							props: {value: item['ID']},
							text: item['NAME']
						}));
					}
					if (BX.type.isPlainObject(trigger.data['APPLY_RULES']) && trigger.data['APPLY_RULES']['config_id'])
					{
						select.value = trigger.data['APPLY_RULES']['config_id'];
					}

					var div = BX.create('div', {attrs: {className: 'bizproc-automation-popup-settings'},
						children: [BX.create('span', {attrs: {
								className: 'bizproc-automation-popup-settings-title'
							}, text: BX.message('BIZPROC_AUTOMATION_TRIGGER_OPENLINE_LABEL') + ':'}), select]
					});
					form.appendChild(div);
				}
			};

			var triggerSaveHandler = function(trigger, formData)
			{
				trigger.data['APPLY_RULES'] = {
					config_id:  formData['data']['config_id']
				}
			};

			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-OPENLINE_ANSWER', triggerDialogHandler);
			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-OPENLINE_ANSWER', triggerSaveHandler);
		})();


		(function(){
			var code = 'FIELD_CHANGED';
			var menuId = 'FIELD_CHANGED' + Math.random();

			var renderFieldCheckbox = function(field, listNode)
			{
				var exists = listNode.querySelector('[data-field="'+field['Id']+'"]');
				if (exists)
				{
					return;
				}

				var node = BX.create("div", {
					attrs: {
						className: "bizproc-automation-popup-checkbox-item",
						'data-field': field['Id']
					},
					children: [
						BX.create("label", {
							attrs: { className: "bizproc-automation-popup-chk-label" },
							children: [
								BX.create("input", {
									attrs: {
										className: 'bizproc-automation-popup-chk',
										type: "checkbox",
										name: "fields[]",
										value: field['Id']
									},
									props: {
										checked: true
									}
								}),
								document.createTextNode(field['Name'])
							]
						})
					]
				});

				listNode.appendChild(node);
			}

			var fieldSelectorHandler = function(targetNode, trigger, listNode)
			{
				if (BX.PopupMenu.getMenuById(menuId))
				{
					return BX.PopupMenu.getMenuById(menuId).show();
				}

				var menuItems = [];

				trigger.component.data['DOCUMENT_FIELDS'].forEach(function(field)
				{
					var fieldId = field['Id'];

					if (
						fieldId === 'ID' ||
						fieldId === 'LEAD_ID' ||
						fieldId === 'DEAL_ID' ||
						fieldId === 'CONTACT_ID' ||
						fieldId === 'CONTACT_IDS' ||
						fieldId === 'COMPANY_ID' ||
						fieldId === 'CREATED_BY_ID' ||
						fieldId === 'MODIFY_BY_ID' ||
						fieldId === 'DATE_CREATE' ||
						fieldId === 'DATE_MODIFY' ||
						fieldId === 'WEBFORM_ID' ||
						fieldId === 'STATUS_ID' ||
						fieldId === 'STAGE_ID' ||
						fieldId === 'CATEGORY_ID' ||
						fieldId === 'ORIGINATOR_ID' ||
						fieldId === 'ORIGIN_ID' ||
						fieldId.indexOf('EVENT_') === 0 ||
						fieldId.indexOf('OPPORTUNITY') >= 0 ||
						fieldId.indexOf('CURRENCY_ID') >= 0 ||
						fieldId.indexOf('ASSIGNED_BY') >= 0 ||
						fieldId.indexOf('.') >= 0 ||
						fieldId.indexOf('_PRINTABLE') >= 0 ||
						field['Type'] === 'phone' ||
						field['Type'] === 'web' ||
						field['Type'] === 'email' ||
						field['Type'] === 'im' ||
						fieldId.indexOf('PHONE_') === 0 ||
						fieldId.indexOf('WEB_') === 0 ||
						fieldId.indexOf('EMAIL_') === 0 ||
						fieldId.indexOf('IM_') === 0
					)
					{
						return;
					}

					menuItems.push({
						text: BX.util.htmlspecialchars(field['Name']),
						field: field,
						onclick: function(e, item)
						{
							renderFieldCheckbox(item.field, listNode);
							this.popupWindow.close();
						}
					});
				});

				BX.PopupMenu.show(
					menuId,
					targetNode,
					menuItems,
					{
						autoHide: true,
						offsetLeft: (BX.pos(this)['width'] / 2),
						angle: { position: 'top', offset: 0 },
						zIndex: 200,
						className: 'bizproc-automation-inline-selector-menu',
						events: {
							onPopupClose: function(popup)
							{
								popup.destroy();
							}
						}
					}
				);
			}

			var show = function(trigger, form)
			{
				form.appendChild(BX.create("span", {
					attrs: { className: "bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete" },
					text: BX.message('CRM_AUTOMATION_CMP_FIELD_CHANGED_FIELDS') + ':'
				}));

				var fieldListNode = BX.create("div", {
					attrs: { className: "bizproc-automation-popup-checkbox" },
					children: []
				});

				form.appendChild(fieldListNode);

				form.appendChild(BX.create("div", {
					attrs: { className: "bizproc-automation-popup-settings bizproc-automation-popup-settings-text" },
					children: [BX.create("span", {
						attrs: {
							className: "bizproc-automation-popup-settings-link"
						},
						text: BX.message('CRM_AUTOMATION_CMP_FIELD_CHANGED_FIELDS_CHOOSE'),
						events: {
							click: function(){ fieldSelectorHandler(this, trigger, fieldListNode); }
						}
					})]
				}));

				var fields = trigger.data['APPLY_RULES']['fields'] || [];
				trigger.component.data['DOCUMENT_FIELDS'].forEach(function(field)
				{
					if (fields.includes((field['Id'])))
					{
						renderFieldCheckbox(field, fieldListNode);
					}
				});
			};

			var save = function(trigger, formData)
			{
				trigger.data['APPLY_RULES'] = {
					fields:  formData['data']['fields']
				}
			};

			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-'+code, show);
			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-'+code, save);
		})();

		(function()
		{
			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-FILL_TRACKNUM', function(trigger, form)
				{
					var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);
					if (triggerData && triggerData['DELIVERY_LIST'])
					{
						var select = BX.create('select', {
							attrs: {className: 'bizproc-automation-popup-settings-dropdown'},
							props: {
								name: 'DELIVERY_ID',
								value: ''
							},
							children: [BX.create('option', {
								props: {value: ''},
								text: BX.message('CRM_AUTOMATION_CMP_FILL_TRACKNUM_DELIVERY_ANY')
							})]
						});

						for (var i = 0; i < triggerData['DELIVERY_LIST'].length; ++i)
						{
							var item = triggerData['DELIVERY_LIST'][i];
							select.appendChild(BX.create('option', {
								props: {value: item['id']},
								text: item['name']
							}));
						}
						if (trigger.data['APPLY_RULES']['DELIVERY_ID'])
						{
							select.value = trigger.data['APPLY_RULES']['DELIVERY_ID'];
						}

						var div = BX.create('div', {attrs: {className: 'bizproc-automation-popup-settings'},
							children: [BX.create('span', {attrs: {
									className: 'bizproc-automation-popup-settings-title'
								}, text: BX.message('CRM_AUTOMATION_CMP_FILL_TRACKNUM_DELIVERY') + ':'}), select]
						});
						form.appendChild(div);
					}
				}
			);

			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-FILL_TRACKNUM', function(trigger, formData)
			{
				trigger.data['APPLY_RULES'] = {
					DELIVERY_ID:  formData['data']['DELIVERY_ID']
				}
			});

		})();

		(function() //SHIPMENT_CHANGED
		{
			var selector;
			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-SHIPMENT_CHANGED', function(trigger, form)
				{
					var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);
					if (triggerData && triggerData['FIELDS'])
					{
						var conditionGroup = new BX.Bizproc.Automation.ConditionGroup(
							trigger.data['APPLY_RULES']['shipmentCondition']
						);
						selector = new BX.Bizproc.Automation.ConditionGroupSelector(conditionGroup, {
							fields: triggerData['FIELDS'],
							fieldPrefix: 'shipping_condition_'
						});

						var selectorDiv =  BX.create("div", {
							attrs: { className: "bizproc-automation-popup-settings" },
							children: [
								BX.create("div", {
									attrs: { className: "bizproc-automation-popup-settings-block" },
									children: [
										BX.create("span", {
											attrs: { className: "bizproc-automation-popup-settings-title" },
											text: BX.message('CRM_AUTOMATION_CMP_SHIPMENT_CHANGED_CONDITION') + ":"
										}),
										selector.createNode()
									]
								})
							]
						});

						form.appendChild(selectorDiv);
					}
				}
			);

			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-SHIPMENT_CHANGED', function(trigger, formData)
			{
				var conditionGroup = BX.Bizproc.Automation.ConditionGroup.createFromForm(
					formData['data'],
					'shipping_condition_'
				);

				trigger.data['APPLY_RULES'] = {
					shipmentCondition:  conditionGroup.serialize()
				}

				if (selector && BX.type.isFunction(selector.destroy))
				{
					selector.destroy();
				}
			});
		})();

		(function() //OPENLINE_ANSWER_CTRL
		{
			var selector;
			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-OPENLINE_ANSWER_CTRL', function(trigger, form)
				{
					var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);
					if (triggerData && triggerData['FIELDS'])
					{
						var conditionGroup = new BX.Bizproc.Automation.ConditionGroup(
							trigger.data['APPLY_RULES']['imolCondition']
						);
						selector = new BX.Bizproc.Automation.ConditionGroupSelector(conditionGroup, {
							fields: triggerData['FIELDS'],
							fieldPrefix: 'imol_condition_'
						});

						var selectorDiv =  BX.create("div", {
							attrs: { className: "bizproc-automation-popup-settings" },
							children: [
								BX.create("div", {
									attrs: { className: "bizproc-automation-popup-settings-block" },
									children: [
										BX.create("span", {
											attrs: { className: "bizproc-automation-popup-settings-title" },
											text: BX.message('CRM_AUTOMATION_CMP_OPENLINE_ANSWER_CTRL_CONDITION') + ":"
										}),
										selector.createNode()
									]
								})
							]
						});

						form.appendChild(selectorDiv);
					}
				}
			);

			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-OPENLINE_ANSWER_CTRL', function(trigger, formData)
			{
				var conditionGroup = BX.Bizproc.Automation.ConditionGroup.createFromForm(
					formData['data'],
					'imol_condition_'
				);

				trigger.data['APPLY_RULES'] = {
					imolCondition:  conditionGroup.serialize()
				}

				if (selector && BX.type.isFunction(selector.destroy))
				{
					selector.destroy();
				}
			});
		})();

		(function()//TASK_STATUS
		{
			var selector;
			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onOpenSettingsDialog-TASK_STATUS', function(trigger, form)
				{
					var triggerData = trigger.manager.getAvailableTrigger(trigger.data['CODE']);
					if (triggerData && triggerData['STATUS_LIST'])
					{
						var select = BX.create('select', {
							attrs: {className: 'bizproc-automation-popup-settings-dropdown'},
							props: {
								name: 'STATUS_ID',
								value: ''
							},
							children: [BX.create('option', {
								props: {value: ''},
								text: BX.message('CRM_AUTOMATION_CMP_TASK_STATUS_ANY')
							})]
						});

						for (var i = 0; i < triggerData['STATUS_LIST'].length; ++i)
						{
							var item = triggerData['STATUS_LIST'][i];
							select.appendChild(BX.create('option', {
								props: {value: item['id']},
								text: item['name']
							}));
						}
						if (trigger.data['APPLY_RULES']['taskStatus'])
						{
							select.value = trigger.data['APPLY_RULES']['taskStatus'];
						}

						var div = BX.create('div', {attrs: {className: 'bizproc-automation-popup-settings'},
							children: [BX.create('span', {attrs: {
									className: 'bizproc-automation-popup-settings-title'
								}, text: BX.message('CRM_AUTOMATION_CMP_TASK_STATUS_LABEL') + ':'}), select]
						});
						form.appendChild(div);
					}
					if (triggerData && triggerData['FIELDS'])
					{
						var conditionGroup = new BX.Bizproc.Automation.ConditionGroup(
							trigger.data['APPLY_RULES']['taskCondition']
						);
						selector = new BX.Bizproc.Automation.ConditionGroupSelector(conditionGroup, {
							fields: triggerData['FIELDS'],
							fieldPrefix: 'task_condition_'
						});

						var selectorDiv =  BX.create("div", {
							attrs: { className: "bizproc-automation-popup-settings" },
							children: [
								BX.create("div", {
									attrs: { className: "bizproc-automation-popup-settings-block" },
									children: [
										BX.create("span", {
											attrs: { className: "bizproc-automation-popup-settings-title" },
											text: BX.message('CRM_AUTOMATION_CMP_TASK_STATUS_CONDITION') + ":"
										}),
										selector.createNode()
									]
								})
							]
						});

						form.appendChild(selectorDiv);
					}
				}
			);

			BX.addCustomEvent('BX.Bizproc.Automation.TriggerManager:onSaveSettings-TASK_STATUS', function(trigger, formData)
			{
				var conditionGroup = BX.Bizproc.Automation.ConditionGroup.createFromForm(
					formData['data'],
					'task_condition_'
				);

				trigger.data['APPLY_RULES'] = {
					taskStatus: formData['data']['STATUS_ID'],
					taskCondition:  conditionGroup.serialize()
				}
				if (selector && BX.type.isFunction(selector.destroy))
				{
					selector.destroy();
				}
			});
		})();
	});
</script>