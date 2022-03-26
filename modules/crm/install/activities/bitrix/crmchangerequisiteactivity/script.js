;(function()
{
	"use strict";

	BX.namespace('BX.Crm.Activity');

	if(typeof BX.Crm.Activity.CrmChangeRequisiteActivity !== "undefined")
	{
		return;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity = {};

	BX.Crm.Activity.CrmChangeRequisiteActivity.init = function(params)
	{
		this.requisiteFieldsNode = BX(params.requisiteFieldsNodeId);

		this.bankDetailFieldsMap = params.bankDetailFieldsMap;
		this.addressFieldsMap = params.addressFieldsMap !== null ? params.addressFieldsMap : {};
		this.fieldsMap = Object.assign(params.requisiteFieldsMap, this.bankDetailFieldsMap, this.addressFieldsMap);

		this.formName = params.formName;
		this.currentValues = params.currentValues !== null ? params.currentValues : {};
		this.messages = params.messages;
		this.documentType = params.documentType;
		this.isRobot = params.isRobot;

		this.conditionFieldsCounter = 0;

		this.selectPresetNode = BX(params.selectPresetNodeId);
		this.preparePresetFieldNames(params);

		var addCondition = this.isRobot ? 'addRBPCondition' : 'addBPCondition';

		if(this.getObjectKeys(this.currentValues).length <= 0)
		{
			this[addCondition]();
		}
		for(var fieldId in this.currentValues)
		{
			this[addCondition](fieldId);
		}

		if(params.isRobot)
		{
			this.initRBPEvents();
		}
		else
		{
			this.initBPEvents(params);
			this.onSelectPresetNodeChange();
		}
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.preparePresetFieldNames = function (params)
	{
		this.presetFieldNames = params.presetFieldNames;
		this.presetFieldNames['0'] = this.getObjectKeys(this.fieldsMap);

		this.presetFieldNames.has = function(key)
		{
			return this.hasOwnProperty(key);
		};

		for(var i in this.presetFieldNames)
		{
			if(this.presetFieldNames.hasOwnProperty(i))
			{
				this.presetFieldNames[i].has = function (key)
				{
					return this.indexOf(key) !== -1;
				}
			}
		}
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.initRBPEvents = function(params)
	{
		this.fieldsListSelectNode = document.querySelector('[data-role="bca-ccra-fields-list"]')

		BX.bind(this.fieldsListSelectNode, 'click', BX.proxy(this.onFieldsListSelectClick, this));
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.onFieldsListSelectClick = function(event)
	{
		var menuId = 'bca-ccra-menu-' + this.conditionFieldsCounter;
		this.conditionFieldsCounter++;

		BX.PopupMenu.show(
			menuId,
			this.fieldsListSelectNode,
			this.getFieldsItems(),
			{
				autoHide: true,
				offsetLeft: (BX.pos(this.fieldsListSelectNode)['width'] / 2),
				angle: { position: 'top', offset: 0 },
				zIndex: 200,
				className: 'bizproc-automation-inline-selector-menu',
				events: {
					onPopupClose: function () {
						this.destroy();
					}
				}
			}
		);

		return event.preventDefault();
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getFieldsItems = function()
	{
		var preset = this.getPresetFieldIds();

		var fieldsItems = [];
		for(var fieldId in this.fieldsMap)
		{
			if(this.fieldsMap.hasOwnProperty(fieldId)
				&& (preset.has(fieldId)
					|| this.addressFieldsMap.hasOwnProperty(fieldId)
					|| this.bankDetailFieldsMap.hasOwnProperty(fieldId)
				)
			)
			{
				fieldsItems.push({
					text: this.fieldsMap[fieldId]['Name'],
					fieldId: fieldId,
					onclick: function (e, item) {
						this.popupWindow.close();
						BX.Crm.Activity.CrmChangeRequisiteActivity.addRBPCondition(item.fieldId);
					}
				});
			}
		}
		return fieldsItems;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.addRBPCondition = function(fieldId)
	{
		this.conditionFieldsCounter++;
		var conditionId = 'id_bca_ccra_row_id_' + this.conditionFieldsCounter;

		if(fieldId === undefined)
		{
			fieldId = this.getObjectKeys(this.fieldsMap)[0];
		}

		var titleNode = BX.create('span', {
			attrs: {className: "bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete"},
			text: this.fieldsMap[fieldId]['Name']
		});

		var deleteButtonNode = BX.create('a', {
			attrs: {
				className: 'bizproc-automation-popup-settings-delete bizproc-automation-popup-settings-link bizproc-automation-popup-settings-link-light',
			},
			props: {href: '#'},
			text: this.messages['CRM_CRA_DELETE_CONDITION']
		});

		BX.bind(deleteButtonNode, 'click', (function (event) {
			this.deleteCondition(conditionId);
			return event.preventDefault();
		}).bind(this));

		this.requisiteFieldsNode.appendChild(BX.create('div', {
			attrs: {
				className: "bizproc-automation-popup-settings",
			},
			props: {
				id: conditionId
			},
			children: [
				titleNode,
				this.renderField(fieldId),
				deleteButtonNode
			]
		}));
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.initBPEvents = function(params)
	{
		BX.bind(BX(params.selectPresetNodeId), 'change', this.onSelectPresetNodeChange.bind(this));
		BX.bind(BX(params.addConditionLinkId), 'click', this.onAddConditionLinkClick.bind(this));
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.onSelectPresetNodeChange = function ()
	{
		for(var i = 0; i < this.requisiteFieldsNode.rows.length; i++)
		{
			var row = this.requisiteFieldsNode.rows[i];
			this.hideFieldNamesOfUnselectedPresets(row.id);
		}
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.onAddConditionLinkClick = function(event)
	{
		this.addBPCondition();
		return event.preventDefault();
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.addBPCondition = function(fieldId)
	{
		this.conditionFieldsCounter++;

		var newRow = this.requisiteFieldsNode.insertRow(-1);
		newRow.id = 'id_bca_ccra_row_id_' + this.conditionFieldsCounter;

		if(fieldId === undefined)
		{
			fieldId = this.getObjectKeys(this.fieldsMap)[0];
		}

		newRow.insertCell(-1).appendChild(this.getFieldSelectNode(newRow, fieldId));
		newRow.insertCell(-1).appendChild(this.getEqualSignNode());
		newRow.insertCell(-1).appendChild(this.renderField(fieldId));
		newRow.insertCell(-1).appendChild(this.getDeleteButtonNode(newRow));
		this.hideFieldNamesOfUnselectedPresets(newRow.id);
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.hideFieldNamesOfUnselectedPresets = function (rowId)
	{
		var preset = this.getPresetFieldIds();

		var selectFieldNode = BX(rowId).firstChild.firstChild;
		var inputFieldNode = BX(rowId).children[2];

		for(var i = 0; i < selectFieldNode.length; i++)
		{
			var option = selectFieldNode[i];
			if(preset.has(option.value)
				|| this.bankDetailFieldsMap.hasOwnProperty(option.value)
				|| this.addressFieldsMap.hasOwnProperty(option.value))
			{
				option.removeAttribute('hidden', 'hidden');
			}
			else
			{
				option.setAttribute('hidden', 'hidden');
			}
		}

		if(preset.has(selectFieldNode.value) === false
			&& this.bankDetailFieldsMap.hasOwnProperty(selectFieldNode.value) === false
			&& this.addressFieldsMap.hasOwnProperty(option.value) === false)
		{
			for(i = 0; i < selectFieldNode.length; i++)
			{
				option = selectFieldNode[i];
				if(option.hasAttribute('hidden') === false)
				{
					selectFieldNode.value = option.value;
					BX(rowId).replaceChild(this.renderField(selectFieldNode.value), inputFieldNode);
					break;
				}
			}
		}
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getObjectKeys = function(obj)
	{
		var objectKeys = [];
		for(var key in obj)
		{
			if(obj.hasOwnProperty(key))
			{
				objectKeys.push(key);
			}
		}
		return objectKeys;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getFieldSelectNode = function(row, selectedFieldName)
	{
		var select = BX.create('select', {
			children: this.getOptionsFromFieldsMap(selectedFieldName)
		});

		select.onchange = BX.proxy(
			function()
			{
				var inputNode = row.children[2];
				row.replaceChild(this.renderField(select.value), inputNode);
			},
			this
		);
		return select;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getPresetFieldIds = function ()
	{
		var presetId = this.selectPresetNode.value;
		if(this.presetFieldNames.has(presetId) === false)
		{
			presetId = '0';
		}
		return this.presetFieldNames[presetId];
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getOptionsFromFieldsMap = function(fieldId)
	{
		var options = [];
		var objectKeys = this.getObjectKeys(this.fieldsMap);
		for(var i = 0; i < objectKeys.length; i++)
		{
			var name = objectKeys[i];
			var attrs = fieldId === name ? {selected: 'selected'} : undefined;
			options.push(BX.create('option', {
				props: {value: name},
				attrs: attrs,
				text: this.fieldsMap[name]['Name']
			}));
		}

		return options;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getEqualSignNode = function()
	{
		return BX.create('span', {
			text: '='
		});
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getFieldNameByRowId = function(id)
	{
		var row = document.getElementById(id);
		var selectNode = row.cells[0].firstChild;

		return selectNode.selectedOptions[0].value;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.renderField = function(fieldId)
	{
		return BX.Bizproc.FieldType.renderControl(
			this.documentType,
			this.fieldsMap[fieldId],
			fieldId,
			this.currentValues[fieldId],
			this.isRobot ? 'public' : 'designer'
		);
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.getDeleteButtonNode = function(row)
	{
		var deleteButtonNode = BX.create('a', {
			props: {href: '#'},
			text: this.messages['CRM_CRA_DELETE_CONDITION']
		});

		BX.bind(deleteButtonNode, 'click', (function (event) {
			this.deleteCondition(row.id);
			event.preventDefault();
		}).bind(this))


		return deleteButtonNode;
	}

	BX.Crm.Activity.CrmChangeRequisiteActivity.deleteCondition = function(rowId)
	{
		var row = document.getElementById(rowId);
		row.parentNode.removeChild(row);
	}
})();