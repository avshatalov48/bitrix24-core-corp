import {Reflection, Type, Event, Dom} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmGetDynamicInfoActivity
{
	documentType: Array<string>;
	isRobot: boolean;
	entityTypeIdSelect: HTMLSelectElement;

	returnFieldsProperty: object;
	returnFieldsMapContainer: HTMLDivElement;
	returnFieldsMap: Map<number, Map<string, object>>;
	returnFieldsIds: Array<string>;

	filterFieldsContainer: HTMLDivElement | null;
	filteringFieldsPrefix: string;
	filterFieldsMap: Map<number, object>;
	conditionGroup: BX.Bizproc.Automation.ConditionGroup | undefined;

	currentEntityTypeId: number;

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
				this.currentEntityTypeId = Number(this.entityTypeIdSelect.value);
				this.entityTypeDependentElements = document.querySelectorAll(
					'[data-role="bca-cuda-entity-type-id-dependent"]',
				);
			}

			this.initFilterFields(options);
			this.initReturnFields(options);

			this.render();
		}
	}

	initFilterFields(options)
	{
		this.conditinIdPrefix = 'id_bca_cuda_field_';
		this.filterFieldsContainer = document.querySelector('[data-role="bca-cuda-filter-fields-container"]');
		this.filteringFieldsPrefix = options.filteringFieldsPrefix;
		this.filterFieldsMap = new Map(
			Object.entries(options.filterFieldsMap)
				.map(([entityTypeId, fieldsMap]) => [Number(entityTypeId), fieldsMap]),
		);
		if (!Type.isNil(options.documentName))
		{
			BX.Bizproc.Automation.API.documentName = options.documentName;
		}
		if (BX.Bizproc.Automation && BX.Bizproc.Automation.ConditionGroup)
		{
			this.conditionGroup = new BX.Bizproc.Automation.ConditionGroup(options.conditions);
		}
	}

	initReturnFields(options)
	{
		this.returnFieldsProperty = options.returnFieldsProperty;
		this.returnFieldsIds = Type.isArray(options.returnFieldsIds) ? options.returnFieldsIds : [];

		this.returnFieldsMapContainer = document.querySelector('[data-role="bca-cuda-return-fields-container"]');
		this.returnFieldsMap = new Map();
		Object.entries(options.returnFieldsMap).forEach(([entityTypeId, fieldsMap]) => {
			this.returnFieldsMap.set(Number(entityTypeId), new Map(Object.entries(fieldsMap)));
		});
	}

	init(): void
	{
		if (this.entityTypeIdSelect)
		{
			Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
		}
	}

	onEntityTypeIdChange(): void
	{
		this.currentEntityTypeId = Number(this.entityTypeIdSelect.value);

		if (BX.Bizproc.Automation && BX.Bizproc.Automation.ConditionGroup)
		{
			this.conditionGroup = new BX.Bizproc.Automation.ConditionGroup();
		}

		this.returnFieldsIds = [];

		this.render();
	}

	render(): void
	{
		if (Type.isNil(this.currentEntityTypeId) || this.currentEntityTypeId === 0)
		{
			this.entityTypeDependentElements.forEach((element) => Dom.hide(element));
		}
		else
		{
			this.entityTypeDependentElements.forEach((element) => Dom.show(element));
			this.renderFilterFields();
			this.renderReturnFields();
		}
	}

	renderFilterFields(): void
	{
		if (
			!Type.isNil(this.conditionGroup)
			&& this.currentEntityTypeId !== 0
		)
		{
			const selector = new BX.Bizproc.Automation.ConditionGroupSelector(this.conditionGroup, {
				fields: Object.values(this.filterFieldsMap.get(this.currentEntityTypeId)),
				fieldPrefix: this.filteringFieldsPrefix,
			});

			Dom.clean(this.filterFieldsContainer);
			this.filterFieldsContainer.appendChild(selector.createNode());
		}
	}

	renderReturnFields(): void
	{
		const entityTypeId = this.currentEntityTypeId;
		const fieldsMap = this.returnFieldsMap.get(entityTypeId);

		if (!Type.isNil(fieldsMap))
		{
			const fieldOptions = {};
			fieldsMap.forEach((field, fieldId) => {
				fieldOptions[fieldId] = field.Name;
			});
			this.returnFieldsProperty.Options = fieldOptions;

			Dom.clean(this.returnFieldsMapContainer);
			this.returnFieldsMapContainer.appendChild(
				BX.Bizproc.FieldType.renderControl(
					this.documentType,
					this.returnFieldsProperty,
					this.returnFieldsProperty.FieldName,
					this.returnFieldsIds,
					this.isRobot ? 'public' : 'designer',
				),
			);
		}
	}
}

namespace.CrmGetDynamicInfoActivity = CrmGetDynamicInfoActivity;