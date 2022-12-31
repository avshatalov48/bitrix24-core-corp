import { Reflection, Type, Event, Dom, Loc } from 'main.core';
import {
	Context,
	ConditionGroup,
	ConditionGroupSelector,
	Document,
	getGlobalContext,
	setGlobalContext,
	InlineSelector,
	Designer,
} from 'bizproc.automation';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmGetDynamicInfoActivity
{
	documentType: Array<string>;
	document: Document;
	isRobot: boolean;
	entityTypeIdSelect: HTMLSelectElement;

	returnFieldsProperty: object;
	returnFieldsMapContainer: HTMLDivElement;
	returnFieldsMap: Map<number, Map<string, object>>;
	returnFieldsIds: Array<string>;

	filterFieldsContainer: HTMLDivElement | null;
	filteringFieldsPrefix: string;
	filterFieldsMap: Map<number, object>;
	conditionGroup: ConditionGroup | undefined;

	currentEntityTypeId: number;

	onOpenFilterFieldsMenu: ?Function;

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

			this.document = new Document({
				rawDocumentType: this.documentType,
				documentFields: options.documentFields,
				title: options.documentName,
			});

			this.initAutomationContext();
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

		// issue 0158608
		if (!Type.isNil(options.documentType) && !this.isRobot)
		{
			BX.Bizproc.Automation.API.documentType = options.documentType;
		}
		this.conditionGroup = new ConditionGroup(options.conditions);
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

	initAutomationContext()
	{
		try
		{
			getGlobalContext();
			if (this.isRobot)
			{
				this.onOpenFilterFieldsMenu = (event) => {
					const dialog = Designer.getInstance().getRobotSettingsDialog();
					const template = dialog.template;
					const robot = dialog.robot;
					if (template && robot)
					{
						template.onOpenMenu(event, robot);
					}
				}
			}
		}
		catch(error)
		{
			setGlobalContext(new Context({document: this.document}));
			this.onOpenFilterFieldsMenu = (event) => this.addBPFields(event.getData().selector);
		}
	}

	addBPFields(selector: InlineSelector): void
	{
		const getSelectorProperties = ({properties, objectName, expressionPrefix}) => {
			if (Type.isObject(properties))
			{
				return Object.entries(properties).map(([id, property]) => ({
					id,
					title: property.Name,
					customData: {
						field: {
							Id: id,
							Type: property.Type,
							Name: property.Name,
							ObjectName: objectName,
							SystemExpression: `{=${objectName}:${id}}`,
							Expression: expressionPrefix ? `{{${expressionPrefix}:${id}}}` : `{=${objectName}:${id}}`,
						},
					}
				}));
			}

			return [];
		}
		const getGlobalSelectorProperties = ({properties, visibilityNames, objectName}) => {
			if (Type.isObject(properties))
			{
				return Object.entries(properties).map(([id, property]) => {
					const field = {
						id,
						Type: property.Type,
						title: property.Name,
						ObjectName: objectName,
						SystemExpression: `{=${objectName}:${id}}`,
						Expression: `{=${objectName}:${id}}`,
					}

					if (property.Visibility && visibilityNames[property.Visibility])
					{
						field.Expression = `{{${visibilityNames[property.Visibility]}:${property.Name}}}`;
					}

					return {
						id,
						title: property.Name,
						supertitle: visibilityNames[property.Visibility],
						customData: { field },
					}
				})
			}

			return [];
		}

		selector.addGroup('workflowParameters', {
			id: 'workflowParameters',
			title: Loc.getMessage('BIZPROC_WFEDIT_MENU_PARAMS'),
			children: [
				{
					id: 'parameters',
					title: Loc.getMessage('BIZPROC_AUTOMATION_CMP_PARAMETERS_LIST'),
					children: getSelectorProperties({
						properties: window.arWorkflowParameters || {},
						objectName: 'Template',
						expressionPrefix: '~*',
					}),
				},
				{
					id: 'variables',
					title: Loc.getMessage('BIZPROC_AUTOMATION_CMP_GLOB_VARIABLES_LIST_1'),
					children: getSelectorProperties({
						properties: window.arWorkflowVariables || {},
						objectName: 'Variable',
					}),
				},
				{
					id: 'constants',
					title: Loc.getMessage('BIZPROC_AUTOMATION_CMP_CONSTANTS_LIST'),
					children: getSelectorProperties({
						properties: window.arWorkflowConstants || {},
						objectName: 'Constant',
						expressionPrefix: '~&',
					}),
				},
			],
		});
		if (window.arWorkflowGlobalVariables && window.wfGVarVisibilityNames)
		{
			selector.addGroup('globalVariables', {
				id: 'globalVariables',
				title: Loc.getMessage('BIZPROC_AUTOMATION_CMP_GLOB_VARIABLES_LIST'),
				children: getGlobalSelectorProperties({
					properties: window.arWorkflowGlobalVariables || {},
					visibilityNames: window.wfGVarVisibilityNames || {},
					objectName: 'GlobalVar',
				}),
			});
		}
		selector.addGroup('globalConstants', {
			id: 'globalConstants',
			title: Loc.getMessage('BIZPROC_AUTOMATION_CMP_GLOB_CONSTANTS_LIST'),
			children: getGlobalSelectorProperties({
				properties: window.arWorkflowGlobalConstants || {},
				visibilityNames: window.wfGConstVisibilityNames || {},
				objectName: 'GlobalConst',
			}),
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

		this.conditionGroup = new ConditionGroup();

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
			const selector = new ConditionGroupSelector(this.conditionGroup, {
				fields: Object.values(this.filterFieldsMap.get(this.currentEntityTypeId)),
				fieldPrefix: this.filteringFieldsPrefix,
				onOpenMenu: this.onOpenFilterFieldsMenu,
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