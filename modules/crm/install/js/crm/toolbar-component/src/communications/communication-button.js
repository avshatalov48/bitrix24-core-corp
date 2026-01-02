import { ClientSelector } from 'crm.client-selector';
import { type JsonObject, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Menu, type MenuItemOptions } from 'main.popup';
import { type Receiver } from 'crm.messagesender';
import { type BaseButton } from 'ui.buttons';

/**
 * @abstract
 */
export class CommunicationButton
{
	/**
	 * @protected
	 */
	ownerInfo: JsonObject;
	/**
	 * @protected
	 */
	data: JsonObject;
	/**
	 * @protected
	 */
	useClientSelector: boolean;

	#settings: JsonObject = {};
	#button: BaseButton;
	#isMenuOpened: boolean = false;
	#menuPopup: ?Menu;
	#isEnabled: boolean = false;
	#clientSelector: ?ClientSelector;

	constructor(settings: JsonObject)
	{
		this.#settings = settings || {};

		this.#button = this.#settings.button;
		this.#button.bindEvent('click', this.onButtonClick.bind(this));

		this.ownerInfo = BX.prop.getObject(this.#settings, 'ownerInfo', {});
		this.data = BX.prop.getObject(this.#settings, 'data', {});

		this.#enable(this.#hasData());

		this.useClientSelector = BX.prop.getBoolean(this.#settings, 'useClientSelector', false);

		EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', this.#onReceiversChange.bind(this));
	}

	getOwnerInfo(): JsonObject
	{
		return (
			{
				ownerID: this.ownerInfo.ENTITY_ID,
				ownerType: this.ownerInfo.ENTITY_TYPE_NAME,
				ownerUrl: this.ownerInfo.SHOW_URL,
				ownerTitle: this.ownerInfo.TITLE,
			}
		);
	}

	#getOwnerTypeName(): string
	{
		return BX.prop.getString(this.ownerInfo, 'ENTITY_TYPE_NAME', '');
	}

	#getOwnerId(): number
	{
		return BX.prop.getInteger(this.ownerInfo, 'ENTITY_ID', 0);
	}

	/**
	 * @abstract
	 */
	getMultifieldTypeName(): string
	{
		return '';
	}

	#hasData(): boolean
	{
		return Type.isPlainObject(this.data) && Type.isArrayFilled(Object.keys(this.data));
	}

	isEnabled(): boolean
	{
		return this.#isEnabled;
	}

	#enable(enabled: boolean): void
	{
		this.#isEnabled = Boolean(enabled);

		this.#button.setDisabled(!this.#isEnabled);
		if (!this.#isEnabled)
		{
			const title = this.getAddAddressSourceMessage(this.#getOwnerTypeName());
			if (title)
			{
				this.#button.getContainer().title = title;
			}
		}
	}

	getAddAddressSourceMessage(entityTypeName: string): string
	{
		return '';
	}

	/**
	 * @abstract
	 */
	onButtonClick(button: BaseButton, event: PointerEvent): void
	{}

	prepareMenuItem(key: any, value: any): MenuItemOptions
	{}

	openMenu(): void
	{
		if (this.#isMenuOpened)
		{
			this.#closeMenu();

			return;
		}

		const menuItems = [];
		for (const [key, items] of Object.entries(this.data))
		{
			for (const item of items)
			{
				menuItems.push(this.prepareMenuItem(key, item));
			}
		}

		if (this.useClientSelector)
		{
			this.#openClientSelector(menuItems);
		}
		else
		{
			this.#openPopupMenu(menuItems);
		}
	}

	#openClientSelector(menuItems: MenuItemOptions[]): void
	{
		if (!this.#clientSelector)
		{
			this.#clientSelector = BX.Crm.ClientSelector.createFromItems({
				targetNode: this.#button.getContainer(),
				items: menuItems,
				events: {
					onSelect: this.onClientSelectorSelect.bind(this),
					onShow: () => {
						this.#isMenuOpened = true;
					},
					onHide: () => {
						this.#isMenuOpened = false;
					},
				},
			});
		}

		this.#clientSelector.show();
	}

	onClientSelectorSelect({ data: { item } }): void
	{
		// may be implement in children classes
	}

	#openPopupMenu(menuItems: MenuItemOptions[]): void
	{
		this.#menuPopup = new Menu({
			bindElement: this.#button.getContainer(),
			offsetTop: 0,
			offsetLeft: 0,
			cacheable: false,
			items: menuItems,
			events: {
				onPopupShow: () => {
					this.#isMenuOpened = true;
				},
				onPopupClose: () => {
					this.#isMenuOpened = false;
				},
				onPopupDestroy: () => {
					this.#isMenuOpened = false;
					this.#menuPopup = null;
				},
			},
		});

		this.#menuPopup.show();
	}

	#closeMenu(): void
	{
		this.#menuPopup?.close();

		this.#clientSelector?.hide();
	}

	#onReceiversChange(event: BaseEvent): void
	{
		const { item, current } = event.getData();

		if (item.entityTypeName !== this.#getOwnerTypeName() || item.entityId !== this.#getOwnerId())
		{
			return;
		}

		this.data = {};

		for (const receiver: Receiver of current)
		{
			if (receiver.address.typeId !== this.getMultifieldTypeName())
			{
				continue;
			}

			const key = `${receiver.addressSource.entityTypeId}_${receiver.addressSource.entityId}`;

			this.data[key] ??= [];

			this.data[key].push({
				ID: receiver.address.id,
				ENTITY_ID: receiver.addressSource.entityId,
				ENTITY_TYPE_NAME: receiver.addressSource.entityTypeName,
				TYPE_ID: receiver.address.typeId,
				VALUE_TYPE: receiver.address.valueType,
				VALUE: receiver.address.value,
				VALUE_FORMATTED: receiver.address.valueFormatted,
				COMPLEX_ID: receiver.address.complexId,
				COMPLEX_NAME: receiver.address.complexName,
				OWNER: {
					ID: receiver.addressSource.entityId,
					TYPE_ID: receiver.addressSource.entityTypeId,
					TITLE: receiver.addressSourceData.title,
				},
			});
		}

		this.#enable(this.#hasData());
	}
}
