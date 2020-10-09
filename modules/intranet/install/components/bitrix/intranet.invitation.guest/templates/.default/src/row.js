import { Cache, Tag, Loc, Type, Dom } from 'main.core';
import type { RowOptions } from './row-options';

export default class Row
{
	email: string = null;
	name: ?string = null;
	lastName: ?string = null;
	cache = new Cache.MemoryCache();

	constructor(rowOptions: RowOptions)
	{
		const options = Type.isPlainObject(rowOptions) ? rowOptions : {};

		if (Type.isStringFilled(options.email))
		{
			this.getEmailTextBox().value = options.email;
		}

		if (Type.isStringFilled(options.name))
		{
			this.getNameTextBox().value = options.name;
		}

		if (Type.isStringFilled(options.lastName))
		{
			this.getLastNameTextBox().value = options.lastName;
		}
	}

	isEmpty(): boolean
	{
		const email = this.getEmailTextBox().value.trim();

		return !Type.isStringFilled(email);
	}

	validate(): boolean
	{
		const email = this.getEmail();
		const name = this.getName();
		const lastName = this.getLastName();

		if (Type.isStringFilled(email))
		{
			const atom = '=_0-9a-z+~\'!\$&*^`|\\#%/?{}-';
			const regExp = new RegExp('^[' + atom + ']+(\\.[' + atom + ']+)*@(([-0-9a-z]+\\.)+)([a-z0-9-]{2,20})$', 'i');
			if (!email.match(regExp))
			{
				Dom.addClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
				return false;
			}

		}
		else if (Type.isStringFilled(name) || Type.isStringFilled(lastName))
		{
			Dom.addClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
			return false;
		}

		Dom.removeClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');

		return true;
	}

	focus()
	{
		this.getEmailTextBox().focus();
	}

	getEmail()
	{
		return this.getEmailTextBox().value.trim();
	}

	getName()
	{
		return this.getNameTextBox().value.trim();
	}

	getLastName()
	{
		return this.getLastNameTextBox().value.trim();
	}

	getContainer(): HTMLElement
	{
		return this.cache.remember('container', () => {
			return Tag.render`
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_EMAIL')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
							${this.getEmailTextBox()}
						</div>
					</div>
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_NAME')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
							${this.getNameTextBox()}
						</div>
					</div>
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_LAST_NAME')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
							${this.getLastNameTextBox()}
						</div>
					</div>
				</div>
			`;
		});
	}

	getEmailTextBox(): HTMLInputElement
	{
		return this.cache.remember('email', () => {
			return Tag.render`
				<input 
					type="email"
					class="ui-ctl-element" 
					placeholder="${Loc.getMessage('INTRANET_INVITATION_GUEST_ENTER_EMAIL')}"
				>
			`;
		});
	}

	getNameTextBox(): HTMLInputElement
	{
		return this.cache.remember('name', () => {
			return Tag.render`
				<input 
					type="text"
					class="ui-ctl-element" 
				>
			`;
		});
	}

	getLastNameTextBox(): HTMLInputElement
	{
		return this.cache.remember('last-name', () => {
			return Tag.render`
				<input 
					type="text"
					class="ui-ctl-element"
				>
			`;
		});
	}
}