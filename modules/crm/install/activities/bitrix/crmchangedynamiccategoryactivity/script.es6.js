import {Reflection, Type, Event, Dom} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmChangeDynamicCategoryActivity
{
	categorySelect;
	stageSelect;

	constructor(options)
	{
		if (Type.isPlainObject(options))
		{
			const form = document.forms[options.formName];

			if (!Type.isNil(form))
			{
				this.categorySelect = form['category_id'];
				this.stageSelect = form['stage_id'];
			}
		}
	}

	init()
	{
		if (!this.categorySelect || !this.stageSelect)
		{
			return false;
		}

		Event.bind(this.categorySelect, 'change', this.filter.bind(this));

		this.filter();
	}

	filter()
	{
		const categoryId = this.categorySelect.value;
		const prefix = `C${categoryId}:`;

		for (let opt of this.stageSelect.options)
		{
			if (opt.value === '')
			{
				continue;
			}

			opt.disabled = opt.value.indexOf(prefix) < 0;

			if (opt.disabled === Dom.isShown(opt))
			{
				Dom.toggle(opt);
			}
			if (opt.disabled && opt.value === this.stageSelect.value)
			{
				opt.selected = false;
			}
		}
	}
}

namespace.CrmChangeDynamicCategoryActivity = CrmChangeDynamicCategoryActivity;