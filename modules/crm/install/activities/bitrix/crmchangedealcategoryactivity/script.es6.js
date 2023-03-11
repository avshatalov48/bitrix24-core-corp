import {Reflection, Type, Event, Dom} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmChangeDealCategoryActivity
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

	init(): void
	{
		if (this.categorySelect && this.stageSelect)
		{
			Event.bind(this.categorySelect, 'change', this.filter.bind(this));

			this.filter();
		}
	}

	filter(): void
	{
		const categoryId = this.categorySelect.value;
		const prefix = categoryId !== '0' ? `C${categoryId}:` : '';

		for (const opt of this.stageSelect.options)
		{
			if (opt.value === '')
			{
				continue;
			}

			if (opt.selected && opt.getAttribute('data-role') === 'expression')
			{
				continue;
			}

			opt.disabled = (prefix && opt.value.indexOf(prefix) < 0) || (!prefix && opt.value.indexOf(':') > -1);

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

namespace.CrmChangeDealCategoryActivity = CrmChangeDealCategoryActivity;