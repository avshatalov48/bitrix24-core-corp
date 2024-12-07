/**
 * @module ava-menu
 */
jn.define('ava-menu', (require, exports, module) => {
	const { menu } = require('native/avamenu') || {};
	const { AnalyticsEvent } = require('analytics');
	const { qrauth } = require('qrauth/utils');
	const { showAhaMoment } = require('ava-menu/aha-moment');
	const { CheckIn } = require('ava-menu/check-in');

	const entryTypes = {
		component: 'component',
		page: 'page',
		list: 'list',
		qrauth: 'qrauth',
	};

	const menuItemsIds = {
		checkIn: 'check_in',
	};

	class AvaMenu
	{
		/**
		 * @param {string} elemId
		 * @param {string} value
		 */
		static setCounter({ elemId, value })
		{
			if (!this.isMenuExists())
			{
				return;
			}

			const items = menu.getItems();

			if (!items)
			{
				console.error('Ava-menu elements are not loaded');

				return;
			}

			const item = items.find(({ id }) => elemId === id);

			if (!item)
			{
				console.error(`Ava-menu element with id ${elemId} not found`);

				return;
			}

			const totalCounter = items.reduce((acc, { counter }) => acc + (counter ? Number(counter) : 0), 0);
			const difference = Number(item.counter || 0) - Number(value || 0);
			const newTotalCounter = totalCounter - difference;

			menu.updateItem(elemId, { counter: String(value) });
			Application.setBadges({ user_avatar: String(newTotalCounter) });
		}

		static setUserInfo({ title, imageUrl })
		{
			if (!this.isMenuExists())
			{
				return;
			}

			const userInfo = menu.getUserInfo();

			if (!userInfo)
			{
				console.error('Ava-menu elements are not loaded');

				return;
			}

			menu.setUserInfo({ ...userInfo, title, imageUrl });
		}

		static isMenuExists()
		{
			if (!menu)
			{
				console.error('Ava-menu is not supported in your app yet');

				return false;
			}

			return true;
		}

		static init()
		{
			if (!menu)
			{
				return;
			}

			const avaMenu = new AvaMenu();

			avaMenu.initEventListeners();
			avaMenu.onAppStarted();
		}

		onAppStarted()
		{
			BX.onAppStarted(() => {
				const info = menu.getUserInfo();

				if (info?.customData?.ahaMoment?.shouldShow === 'Y')
				{
					try
					{
						// eslint-disable-next-line no-unused-vars
						const { IntranetBackground } = require('intranet/background');
						BX.addCustomEvent('userMiniProfileClosed', () => {
							showAhaMoment();
						});
					}
					catch
					{
						showAhaMoment();
					}
				}

				CheckIn.handleItemColor();
			});
		}

		initEventListeners()
		{
			menu.removeAllListeners('titleTap');
			menu.removeAllListeners('itemTap');

			menu.on('titleTap', (event) => {
				menu.hide();

				this.sendAnalyticsOnAvaMenuItemClicked({
					hasCounter: Number(event.counter) > 0,
				});

				this.handleOnMenuItemTap(event.customData.entryParams);
			});

			menu.on('itemTap', (event) => {
				menu.hide();

				this.sendAnalyticsOnAvaMenuItemClicked({
					hasCounter: Number(event.counter) > 0,
				});

				if (this.handleOnMenuItemTap(event.customData))
				{
					return;
				}

				if (event.id === menuItemsIds.checkIn)
				{
					CheckIn.open(event);
				}
			});
		}

		sendAnalyticsOnAvaMenuItemClicked({ hasCounter })
		{
			new AnalyticsEvent({
				tool: 'intranet',
				category: 'ava_menu',
				event: 'menu_open',
				type: hasCounter ? 'with_counter' : 'no_counter',
			}).send();
		}

		handleOnMenuItemTap(customData)
		{
			switch (customData?.type)
			{
				case entryTypes.component:
					PageManager.openComponent('JSStackComponent', {
						canOpenInDefault: true,
						...customData,
					});

					return true;

				case entryTypes.page:
					PageManager.openPage(customData);

					return true;

				case entryTypes.list:
					PageManager.openList(customData);

					return true;

				case entryTypes.qrauth:
					qrauth.open(customData);

					return true;

				default:
					return false;
			}
		}
	}

	module.exports = { AvaMenu, CheckIn };
});
