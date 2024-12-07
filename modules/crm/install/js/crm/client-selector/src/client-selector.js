import { Loc, Text, Type } from 'main.core';
import type { ItemOptions } from 'ui.entity-selector';
import { Dialog } from 'ui.entity-selector';

export type Communication = {
	caption: string,
	entityId: number,
	entityTypeId: number,
	entityTypeName: string,
	phones?: CommunicationItem[],
	emails?: CommunicationItem[],
}

export type CommunicationItem = {
	id: string,
	type: string,
	typeLabel: string,
	value: string,
	valueFormatted: string,
}

type SelectorEvents = {
	'Item:onSelect'?: Function,
	'Item:onDeselect'?: Function,
	onShow?: Function,
	onHide?: Function,
}

const DEFAULT_TAB_ID = 'client';

export class ClientSelector
{
	targetNode: HTMLElement;
	context: string;
	items: ItemOptions[] = [];
	events: SelectorEvents = {};
	clientSelectorDialog: Dialog;
	#multiple: boolean = false;

	static createFromCommunications({
		targetNode,
		context = null,
		communications,
		multiple = false,
		selected = [],
		events = {},
	}): ClientSelector
	{
		const instance = new ClientSelector({
			targetNode,
			multiple,
			context,
			events,
		});

		instance.items = instance.getPhoneSelectorItems(communications);
		instance.setSelected(selected);

		return instance;
	}

	static createFromItems({
		targetNode,
		context = null,
		items,
		multiple = false,
		selected = [],
		events = {},
	}): ClientSelector
	{
		const instance = new ClientSelector({
			targetNode,
			multiple,
			context,
			events,
		});

		instance.items = instance.prepareItems(items);
		instance.setSelected(selected);

		return instance;
	}

	constructor({
		targetNode,
		multiple = false,
		context = null,
		events = {},
	})
	{
		this.targetNode = targetNode;
		this.#multiple = multiple;
		this.context = Type.isStringFilled(context) ? context : `crm-client-selector-${Text.getRandom()}`;
		this.events = Type.isObjectLike(events) ? events : {};
	}

	setSelected(ids: string[]): ClientSelector
	{
		// eslint-disable-next-line no-return-assign,no-param-reassign
		this.items.forEach((item) => item.selected = ids.includes(item.id));

		return this;
	}

	setSelectedItemByEntityData(entityId: number, entityTypeId: number): ClientSelector
	{
		this.items.forEach((item) => {
			if (
				item.customData.entityId === entityId
				&& item.customData.entityTypeId === entityTypeId
			)
			{
				// eslint-disable-next-line no-param-reassign
				item.selected = true;
			}
		});

		return this;
	}

	getPhoneSelectorItems(communications: Communication[]): Array
	{
		const items = [];

		communications.forEach((communication) => {
			const {
				phones,
				entityTypeName,
				entityId,
				entityTypeId,
				caption: title,
			} = communication;

			if (!Array.isArray(phones))
			{
				return;
			}

			phones.forEach((phone) => {
				const { id, valueFormatted, typeLabel } = phone;

				items.push({
					id,
					title,
					subtitle: `${valueFormatted}, ${typeLabel}`,
					entityId: DEFAULT_TAB_ID,
					tabs: DEFAULT_TAB_ID,
					avatar: this.#getEntityAvatarPath(entityTypeName),
					customData: {
						entityId,
						entityTypeId,
					},
				});
			});
		});

		return items;
	}

	prepareItems(items: ItemOptions[]): ItemOptions[]
	{
		return items.map((item) => {
			item.entityId = DEFAULT_TAB_ID;
			item.tabs = DEFAULT_TAB_ID;

			if (item.customData?.entityTypeId && !item.avatar)
			{
				const { entityTypeId } = item.customData;
				item.avatar = item.avatar ?? this.#getEntityAvatarPath(BX.CrmEntityType.resolveName(entityTypeId));
			}

			return item;
		});
	}

	#getEntityAvatarPath(entityTypeName: string): string
	{
		// eslint-disable-next-line no-param-reassign
		entityTypeName = entityTypeName.toLowerCase();

		const whiteList = [
			'contact',
			'company',
			'lead',
		];

		if (!whiteList.includes(entityTypeName))
		{
			return '';
		}

		return `/bitrix/images/crm/entity_provider_icons/${entityTypeName}.svg`;
	}

	show(): void
	{
		const { targetNode, context, items } = this;
		const events = this.#prepareEvents();
		const tabs = [
			{
				id: DEFAULT_TAB_ID,
				title: Loc.getMessage('CRM_CLIENT_SELECTOR_TAB_TITLE'),
			},
		];

		this.clientSelectorDialog = new Dialog({
			targetNode,
			id: 'client-phone-selector-dialog',
			context,
			multiple: this.#multiple,
			dropdownMode: true,
			showAvatars: true,
			enableSearch: true,
			width: 450,
			zIndex: 2500,
			items,
			tabs,
			events,
		});

		this.clientSelectorDialog.show();
	}

	#prepareEvents(): SelectorEvents
	{
		const { events: { onSelect, onDeselect, onHide, onShow } } = this;

		const events = {};

		if (onSelect)
		{
			events['Item:onSelect'] = onSelect;
		}

		if (onDeselect)
		{
			events['Item:onDeselect'] = onDeselect;
		}

		if (onHide)
		{
			events.onHide = onHide;
		}

		if (onShow)
		{
			events.onShow = onShow;
		}

		return events;
	}

	hide(): void
	{
		if (this.isOpen())
		{
			this.clientSelectorDialog.hide();
		}
	}

	isOpen(): boolean
	{
		return (this.clientSelectorDialog && this.clientSelectorDialog.isOpen());
	}
}
