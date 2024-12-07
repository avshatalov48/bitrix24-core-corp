import { Type } from 'main.core';
import { MenuItemOptions } from 'main.popup';

export const MENU_ITEM_STUB_ID = 'stub';
export const MENU_SETTINGS_ID = 'crm-timeline-whatsapp-settings-menu';

const ACTIVE_MENU_ITEM_CLASS = 'menu-popup-item-accept';
const DEFAULT_MENU_ITEM_CLASS = 'menu-popup-item-none';

// eslint-disable-next-line class-methods-use-this
export function getSubmenuStubItems(): MenuItemOptions[]
{
	// needed for emitted the onSubMenuShow event
	return [
		{
			id: MENU_ITEM_STUB_ID,
		},
	];
}

export function getSendersItems(
	fromList: Object[],
	selectedPhoneId: string,
	onClickHandler: Function,
): MenuItemOptions[]
{
	if (!Type.isArrayFilled(fromList))
	{
		return [];
	}

	const result = [];
	fromList.forEach(({ id, name: text }) => {
		const className = (id === selectedPhoneId ? ACTIVE_MENU_ITEM_CLASS : DEFAULT_MENU_ITEM_CLASS);
		result.push({
			id,
			text,
			className,
			onclick: onClickHandler,
		});
	});

	return result;
}

export function getCommunicationsItems(
	communications: Object[],
	selectedPhoneId: string,
	onClickHandler: Function,
): MenuItemOptions[]
{
	if (!Type.isArrayFilled(communications))
	{
		return [];
	}

	const result = [];
	communications.forEach((communication: Object) => {
		if (Type.isArrayFilled(communication.phones))
		{
			communication.phones.forEach((phone: Object) => {
				const className = (phone.id === selectedPhoneId ? ACTIVE_MENU_ITEM_CLASS : DEFAULT_MENU_ITEM_CLASS);
				result.push({
					id: phone.id,
					text: `${communication.caption} (${phone.valueFormatted})`,
					className,
					onclick: onClickHandler,
				});
			});
		}
	});

	return result;
}

export function getNewCommunications(input: Array): Array
{
	const phoneReceivers = input.filter((receiver: Object) => receiver.address.typeId === 'PHONE');
	const newCommunications: {[addressSourceHash: string]: Object} = {};
	for (const receiver of phoneReceivers)
	{
		let communication = newCommunications[receiver.addressSource.hash];

		if (!communication)
		{
			communication = {
				entityTypeId: receiver.addressSource.entityTypeId,
				entityTypeName: BX.CrmEntityType.resolveName(receiver.addressSource.entityTypeId),
				entityId: receiver.addressSource.entityId,
				caption: receiver.addressSourceData?.title,
				phones: [],
			};
		}

		communication.phones.push({
			id: receiver.address.id,
			type: receiver.address.typeId,
			value: receiver.address.value,
			valueFormatted: receiver.address.valueFormatted,
		});

		newCommunications[receiver.addressSource.hash] = communication;
	}

	return Object.values(newCommunications);
}
