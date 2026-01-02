import { type JsonObject, Loc, Type } from 'main.core';
import { type MenuItemOptions } from 'main.popup';
import { type BaseButton } from 'ui.buttons';
import { CommunicationButton } from './communication-button';

export class EmailButton extends CommunicationButton
{
	getAddAddressSourceMessage(entityTypeName: string): string
	{
		return Loc.getMessage(`CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_EMAIL_SEND_${entityTypeName}`) || Loc.getMessage('CRM_TOOLBAR_COMPONENT_ADD_CLIENT_FOR_EMAIL_SEND');
	}

	onButtonClick(button: BaseButton, event: PointerEvent): void
	{
		if (!this.isEnabled())
		{
			return;
		}

		if (!this.useClientSelector)
		{
			BX.CrmActivityEditor.addEmail(this.getOwnerInfo());

			return;
		}

		const keys = Object.keys(this.data);
		if (keys.length === 1)
		{
			const firstKey = keys[0];
			const items = this.data[firstKey];
			if (items.length === 1)
			{
				BX.CrmActivityEditor.addEmail(this.getOwnerInfo());

				return;
			}
		}

		this.openMenu();
	}

	prepareMenuItem(key: string, value: JsonObject): MenuItemOptions
	{
		if (!Type.isPlainObject(value) || !this.useClientSelector)
		{
			return {};
		}

		return {
			id: value.ID,
			title: value.OWNER ? value.OWNER.TITLE : value.VALUE_FORMATTED,
			subtitle: value.OWNER ? `${value.VALUE_FORMATTED}, ${value.COMPLEX_NAME}` : value.COMPLEX_NAME,
			avatar: null,
			customData: {
				entityId: value.OWNER ? value.OWNER.ID : null,
				entityTypeId: value.OWNER ? value.OWNER.TYPE_ID : null,
				value: value.VALUE ?? null,
				valueType: value.VALUE_TYPE ?? null,
			},
		};
	}

	onClientSelectorSelect({ data: { item } }): void
	{
		const { customData } = item;
		const entityTypeId = customData.get('entityTypeId');

		const data = this.getOwnerInfo();

		data.mailDefaultCommunications = [
			{
				ENTITY_ID: customData.get('entityId'),
				ENTITY_TYPE_ID: entityTypeId,
				ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(entityTypeId),
				TYPE: this.getMultifieldTypeName(),
				VALUE: customData.get('value'),
				VALUE_TYPE: customData.get('valueType'),
			},
		];

		BX.CrmActivityEditor.addEmail(data);
	}

	getMultifieldTypeName(): string
	{
		return 'EMAIL';
	}
}
