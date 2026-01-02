import { type JsonObject, Type } from 'main.core';
import { type MenuItemOptions } from 'main.popup';
import { CommunicationButton } from './communication-button';
import { type BaseButton } from 'ui.buttons';

export class MessengerButton extends CommunicationButton
{
	onButtonClick(button: BaseButton, event: PointerEvent): void
	{
		const keys = Object.keys(this.data);
		if (keys.length === 1)
		{
			const firstKey = keys[0];
			const items = this.data[firstKey];
			if (items.length === 1)
			{
				const parts = firstKey.split('_');
				if (parts.length >= 2)
				{
					this.#openChat(firstKey, items[0]);

					return;
				}
			}
		}

		this.openMenu();
	}

	prepareMenuItem(key: string, value: JsonObject | string): MenuItemOptions
	{
		let	messengerText = '';
		let messengerValue = '';

		if (Type.isPlainObject(value))
		{
			messengerValue = BX.prop.getString(value, 'VALUE', '');
			const valueType = BX.prop.getString(value, 'VALUE_TYPE', '');
			if (valueType === 'OPENLINE')
			{
				// Open line does not have formatted value
				messengerText = BX.prop.getString(value, 'COMPLEX_NAME', '');
			}
			else
			{
				messengerText = `${BX.prop.getString(value, 'COMPLEX_NAME', '')}: ${BX.prop.getString(value, 'VALUE_FORMATTED', '')}`;
			}
		}
		else
		{
			messengerText = value;
			messengerValue = value;
		}

		return {
			text: messengerText,
			onclick: () => {
				this.#openChat(key, messengerValue);
			},
		};
	}

	#openChat(entityKey: string, messenger: JsonObject | string): void
	{
		if (Type.isNil(window.top.BXIM))
		{
			console.error('crm.toolbar-component: messaging not supported');

			return;
		}

		const messengerValue = Type.isPlainObject(messenger) ? messenger.VALUE : messenger;
		window.top.BXIM.openMessengerSlider(messengerValue, { RECENT: 'N', MENU: 'N' });
	}

	getMultifieldTypeName(): string
	{
		return 'IM';
	}
}
