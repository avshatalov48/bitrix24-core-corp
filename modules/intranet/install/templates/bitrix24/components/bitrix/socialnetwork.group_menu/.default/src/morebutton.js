import { Type, Loc, ajax } from 'main.core';
import { MenuManager } from 'main.popup';

export default class MoreButton
{
	constructor(params)
	{
		this.menu = null;
		this.class = {
			activeItem: 'menu-popup-item-sgm-accept-sm',
			inactiveItem: 'menu-popup-item-sgm-empty-sm',
		};

		this.init(params);
		return this;
	}

	init(params)
	{
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.bindingMenuItems = Type.isObject(params.bindingMenuItems) ? Object.values(params.bindingMenuItems) : [];
		this.userIsMember = Type.isBoolean(params.userIsMember) ? params.userIsMember : false;
		this.subscribedValue = Type.isBoolean(params.subscribedValue) ? params.subscribedValue : false;

		const moreButton = document.getElementById('group-menu-more-button');
		if (!moreButton)
		{
			return;
		}

		moreButton.addEventListener('click', this.showMoreMenu.bind(this));
	}

	showMoreMenu(event)
	{
		event.preventDefault();

		const bindingMenu = [];

		this.bindingMenuItems.forEach((item) => {
			bindingMenu.push(item);
		});

		const menu = [];

		if (this.userIsMember)
		{
			menu.push({
				id: 'subscribe',
				text: Loc.getMessage('SONET_SGM_T_MORE_MENU_SUBSCRIBE'),
				className: (this.subscribedValue ? this.class.activeItem : this.class.inactiveItem),
				onclick: () => {
					this.setSubscription(true);
				},
			});
			menu.push({
				id: 'unsubscribe',
				text: Loc.getMessage('SONET_SGM_T_MORE_MENU_UNSUBSCRIBE'),
				className: (!this.subscribedValue ? this.class.activeItem : this.class.inactiveItem),
				onclick: () => {
					this.setSubscription(false);
				},
			});
		}

		if (bindingMenu.length > 0)
		{
			if (menu.length > 0)
			{
				menu.push({
					delimiter: true,
				});
			}

			menu.push({
				text: Loc.getMessage('SONET_SGM_T_MORE_MENU_BINDING'),
				items: bindingMenu,
			});
		}

		if (menu.length <= 0)
		{
			return;
		}

		const bindElement = event.target;

		this.menu = MenuManager.create({
			id: 'group-more-menu',
			offsetTop: 5,
			offsetLeft: (bindElement.offsetWidth - 18),
			angle: true,
			items: menu,
			events: {
				onPopupClose: () => {
					if (bindElement.tagName === 'BUTTON')
					{
						bindElement.classList.remove('ui-btn-active');
					}
				}
			},
			subMenuOptions: {},
		});

		this.menu.popupWindow.setBindElement(bindElement);
		this.menu.popupWindow.show();
	}

	setSubscription(value: boolean)
	{
		this.redrawMenu(value);

		ajax.runAction('socialnetwork.api.workgroup.setSubscription', {
			data: {
				params: {
					groupId: this.groupId,
					value: (value ? 'Y' : 'N'),
				}
			},
		}).then((data) => {
			const eventData = {
				code: 'afterSetSubscribe',
				data: {
					groupId: this.groupId,
					value: (data.RESULT === 'Y'),
				},
			};
			window.top.BX.SidePanel.Instance.postMessageAll(window, 'sonetGroupEvent', eventData);
		}).catch(() => {
			this.redrawMenu(!value);
		});
	}

	redrawMenu(value: boolean)
	{
		if (!this.menu)
		{
			return;
		}

		const activeItem = this.menu.getMenuItem(value ? 'subscribe' : 'unsubscribe');
		const inactiveItem = this.menu.getMenuItem(value ? 'unsubscribe' : 'subscribe');

		if (activeItem)
		{
			activeItem.layout.item.classList.remove(this.class.inactiveItem);
			activeItem.layout.item.classList.add(this.class.activeItem);
		}

		if (inactiveItem)
		{
			inactiveItem.layout.item.classList.remove(this.class.activeItem);
			inactiveItem.layout.item.classList.add(this.class.inactiveItem);
		}
	}

}
