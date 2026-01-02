import { Cache, Event, Tag, Loc } from 'main.core';
import { AvatarBase, AvatarRound } from 'ui.avatar';
import { Menu, Popup } from 'main.popup';
import type { DesktopAccount } from 'im.v2.lib.desktop-api';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import { EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

export type ItemOptions = {
	account: DesktopAccount,
	isCurrent: boolean,
};

export class Item
{
	#cache = new Cache.MemoryCache();

	constructor(options: ItemOptions)
	{
		this.#setOptions(options);
	}

	getContainer(): HTMLElement
	{
		return this.#cache.remember('container', () => {
			return Tag.render`
				<div class="intranet-desktop-account-list-item__wrapper">
					<div class="intranet-desktop-account-list-item__avatar">
						${this.#getAvatar().getContainer()}
					</div>
					<div class="intranet-desktop-account-list-item__info-wrapper">
						${this.#getHost()}
						${this.#getEmail()}
					</div>
					${this.#getMoreMenu()}
				</div>
			`;
		});
	}

	#setOptions(options: ItemOptions): Item
	{
		this.#cache.set('options', options);

		return this;
	}

	#getOptions(): ItemOptions
	{
		return this.#cache.get('options', {});
	}

	#getMoreMenu(): HTMLElement
	{
		return this.#cache.remember('moreMenuElement', () => {
			const element = Tag.render`
				<i class="ui-icon-set --more-l intranet-desktop-account-list-item__more"/>
			`;
			const menu = new Menu({
				bindElement: element,
				items: [
					this.#getOptions().account.connected
						? {
							text: Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_MENU_ITEM_DISCONNECT'),
							onclick: () => {
								DesktopApi.disconnectAccount(this.#getOptions().account.host);
								menu.close();
								EventEmitter.emit('BX.Intranet.DesktopPortalList.Item:change');
							},
						} : {
							text: Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_MENU_ITEM_CONNECT'),
							onclick: () => {
								DesktopApi.connectAccount(
									this.#getOptions().account.host,
									this.#getOptions().account.login,
									this.#getOptions().account.protocol,
									navigator.language,
								);
								menu.close();
								EventEmitter.emit('BX.Intranet.DesktopPortalList.Item:change');
							},
						},
					{
						text: Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_MENU_ITEM_DELETE'),
						onclick: () => {
							menu.close();
							this.#getDeleteConfirmPopup(menu).show();
						},
					},
				],
			});
			Event.bind(element, 'click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				menu.toggle();
			});

			return element;
		});
	}

	#getAvatar(): AvatarBase
	{
		const path = this.#getOptions().account.avatar === '/bitrix/js/im/images/blank.gif'
			? '/bitrix/js/ui/icons/b24/images/ui-user.svg?v2'
			: this.#getOptions().account.avatar;
		const options = {
			size: 36,
			userpicPath: encodeURI(path),
		};

		return new AvatarRound(options);
	}

	#getHost(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-desktop-account-list-item__title-wrapper">
				<span class="intranet-desktop-account-list-item__title${this.#getOptions().isCurrent ? ' --current' : ''}">${this.#getOptions().account.host}</span>
				${this.#getStatus()}
			</div>
		`;
	}

	#getDeleteConfirmPopup(): Popup
	{
		return this.#cache.remember('deleteConfirmPopup', () => {
			return new MessageBox({
				minWidth: 500,
				title: Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_CONFIRM_POPUP_TITLE'),
				message: Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_CONFIRM_POPUP_MESSAGE'),
				buttons: MessageBoxButtons.OK_CANCEL,
				okCaption: Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_CONFIRM_POPUP_CONFIRM_BUTTON'),
				onOk: () => {
					const { host, login } = this.#getOptions().account;
					DesktopApi.deleteAccount(host, login);
					EventEmitter.emit('BX.Intranet.DesktopPortalList.Item:change');
					this.#getDeleteConfirmPopup().close();
				},
				useAirDesign: true,
			});
		});
	}

	#getStatus(): ?HTMLElement
	{
		if (!this.#getOptions().isCurrent)
		{
			return null;
		}

		return this.#cache.remember('statusElement', () => {
			return Tag.render`
				<span class="intranet-desktop-account-list-item__status">${Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_CURRENT_STATUS')}</span>
			`;
		});
	}

	#getEmail(): HTMLElement
	{
		return Tag.render`
			<span class="intranet-desktop-account-list-item__description">${this.#getOptions().account.email}</span>
		`;
	}
}
