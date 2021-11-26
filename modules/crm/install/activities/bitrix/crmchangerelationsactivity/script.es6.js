import {Reflection, Type, Event, Dom} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmChangeRelationsActivity
{
	actionTypeSelect: HTMLSelectElement;
	parentIdPropertyDiv: HTMLInputElement;

	constructor(options)
	{
		if (Type.isPlainObject(options))
		{
			const form = document.forms[options.formName];

			if (!Type.isNil(form))
			{
				this.actionTypeSelect = form.action;
				this.parentIdPropertyDiv = form.parent_id.parentElement.parentElement;
			}

			this.onActionTypeChange();
		}
	}

	init(): void
	{
		Event.bind(this.actionTypeSelect, 'change', this.onActionTypeChange.bind(this));
	}

	onActionTypeChange(): void
	{
		if (this.actionTypeSelect.value === 'remove')
		{
			Dom.style(this.parentIdPropertyDiv, 'visibility', 'hidden');
		}
		else
		{
			Dom.style(this.parentIdPropertyDiv, 'visibility', 'visible');
		}
	}
}

namespace.CrmChangeRelationsActivity = CrmChangeRelationsActivity;