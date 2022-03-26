import {Reflection, Type, Event, Dom, Loc} from 'main.core';
import {MenuManager} from 'main.popup';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmUpdateDynamicActivity
{
	documentType: Array<string>;
	isRobot: boolean;
	entityTypeIdSelect: HTMLSelectElement;
	fieldsListSelect: HTMLSelectElement;
	entitiesFieldsContainers: HTMLDivElement | HTMLTableElement;

	filterFieldsContainer: HTMLDivElement | null;
	filteringFieldsPrefix: string;
	filterFieldsMap: Map<string, object>;
	conditionGroup: BX.Bizproc.Automation.ConditionGroup | undefined;

	currentEntityTypeId: number;
	fieldsMap: Map<string, object>;
	currentValues: Map<string, object>;

	constructor(options)
	{
		if (Type.isPlainObject(options))
		{
			this.documentType = options.documentType;
			this.isRobot = options.isRobot;
			const form = document.forms[options.formName];

			if (!Type.isNil(form))
			{
				this.entityTypeIdSelect = form.dynamic_type_id;
				this.currentEntityTypeId = this.entityTypeIdSelect.value;
				this.entityTypeDependentElements = document.querySelectorAll(
					'[data-role="bca-cuda-entity-type-id-dependent"]',
				);
			}

			if (this.isRobot)
			{
				this.fieldsListSelect = document.querySelector('[data-role="bca-cuda-fields-list"]');
			}
			else
			{
				this.addConditionButton = document.querySelector('[data-role="bca_cuda_add_condition"]');
			}
			this.entitiesFieldsContainers = document.querySelector('[data-role="bca-cuda-fields-container"]');
			this.conditinIdPrefix = 'id_bca_cuda_field_';

			this.fieldsMap = new Map(Object.entries(options.fieldsMap));

			this.filterFieldsContainer = document.querySelector('[data-role="bca-cuda-filter-fields-container"]');
			this.filteringFieldsPrefix = options.filteringFieldsPrefix;
			this.filterFieldsMap = new Map(Object.entries(options.filterFieldsMap));
			if (!Type.isNil(options.documentName))
			{
				BX.Bizproc.Automation.API.documentName = options.documentName;
			}
			if (BX.Bizproc.Automation && BX.Bizproc.Automation.ConditionGroup)
			{
				this.conditionGroup = new BX.Bizproc.Automation.ConditionGroup(options.conditions);
			}

			this.currentValues = new Map();
			Array
				.from(this.fieldsMap.keys())
				.forEach((entityTypeId) => this.currentValues.set(entityTypeId, {}));

			if (!Type.isNil(this.currentEntityTypeId) && Type.isObject(options.currentValues))
			{
				this.currentValues.set(this.currentEntityTypeId, options.currentValues);
			}
			this.render();
		}
	}

	init(): void
	{
		if (
			this.entityTypeIdSelect
			&& this.fieldsMap
			&& this.entitiesFieldsContainers
		)
		{
			Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
		}

		if (this.isRobot && this.fieldsListSelect)
		{
			Event.bind(this.fieldsListSelect, 'click', this.onFieldsListSelectClick.bind(this));
		}
		else if (!this.isRobot && this.addConditionButton)
		{
			Event.bind(this.addConditionButton, 'click', this.onAddConditionButtonClick.bind(this));
		}
	}

	onEntityTypeIdChange(): void
	{
		this.currentEntityTypeId = this.entityTypeIdSelect.value;

		Dom.clean(this.filterFieldsContainer);
		if (BX.Bizproc.Automation && BX.Bizproc.Automation.ConditionGroup)
		{
			this.conditionGroup = new BX.Bizproc.Automation.ConditionGroup();
		}

		Array
			.from(this.entitiesFieldsContainers.children)
			.forEach((elem) => Dom.remove(elem));

		this.render();
	}

	render(): void
	{
		if (Type.isNil(this.currentEntityTypeId) || this.currentEntityTypeId === '')
		{
			this.entityTypeDependentElements.forEach((element) => Dom.hide(element));
		}
		else
		{
			this.entityTypeDependentElements.forEach((element) => Dom.show(element));
			this.renderFilterFields();
			this.renderEntityFields();
		}
	}

	renderFilterFields(): void
	{
		if (
			!Type.isNil(this.conditionGroup)
			&& !Type.isNil(this.currentEntityTypeId)
		)
		{
			const selector = new BX.Bizproc.Automation.ConditionGroupSelector(this.conditionGroup, {
				fields: Object.values(this.filterFieldsMap.get(this.currentEntityTypeId)),
				fieldPrefix: this.filteringFieldsPrefix,
			});

			this.filterFieldsContainer.appendChild(selector.createNode());
		}
	}

	renderEntityFields(): void
	{
		Object
			.keys(this.currentValues.get(this.currentEntityTypeId))
			.forEach((fieldId) => this.addCondition(fieldId));
	}

	onFieldsListSelectClick(event)
	{
		const fields = this.fieldsMap.get(this.currentEntityTypeId);
		if (Type.isNil(fields))
		{
			return event.preventDefault();
		}

		const activity = this;
		const menuItems = Object.entries(fields).map(([fieldId, field]) => ({
			fieldId,
			text: field.Name,
			onclick(_, item)
			{
				this.popupWindow.close();
				activity.addCondition(item.fieldId);
			},
		}));

		const menuManagerOptions = {
			id: Math.random().toString(),
			bindElement: this.fieldsListSelect,
			items: Array.from(menuItems),
			autoHide: true,
			offsetLeft: Dom.getPosition(this.fieldsListSelect).width / 2,
			angle: {
				position: 'top',
				offset: 0,
			},
			zIndex: 200,
			className: 'bizproc-automation-inline-selector-menu',
			events: {
				onPopupClose()
				{
					this.destroy();
				},
			},
		};
		MenuManager.show(menuManagerOptions);

		return event.preventDefault();
	}

	onAddConditionButtonClick(event): void
	{
		const defaultFieldId = Object.keys(this.fieldsMap.get(this.currentEntityTypeId))[0];
		this.addCondition(defaultFieldId);

		return event.preventDefault();
	}

	addCondition(fieldId: string): void
	{
		if (this.isRobot)
		{
			this.addRobotCondition(fieldId);
		}
		else
		{
			this.addBizprocCondition(fieldId);
		}
	}

	addRobotCondition(fieldId: string): void
	{
		const conditionId = this.conditinIdPrefix + fieldId;

		const titleNode = Dom.create('span', {
			attrs: {
				className: 'bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete',
			},
			text: this.fieldsMap.get(this.currentEntityTypeId)[fieldId].Name,
		});

		const deleteButton = Dom.create('a', {
			attrs: {
				className: 'bizproc-automation-popup-settings-delete bizproc-automation-popup-settings-link bizproc-automation-popup-settings-link-light',
			},
			props: {href: '#'},
			text: Loc.getMessage('CRM_UDA_DELETE_CONDITION'),
			events: {
				// eslint-disable-next-line func-names
				click: function(event) {
					this.deleteCondition(fieldId);
					return event.preventDefault();
				}.bind(this),
			},
		});

		const fieldNode = Dom.create('div', {
			attrs: {
				className: 'bizproc-automation-popup-settings',
			},
			props: {id: conditionId},
			children: [
				titleNode,
				this.renderField(fieldId),
				deleteButton,
			],
		});

		this.entitiesFieldsContainers.appendChild(fieldNode);
	}

	deleteCondition(fieldId: string): void
	{
		const conditionId = this.conditinIdPrefix + fieldId;
		Dom.remove(document.getElementById(conditionId));
	}

	addBizprocCondition(fieldId: string): void
	{
		const newConditionRow = this.entitiesFieldsContainers.insertRow(-1);
		newConditionRow.id = this.conditinIdPrefix + Math.random().toString().substr(1, 5);

		const activity = this;

		const entityFieldSelect = Dom.create('select', {
			children: this.getCurrentFieldsOptions(fieldId),
			events: {
				change(event) {
					const fieldValueNode = newConditionRow.children[2];
					const newFieldId = event.srcElement.value;
					newConditionRow.replaceChild(activity.renderField(newFieldId), fieldValueNode);
				},
			},
		});

		const equalSignNode = Dom.create('span', {text: '='});

		const entityFieldValueNode = this.renderField(fieldId);

		const deleteConditionButton = Dom.create('a', {
			props: {href: '#'},
			text: Loc.getMessage('CRM_UDA_DELETE_CONDITION'),
			events: {
				click(event) {
					Dom.remove(document.getElementById(newConditionRow.id));
					event.preventDefault();
				},
			},
		});

		[entityFieldSelect, equalSignNode, entityFieldValueNode, deleteConditionButton].forEach(
			(node) => {
				newConditionRow.insertCell(-1).appendChild(node);
			},
		);
	}

	getCurrentFieldsOptions(selectedFieldId)
	{
		return Object
			.entries(this.fieldsMap.get(this.currentEntityTypeId))
			.map(
				([fieldId, field]) => {
					return Dom.create('option', {
						props: {value: field.FieldName},
						attrs: selectedFieldId === fieldId ? {selected: 'selected'} : undefined,
						text: field.Name,
					});
				},
			);
	}

	renderField(fieldId: string): HTMLElement
	{
		let value = this.currentValues.get(this.currentEntityTypeId)[fieldId];
		if (Type.isNil(value))
		{
			value = '';
		}

		return BX.Bizproc.FieldType.renderControl(
			this.documentType,
			this.fieldsMap.get(this.currentEntityTypeId)[fieldId],
			fieldId,
			value,
			this.isRobot ? 'public' : 'designer',
		);
	}
}

namespace.CrmUpdateDynamicActivity = CrmUpdateDynamicActivity;