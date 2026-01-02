import { type JsonObject, Loc, Text, Type } from 'main.core';
import { type MenuItemOptions } from 'main.popup';
import { type BaseButton } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';
import { type ItemOptions } from 'ui.entity-selector';
import { CommunicationButton } from './communication-button';

export class PhoneButton extends CommunicationButton
{
	getAddAddressSourceMessage(entityTypeName: string): string
	{
		return Loc.getMessage(`CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_CALL_${entityTypeName}`) || Loc.getMessage('CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_CALL');
	}

	onButtonClick(button: BaseButton, event: PointerEvent): void
	{
		if (!this.isEnabled())
		{
			return;
		}

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
					this.#addCall(firstKey, items[0]);

					return;
				}
			}
		}

		this.openMenu();
	}

	prepareMenuItem(key, value): MenuItemOptions
	{
		let	phoneText = value;
		let phoneValue = value;

		if (Type.isPlainObject(value))
		{
			const complexName = BX.prop.getString(value, 'COMPLEX_NAME', '');
			const valueFormatted = BX.prop.getString(value, 'VALUE_FORMATTED', '');

			phoneText = `${complexName}: ${valueFormatted}`;
			phoneValue = BX.prop.getString(value, 'VALUE', '');

			if (this.useClientSelector)
			{
				return this.#createClientSelectorMenuItem(value);
			}
		}

		return this.#createPopupMenuItem(key, phoneValue, phoneText);
	}

	#createClientSelectorMenuItem(value: JsonObject): ItemOptions
	{
		const complexName = BX.prop.getString(value, 'COMPLEX_NAME', '');
		const valueFormatted = BX.prop.getString(value, 'VALUE_FORMATTED', '');
		const phoneValue = BX.prop.getString(value, 'VALUE', '');

		const owner = Type.isObjectLike(value.OWNER) ? value.OWNER : null;

		return {
			id: value.ID,
			title: owner ? owner.TITLE : valueFormatted,
			subtitle: owner ? `${valueFormatted}, ${complexName}` : complexName,
			avatar: null,
			customData: {
				entityId: owner ? owner.ID : null,
				entityTypeId: owner ? owner.TYPE_ID : null,
				value: phoneValue,
			},
		};
	}

	#createPopupMenuItem(entityKey: string, value: JsonObject, text: string): MenuItemOptions
	{
		return {
			text,
			onclick: () => {
				this.#addCall(entityKey, value);
			},
		};
	}

	onClientSelectorSelect({ data: { item } }): void
	{
		const { customData } = item;
		const entityKey = `${customData.get('entityTypeId')}_${customData.get('entityId')}`;
		const value = customData.get('value');

		this.#addCall(entityKey, value);
	}

	#addCall(entityKey: string, phone: JsonObject | string): void
	{
		if (Type.isNil(window.top.BXIM))
		{
			MessageBox.alert(
				Loc.getMessage('CRM_TOOLBAR_COMPONENT_TELEPHONY_NOT_SUPPORTED'),
				null,
				Loc.getMessage('CRM_TOOLBAR_COMPONENT_TELEPHONY_NOT_SUPPORTED_OK'),
			);

			return;
		}

		const parts = entityKey.split('_');
		if (parts.length < 2)
		{
			return;
		}

		const entityTypeId = Text.toInteger(parts[0]);
		const entityId = Text.toInteger(parts[1]);

		const ownerTypeId = BX.prop.getInteger(this.ownerInfo, 'ENTITY_TYPE_ID', 0);
		const ownerId = BX.prop.getInteger(this.ownerInfo, 'ENTITY_ID', 0);

		const phoneValue = Type.isPlainObject(phone) ? phone.VALUE : phone;

		const params = {
			ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(entityTypeId),
			ENTITY_ID: entityId,
			AUTO_FOLD: true,
		};
		if (ownerTypeId !== entityTypeId || ownerId !== entityId)
		{
			params.BINDINGS = [{ OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(ownerTypeId), OWNER_ID: ownerId }];
		}

		window.top.BXIM.phoneTo(phoneValue, params);
	}

	getMultifieldTypeName(): string
	{
		return 'PHONE';
	}
}
