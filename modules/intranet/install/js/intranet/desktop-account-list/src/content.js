import { ConfigOptions } from './types';
import { Cache, Dom, Loc, Tag } from 'main.core';
import { Item } from './item';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import type { DesktopAccount } from 'im.v2.lib.desktop-api';
import { EventEmitter } from 'main.core.events';

export class Content
{
	#cache = new Cache.MemoryCache();

	getAccounts(): Array<DesktopAccount>
	{
		return this.#cache.remember('accounts', () => {
			return (typeof BXDesktopSystem === 'undefined') ? [] : DesktopApi.getAccountList();
		});
	}

	getCurrentAccount(): ?DesktopAccount
	{
		return this.#cache.remember('currentAccount', () => {
			const currentUserId = Loc.getMessage('USER_ID');
			const accounts = this.getAccounts();
			const currentHost = location.host;

			return accounts.find((account) => account.id === currentUserId && currentHost === account.host);
		});
	}

	getContent(): Array<ConfigOptions>
	{
		return [
			this.#getCurrentPortal(),
			this.#getPanel(),
			this.#getPortalList(),
		];
	}

	#getCurrentPortal(): ConfigOptions
	{
		return this.#cache.remember('currentPortal', () => {
			if (!this.getCurrentAccount())
			{
				return null;
			}

			const item = new Item({
				account: this.getCurrentAccount(),
				isCurrent: true,
			});

			return {
				html: item.getContainer(),
			};
		});
	}

	#getPanel(): ConfigOptions
	{
		return this.#cache.remember('panel', () => {
			const onclick = () => {
				DesktopApi.openAddAccountTab();
				EventEmitter.emit('BX.Intranet.DesktopPortalList:openConnector');
			};

			return {
				html: Tag.render`
					<div class="intranet-desktop-account-list-item-list-header__wrapper">
						<span class="intranet-desktop-account-list-item-list-header__title">
							${Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_HEADER_TITLE')}
						</span>
						<div onclick="${onclick}" class="intranet-desktop-account-list-item-list-header__connect-wrapper">
							<i class="ui-icon-set --plus-s intranet-desktop-account-list-item-list-header__connect-icon"></i>
							<span class="intranet-desktop-account-list-item-list-header__connect-title">
								${Loc.getMessage('INTRANET_DESKTOP_PORTAL_LIST_HEADER_BUTTON')}
							</span>
						</div>
					</div>
				`,
				withoutBackground: true,
			};
		});
	}

	#getPortalList(): ConfigOptions
	{
		return this.#cache.remember('portalList', () => {
			const container = Tag.render`
				<div class="intranet-desktop-account-list-item-list__wrapper"></div>
			`;

			this.getAccounts().forEach((account) => {
				if (account !== this.getCurrentAccount())
				{
					const item = new Item({
						account,
						isCurrent: false,
					});

					Dom.append(item.getContainer(), container);
				}
			});

			return {
				html: container,
			};
		});
	}
}
