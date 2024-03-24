import { Reflection, Type, Event, Dom, Tag, Text } from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Activity');

type Property = {
	Id: string,
	Name: string,
	FieldName: string,
	Type: string,
	Required: ?boolean,
	Default: any,
	Options: ?Array,
	Settings: Object<string, any>,
}

type EntityTypeId = number;
type FieldId = string;
type FieldsMap = Object<FieldId, Property>;
type RawHTML = string;

class CrmCreateDynamicActivity
{
	isRobot: boolean;
	fieldsMapContainer: ?HTMLDivElement = undefined;
	entityTypeIdSelect: ?HTMLSelectElement = undefined;
	entitiesFieldsMap: Object<EntityTypeId, {
		documentType: [string, string, string],
		fieldsMap: FieldsMap,
	}>;

	currentValues: Object<FieldId, any> = {};
	renderedProperties: Object<FieldId, RawHTML> = {};

	entitiesFieldsContainers = new Map();

	constructor(options: {
		isRobot: boolean,
		formName: string,
		entitiesFieldsMap: Object<EntityTypeId, FieldsMap>,
		currentValues: Object<FieldId, any>,
	})
	{
		this.fieldsMapContainer = document.getElementById('fields-map-container');

		if (Type.isPlainObject(options))
		{
			this.isRobot = options.isRobot;

			const form = document.forms[options.formName];
			if (!Type.isNil(form))
			{
				this.entityTypeIdSelect = form['dynamic_type_id'];
			}

			this.entitiesFieldsMap = options.entitiesFieldsMap;

			if (Type.isPlainObject(options.currentValues))
			{
				this.currentValues = options.currentValues;
			}
		}
	}

	get currentEntityTypeId(): number
	{
		if (!this.entityTypeIdSelect)
		{
			return 0;
		}

		return parseInt(this.entityTypeIdSelect.value, 10);
	}

	getBindFieldId(): string
	{
		return `${this.currentEntityTypeId}_BindToCurrentElement`;
	}

	init(): boolean
	{
		if (this.entityTypeIdSelect)
		{
			this.render();
			Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
		}
	}

	onEntityTypeIdChange(): void
	{
		Dom.clean(this.fieldsMapContainer);
		this.currentValues = {};
		this.render();
	}

	render(): void
	{
		if (Object.hasOwn(this.entitiesFieldsMap, this.currentEntityTypeId))
		{
			const { fieldsMap } = this.entitiesFieldsMap[this.currentEntityTypeId];
			this.loadRenderedFields();

			for (const fieldId of Object.keys(fieldsMap))
			{
				Dom.append(this.renderProperty(fieldId), this.fieldsMapContainer);
			}
		}
	}

	loadRenderedFields()
	{
		const { documentType, fieldsMap } = this.entitiesFieldsMap[this.currentEntityTypeId];

		if (Type.isFunction(BX.Bizproc.FieldType.renderControlCollection))
		{
			this.renderedProperties = BX.Bizproc.FieldType.renderControlCollection(
				documentType,
				Object.entries(fieldsMap).map(([fieldId, field]) => ({
					property: field,
					fieldName: field.FieldName,
					value: this.currentValues[fieldId],
					controlId: fieldId,
				})),
				this.isRobot ? 'public' : 'designer',
			);
		}
	}

	renderProperty(fieldId: FieldId): HTMLElement
	{
		if (this.getBindFieldId() === fieldId)
		{
			return (
				this.isRobot
					? this.renderRobotBindField()
					: ''
			);
		}

		return (
			this.isRobot
				? this.renderRobotProperty(fieldId)
				: this.renderDesignerProperty(fieldId)
		);
	}

	renderRobotBindField(): HTMLElement
	{
		const { fieldsMap } = this.entitiesFieldsMap[this.currentEntityTypeId];

		const bindField = fieldsMap[this.getBindFieldId()];
		const bindFieldValue = (
			Object.hasOwn(this.currentValues, this.getBindFieldId())
			&& (
				this.currentValues[this.getBindFieldId()] === 'Y'
				|| this.currentValues[this.getBindFieldId()] === true
			)
		);

		return Tag.render`
			<div class="bizproc-automation-popup-settings">
				<div class="bizproc-automation-popup-checkbox-item">
					<input type="hidden" name="${Text.encode(bindField.FieldName)}" value="N">
					<label class="bizproc-automation-popup-chk-label">
						<input
							type="checkbox"
							name="${Text.encode(bindField.FieldName)}"
							value="Y"
							class="bizproc-automation-popup-chk"
							${bindFieldValue ? 'checked' : ''}
						>
						${Text.encode(bindField.Name)}
					</label>
				</div>
			</div>
		`;
	}

	renderRobotProperty(fieldId: FieldId): HTMLElement
	{
		const { documentType, fieldsMap } = this.entitiesFieldsMap[this.currentEntityTypeId];
		const property = fieldsMap[fieldId];

		const fallback = () => BX.Bizproc.FieldType.renderControlPublic(
			documentType,
			property,
			property.FieldName,
			this.currentValues[fieldId],
		);

		return Tag.render`
			<div class="bizproc-automation-popup-settings">
				<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
					${Text.encode(property.Name)}:
				</span>
				${Type.isDomNode(this.renderedProperties[fieldId]) ? this.renderedProperties[fieldId] : fallback()}
			</div>
		`;
	}

	renderDesignerProperty(fieldId: FieldId): HTMLElement
	{
		const { documentType, fieldsMap } = this.entitiesFieldsMap[this.currentEntityTypeId];
		const property = fieldsMap[fieldId];

		const fallback = () => BX.Bizproc.FieldType.renderControlDesigner(
			documentType,
			property,
			property.FieldName,
			this.currentValues[fieldId],
		);

		return Tag.render`
			<tr>
				<td align="right" width="40%">${Text.encode(property.Name)}:</td>
				<td width="60%">
					${Type.isDomNode(this.renderedProperties[fieldId]) ? this.renderedProperties[fieldId] : fallback()}
				</td>
			</tr>
		`;
	}
}

namespace.CrmCreateDynamicActivity = CrmCreateDynamicActivity;