import {Reflection, Type, Event, Dom} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmCreateDynamicActivity
{
	entityTypeIdSelect;
	currentEntityTypeId;
	fieldsContainerIdPrefix;
	entitiesFieldsContainers = new Map();

	constructor(options)
	{
		if (Type.isPlainObject(options))
		{
			const form = document.forms[options.formName];

			if (!Type.isNil(form))
			{
				this.entityTypeIdSelect = form['dynamic_type_id'];
				this.currentEntityTypeId = this.entityTypeIdSelect.value;
			}
			if (Type.isString(options.fieldsContainerIdPrefix))
			{
				this.fieldsContainerIdPrefix = options.fieldsContainerIdPrefix;
			}
		}
	}

	init(): boolean
	{
		if (this.entityTypeIdSelect && this.fieldsContainerIdPrefix)
		{
			Event.bind(this.entityTypeIdSelect, 'change', this.onEntityTypeIdChange.bind(this));
		}
	}

	onEntityTypeIdChange(): void
	{
		Dom.hide(this.getEntityFieldsContainer(this.currentEntityTypeId));

		this.currentEntityTypeId = this.entityTypeIdSelect.value;
		Dom.show(this.getEntityFieldsContainer(this.currentEntityTypeId));
	}

	getEntityFieldsContainer(entityTypeId: string): HTMLElement
	{
		if (!this.entitiesFieldsContainers.has(entityTypeId))
		{
			this.entitiesFieldsContainers.set(
				entityTypeId,
				document.getElementById(this.fieldsContainerIdPrefix + entityTypeId),
			);
		}

		return this.entitiesFieldsContainers.get(entityTypeId);
	}
}

namespace.CrmCreateDynamicActivity = CrmCreateDynamicActivity;