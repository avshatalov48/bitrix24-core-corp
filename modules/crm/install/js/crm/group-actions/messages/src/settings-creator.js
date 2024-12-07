import { BaseEvent } from 'main.core.events';
import { Loc } from 'main.core';
import type { MenuItemOptions, Menu, MenuItem } from 'main.popup';
import { MenuManager } from 'main.popup';
import { fetchSmsProvidersConfig, FromListItem, ProviderConfig } from './http';
import { DEFAULT_PROVIDER } from './messages';

/**
 * Currently only 'ednaru' provider is supported. To extend must implement select provider logic
 */
export class SettingsCreator
{
	#instance: Menu = null;

	#currentFromNumberId: ?string = null;

	#rawProviders: ProviderConfig[] = [];

	constructor(currentFromNumber: ?string)
	{
		this.#currentFromNumberId = currentFromNumber;
	}

	async create(): Promise<Menu>
	{
		this.#rawProviders = await fetchSmsProvidersConfig();

		if (this.#currentFromNumberId === null)
		{
			this.#currentFromNumberId = this.#getEdnaProviderFromRaw(this.#rawProviders).fromList[0].id;
		}

		const menuId = 'crm-whatsapp-channels-settings-menu';

		this.#instance = MenuManager.create({
			id: menuId,
			bindElement: document.querySelector('.bx-crm-group-actions-messages-settings-icon'),
			items: [
				{
					delimiter: true,
					text: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_SETTINGS'),
				},
				{
					id: 'channelSubmenu',
					text: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_SENDER_SELECTOR'),
					items: this.#createProvidersMenu(this.#rawProviders),
				},
				{
					id: 'phoneSubmenu',
					text: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_NUMBER_SELECTOR'),
					items: this.#createFromNumberMenus(
						this.#getEdnaProviderFromRaw(this.#rawProviders).fromList,
						this.#currentFromNumberId,
					),
				},
			],
		});

		return this.#instance;
	}

	#getEdnaProviderFromRaw(rawProviders: ProviderConfig[]): ProviderConfig
	{
		if (this.#rawProviders.length !== 1 && rawProviders[0].id !== DEFAULT_PROVIDER)
		{
			throw new Error(`Currently only ${DEFAULT_PROVIDER} is supported.`);
		}

		return this.#rawProviders[0];
	}

	#createProvidersMenu(providers: ProviderConfig[]): MenuItemOptions[]
	{
		const ednaProvider = this.#getEdnaProviderFromRaw(providers);

		return [{
			id: ednaProvider.id,
			title: ednaProvider.name,
			text: ednaProvider.name,
			disabled: true,
			className: 'menu-popup-item-accept',
		}];
	}

	#createFromNumberMenus(fromList: FromListItem[], currentFromNumber: string): MenuItemOptions[]
	{
		return fromList.map((fromNumber) => {
			const className = fromNumber.id === currentFromNumber ? 'menu-popup-item-accept' : 'menu-popup-item-none';

			return {
				id: fromNumber.id,
				title: fromNumber.name,
				text: fromNumber.name,
				onclick: this.#onFromNumbersChange.bind(this),
				className,
			};
		});
	}

	#onFromNumbersChange(event: BaseEvent, fromMenu: MenuItem)
	{
		const selectedChannelPhone = fromMenu.id;

		BX.Event.EventEmitter.emit('BX.Crm.GroupActionsWhatsApp.FromPhoneSelected', { phone: selectedChannelPhone });
		this.#instance.close();
	}
}
