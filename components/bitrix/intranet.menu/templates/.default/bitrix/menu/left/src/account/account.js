import { Dom, Event, Loc, Tag, Type } from 'main.core';
import { DesktopApi, DesktopAccount } from 'im.v2.lib.desktop-api';
import { showDesktopDeleteConfirm } from 'im.v2.lib.confirm';
import { Menu, Popup, PopupManager } from 'main.popup';
import { PopupType } from 'im.v2.const';
import { Loader } from 'main.loader';

export class Account
{
	static defaultAvatar = '/bitrix/js/im/images/blank.gif';
	static defaultAvatarDesktop = '/bitrix/js/ui/icons/b24/images/ui-user.svg?v2';

	accounts: DesktopAccount[] = [];
	currentUser: ?Object = null;
	contextPopup: BX.Main.Popup[] = [];
	popup: BX.Main.Popup = null;
	allCounters: Object = {};
	wrapper: HTMLElement = null;
	loader: Loader = null;

	constructor(allCounters: Object)
	{
		this.wrapper = document.getElementById('history-items');
		this.loader = null;

		this.checkCounters(allCounters);
		this.reload();

		this.viewDesktopUser();
		this.initPopup();
	}

	#getLoader(): Loader
	{
		if (this.loader)
		{
			return this.loader;
		}

		this.loader = new Loader({
			target: document.querySelector('.intranet__desktop-menu_popup'),
			size: 80,
			mode: 'absolute',
			strokeWidth: 4,
		});

		return this.loader;
	}

	hideLoader(): void
	{
		this.#getLoader().hide();
	}

	checkCounters(allCounters: Object): void
	{
		for (const counterId of Object.keys(allCounters))
		{
			let key = counterId;
			if (counterId === '**')
			{
				key = 'live-feed';
			}
			this.allCounters[key] = allCounters[counterId];
		}
	}

	getSumCounters(): number
	{
		let sum = 0;
		for (const counterId of Object.keys(this.allCounters))
		{
			if (counterId === 'tasks_effective' || counterId === 'invited_users')
			{
				continue;
			}
			const val = this.allCounters[counterId] ? parseInt(this.allCounters[counterId], 10) : 0;
			sum += val;
		}

		return sum;
	}

	getTabUsers(): []
	{
		// eslint-disable-next-line no-undef
		return Type.isUndefined(BXDesktopSystem) ? [] : BXDesktopSystem?.TabList();
	}

	reload(): void
	{
		this.reloadAccounts();
		this.viewPopupAccounts();
	}

	reloadAccounts(): void
	{
		const currentUserId = parseInt(Loc.getMessage('USER_ID'), 10);
		// eslint-disable-next-line no-undef
		this.accounts = Type.isUndefined(BXDesktopSystem) ? [] : DesktopApi.getAccountList();
		this.currentUser = this.accounts.find(
			(account) => parseInt(account.id, 10) === currentUserId
				&& account.portal === location.hostname,
		);
	}

	getAccountBy(account: DesktopAccount): DesktopAccount
	{
		return this.accounts.find(
			(x) => parseInt(x.id, 10) === parseInt(account.id, 10)
				&& x.portal === account.portal,
		);
	}

	initPopup(): void
	{
		const menuContent = document.querySelector('.intranet__desktop-menu_popup');
		const userNode = document.querySelector('.intranet__desktop-menu_user-block');

		this.popup = new Popup({
			content: menuContent,
			bindElement: userNode,
			width: 320,
			background: '#282e39',
			closeIcon: true,
			closeByEsc: true,
		});

		Event.bind(userNode, 'click', (event) => {
			if (this.popup.isShown())
			{
				this.popup.close();
			}
			else
			{
				event.stopPropagation();
				this.popup.show();
				this.#getLoader().hide();
				this.reload();
			}
		});

		Event.bind(document, 'click', (event) => {
			const withinBoundaries = event.composedPath().includes(menuContent);

			if (!withinBoundaries)
			{
				this.popup.close();
			}
		});
	}

	setCounters(counters: Object): void
	{
		let newCounters = counters;

		if (counters.data)
		{
			newCounters = counters.data;
			if (newCounters[0] && Type.isObject(newCounters[0]))
			{
				newCounters = newCounters[0];
			}
		}

		for (const counterId of Object.keys(newCounters))
		{
			let cId = counterId;
			if (counterId === '**')
			{
				cId = 'live-feed';
			}
			this.allCounters[cId] = newCounters[counterId];
		}

		const sumCounters = this.getSumCounters();
		const block = document.getElementsByClassName('intranet__desktop-menu_user-block')[0];
		const counterNode = block?.querySelector('[data-role="counter"]');

		if (counterNode)
		{
			if (sumCounters > 0)
			{
				counterNode.innerHTML = sumCounters > 99 ? '99+' : sumCounters;
				if (!Dom.hasClass(block, 'intranet__desktop-menu_item_counters'))
				{
					Dom.addClass(block, 'intranet__desktop-menu_item_counters');
				}
			}
			else
			{
				counterNode.innerHTML = '';
				Dom.addClass(block, 'intranet__desktop-menu_item_counters');
			}
		}
	}

	removeElements(className: string): void
	{
		const elements = document.getElementsByClassName(className);

		[...elements].forEach((element) => {
			element.remove();
		});
	}

	viewDesktopUser(): void
	{
		if (this.currentUser === null)
		{
			return;
		}

		const block = document.getElementsByClassName('intranet__desktop-menu_user')[0];
		const counters = this.getSumCounters();

		let counterBlock = null;
		if (counters > 0)
		{
			const countersView = counters > 99 ? '99+' : counters;
			counterBlock = Tag.render`
				<div class="intranet__desktop-menu_user-counter ui-counter ui-counter-md ui-counter-danger">
					<div class="ui-counter-inner" data-role="counter">${countersView}</div>
				</div>
			`;
		}

		this.removeElements('intranet__desktop-menu_user-block');

		const userData = Tag.render`
			<div class="intranet__desktop-menu_user-block ${counters > 0 ? 'intranet__desktop-menu_item_counters' : ''}">
							<span class="intranet__desktop-menu_user-avatar ui-icon ui-icon-common-user ui-icon-common-user-desktop">
								<i></i>
								${counterBlock}
							</span>
							<span class="intranet__desktop-menu_user-inner">
								<span class="intranet__desktop-menu_user-name">${this.currentUser.portal}</span>
								<span class="intranet__desktop-menu_user-post">${this.currentUser.work_position}</span>
							</span>
							<span class="intranet__desktop-menu_user-inner-after"></span>
						</div>
		`;

		Dom.append(userData, block);

		const avatar = document.getElementsByClassName('ui-icon-common-user-desktop')[0];
		const previewImage = this.getAvatarUrl(this.currentUser);
		Dom.style(avatar, '--ui-icon-service-bg-image', previewImage);
	}

	getAvatarUrl(account): string
	{
		let avatarUrl = '';

		if (account.avatar.includes('http://') || account.avatar.includes('https://'))
		{
			avatarUrl = account.avatar;
		}
		else
		{
			avatarUrl = `${account.protocol}://${account.portal}${account.avatar}`;
		}

		return `url('${BX.util.htmlspecialchars(account.avatar === Account.defaultAvatar ? Account.defaultAvatarDesktop : BX.util.htmlspecialchars(avatarUrl))}')`;
	}

	viewPopupAccounts(): void
	{
		if (this.currentUser === null)
		{
			return;
		}

		const menuPopup = document.getElementsByClassName('intranet__desktop-menu_popup')[0];
		let position = '';

		if (Type.isStringFilled(this.currentUser.work_position))
		{
			position = `<span class="intranet__desktop-menu_popup-post">${this.currentUser.work_position}</span>`;
		}

		this.removeElements('intranet__desktop-menu_popup-header');

		const item = Tag.render`
			<div class="intranet__desktop-menu_popup-header">
				<span class="intranet__desktop-menu_user-avatar ui-icon ui-icon-common-user ui-icon-common-user-popup">
					<i></i>
				</span>
				<span class="intranet__desktop-menu_popup-label">${this.currentUser.portal}</span>
				<div class="intranet__desktop-menu_popup-header-user" onclick="BX.SidePanel.Instance.open('${this.currentUser.profile}')">
					<span class="intranet__desktop-menu_popup-name intranet__desktop-menu_popup-name--chevron">
						${`${this.currentUser.first_name} ${this.currentUser.last_name}`}
					</span>
					${position}
				</div>
			</div>
		`;

		Dom.insertBefore(item, menuPopup.firstElementChild);

		const avatar = document.getElementsByClassName('ui-icon-common-user-popup')[0];
		const previewImage = this.getAvatarUrl(this.currentUser);
		Dom.style(avatar, '--ui-icon-service-bg-image', previewImage);

		const block = document.getElementsByClassName('intranet__desktop-menu_popup-list')[0];

		this.removeElements('intranet__desktop-menu_popup-item-account');

		let index = 0;
		const users = this.getTabUsers();
		for (const account of this.accounts)
		{
			let currentUserClass = '';
			let currentUserConnected = '';
			let counters = 0;
			const isSelected = users.some(
				(x) => parseInt(x.id, 10) === parseInt(account.id, 10)
					&& x.portal === account.portal,
			);
			if (isSelected)
			{
				if (
					parseInt(account.id, 10) === parseInt(this.currentUser.id, 10)
					&& account.portal === this.currentUser.portal
				)
				{
					counters = this.getSumCounters();
					currentUserConnected = '--selected';
				}
				currentUserClass = '--connected';
			}

			const countersView = counters > 99 ? '99+' : counters;

			const item = Tag.render`
				<li class="intranet__desktop-menu_popup-item intranet__desktop-menu_popup-item-account ${counters > 0 ? 'intranet__desktop-menu_item_counters' : ''} ${currentUserClass} ${currentUserConnected}">
					<span class="intranet__desktop-menu_user-avatar ui-icon ui-icon-common-user ui-icon-common-user-${index}">
						<i></i>
						<div class="intranet__desktop-menu_user-counter ui-counter ui-counter-md ui-counter-danger">
							<div class="ui-counter-inner">${countersView}</div>
						</div>	
					</span>
					<span class="intranet__desktop-menu_popup-user">
						<span class="intranet__desktop-menu_popup-name">${account.portal}</span>
						<span class="intranet__desktop-menu_popup-post">${account.login}</span>
					</span>
					<span class="intranet__desktop-menu_popup-btn ui-icon-set --more" id="ui-icon-set-${index}"></span>
				</li>
			`;

			Dom.insertBefore(item, block.children[index]);

			this.openTabOrConnectAccount(account, item);
			this.addContextMenu(account, index, isSelected);

			const userAvatar = document.getElementsByClassName(`ui-icon-common-user-${index}`)[0];
			const previewUserImage = this.getAvatarUrl(account);
			Dom.style(userAvatar, '--ui-icon-service-bg-image', previewUserImage);

			index++;
		}
	}

	openTabOrConnectAccount(account: DesktopAccount, item: any): void
	{
		const block = item.querySelector('.intranet__desktop-menu_popup-user');
		Event.bind(block, 'click', () => {
			const siteUrl = `${account.protocol}://${account.portal}`;

			if (this.isAccountConnected(account))
			{
				window.open(siteUrl, '_blank');

				return;
			}

			this.#getLoader().show();
			this.connectAccount(account, () => {
				this.checkConnectedAccountAndStopLoader(account, 0, this.hideLoader);
			});
		});
	}

	connectAccount(account: DesktopAccount, callback: Function): void
	{
		const { host, login, protocol } = account;
		const userLang = navigator.language;
		DesktopApi.connectAccount(host, login, protocol, userLang);
		callback();
	}

	isAccountConnected(account: DesktopAccount): boolean
	{
		const users = this.getTabUsers();

		return users.some(
			(user) => parseInt(user.id, 10) === parseInt(account.id, 10) && user.portal === account.portal,
		);
	}

	checkConnectedAccountAndStopLoader(account: DesktopAccount, counter: number, callback: Function): void
	{
		if (counter >= 5)
		{
			this.reload();
			callback();

			return;
		}

		setTimeout(() => {
			this.reloadAccounts();
			const upAccount = this.getAccountBy(account);
			if (upAccount.connected)
			{
				callback();
			}
			else
			{
				this.checkConnectedAccountAndStopLoader(account, counter + 1, callback);
			}
		}, 1000);
	}

	addContextMenu(account: DesktopAccount, index: number, isSelected: boolean): void
	{
		const button = document.getElementById(`ui-icon-set-${index}`);
		if (this.contextPopup[index])
		{
			this.contextPopup[index].destroy();
		}
		this.contextPopup[index] = new Menu({
			bindElement: button,
			className: 'intranet__desktop-menu_context',
			items: [
				isSelected
					? {
						text: Loc.getMessage('MENU_ACCOUNT_POPUP_DISCONNECT'),
						onclick: () => this.disconnectAccount(account, index),
					}
					:					{
						text: Loc.getMessage('MENU_ACCOUNT_POPUP_CONNECT'),
						onclick: (event) => this.connectAccountFromMenu(account, event, index),
					},
				{
					text: Loc.getMessage('MENU_ACCOUNT_POPUP_REMOVE'),
					onclick: async () => this.removeAccount(account, index),
				},
			],
		});

		Event.bind(button, 'click', (event) => this.handleContextMenu(event));
	}

	removeAccount(account: DesktopAccount, index: number): void
	{
		showDesktopDeleteConfirm().then((userChoice) => {
			if (userChoice)
			{
				const { host, login } = account;
				DesktopApi.deleteAccount(host, login);
				PopupManager.getPopupById(PopupType.userProfile)?.close();
				this.reload();
			}
			this.closeContextMenu(index);
		});
	}

	disconnectAccount(account: DesktopAccount, index: number): void
	{
		const { host } = account;
		// eslint-disable-next-line no-undef
		BXDesktopSystem?.AccountDisconnect(host);
		this.closeContextMenu(index);
	}

	connectAccountFromMenu(account: DesktopAccount, event: Event, index: number): void
	{
		const { host, login, protocol } = account;
		const userLang = navigator.language;
		this.#getLoader().show();
		DesktopApi.connectAccount(host, login, protocol, userLang);
		event.stopPropagation();

		this.checkConnectedAccountAndStopLoader(account, 0, () => {
			this.#getLoader().hide();
			this.closeContextMenu(index);
		});
	}

	handleContextMenu(event: Event): void
	{
		const index: number = parseInt(event.target.id.replace('ui-icon-set-', ''), 10);
		if (this.contextPopup[index])
		{
			this.contextPopup[index].show();
		}
	}

	closeContextMenu(index: number): void
	{
		if (this.contextPopup[index])
		{
			this.contextPopup[index].close();
		}
		this.popup.close();
	}

	openLoginTab(): void
	{
		DesktopApi.openAddAccountTab();
	}
}
