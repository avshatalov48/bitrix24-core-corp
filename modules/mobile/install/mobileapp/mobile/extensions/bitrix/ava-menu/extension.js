/**
 * @module ava-menu
 */
jn.define('ava-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { menu } = require('native/avamenu') || {};
	const { AnalyticsEvent } = require('analytics');
	const { qrauth } = require('qrauth/utils');
	const { CheckIn } = require('ava-menu/check-in');
	const { Sign } = require('ava-menu/sign');
	const { Calendar } = require('ava-menu/calendar');

	const entryTypes = {
		component: 'component',
		page: 'page',
		list: 'list',
		qrauth: 'qrauth',
		switch_account: 'switch_account',
	};

	const menuItemsIds = {
		calendar: 'calendar',
		checkIn: 'check_in',
		startSigning: 'start_signing',
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

		static setUserInfo(params = {})
		{
			if (!this.isMenuExists())
			{
				return;
			}

			const userInfoParams = {
				...menu.getUserInfo(),
				...params,
			};

			menu.setUserInfo(userInfoParams);
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

		static getCollabStyles()
		{
			return {};
		}

		static isCollaber()
		{
			return Boolean(env.isCollaber);
		}

		onAppStarted()
		{
			BX.onAppStarted(() => {
				AvaMenu.setUserInfo();
				CheckIn.handleItemColor();
				Sign.handleItemColor();
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

				if (event.id === menuItemsIds.calendar && Calendar.open(event.customData))
				{
					return;
				}

				if (this.handleOnMenuItemTap(event.customData))
				{
					return;
				}

				if (event.id === menuItemsIds.startSigning)
				{
					Sign.open();
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
					void qrauth.open({
						...customData,
						showHint: true,
						title: Loc.getMessage('MOBILE_AVA_MENU_GO_TO_WEB_TITLE'),
						hintText: Loc.getMessage('MOBILE_AVA_MENU_GO_TO_WEB_HINT_TEXT'),
						analyticsSection: 'ava_menu',
					});

					return true;

				case entryTypes.switch_account:
					Application.exit();

					return true;

				default:
					return false;
			}
		}
	}

	module.exports = { AvaMenu, CheckIn };
});
