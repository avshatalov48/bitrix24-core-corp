import { Reflection, Type } from 'main.core';
import { NavigationPanel } from 'ui.navigationpanel';

import './style.css';

const namespace = Reflection.namespace('BX.Crm');

export default class NavigationBar extends NavigationPanel
{
	#id: string;
	#binding: Array;

	constructor(options: Object): void
	{
		if (!Type.isPlainObject(options))
		{
			throw 'BX.Crm.NavigationBar: The "options" argument must be object.';
		}

		options.items = Type.isArray(options.items) ? options.items : [];
		options.items.forEach(item => {
			if (
				!item.hasOwnProperty('active')
				&& item.hasOwnProperty('isActive')
			)
			{
				item.active = item.isActive;
			}

			if (Type.isStringFilled(item.lockedCallback))
			{
				item.locked = true;
				item.url = '';
				item.events = {click: () => eval(item.lockedCallback)}
			}

			if (Type.isStringFilled(item.url))
			{
				item.events = {click: () => this.openUrl(item.id, item.url)}
			}
		});

		super({
			target: BX(options.id),
			items: options.items,
		});

		this.#id = options.id;
		this.#binding = options.binding;
	}

	openUrl(itemId: String, url: String): void
	{
		if (!Type.isStringFilled(url))
		{
			return;
		}

		if (this.#binding && Type.isPlainObject(this.#binding))
		{
			const category = Type.isStringFilled(this.#binding.category)
				? this.#binding.category
				: '';
			const name = Type.isStringFilled(this.#binding.name)
				? this.#binding.name
				: '';
			const key = Type.isStringFilled(this.#binding.key)
				? this.#binding.key
				: '';

			if (category !== '' && name !== '' && key !== '')
			{
				const value = itemId + ":" + BX.formatDate(new Date(), 'YYYYMMDD');

				BX.userOptions.save(category, name, key, value, false);
			}
		}

		setTimeout(function() { window.location.href = url; }, 150);
	}
}

namespace.NavigationBar = NavigationBar;
