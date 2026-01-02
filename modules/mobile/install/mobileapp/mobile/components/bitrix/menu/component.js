(() => {
	const require = (ext) => jn.require(ext);
	const { MoreMenu } = require('more-menu');
	const {
		getUpdateSectionsWithCounters,
		calculateTotalCounter,
	} = require('more-menu/utils');
	const { MenuNavigator } = require('more-menu/navigator');
	const { Type } = require('type');
	const { qrauth } = require('qrauth/utils');

	/**
	 * @class MenuService
	 */
	class MenuService
	{
		constructor()
		{
			this.menuNavigator = new MenuNavigator({});
		}

		updateBadges(menuList)
		{
			const cached = Application.sharedStorage().get('userCounters');
			let counters = {};
			try
			{
				counters = cached ? JSON.parse(cached) : {};
			}
			catch (error)
			{
				console.error('Invalid counters JSON', error);
			}

			const current = counters[env.siteId] || {};
			this.currentCounters = current;
			if (Object.keys(current).length > 0)
			{
				const sections = getUpdateSectionsWithCounters(menuList, current);
				const total = calculateTotalCounter(sections, current);
				Application.setBadges({ more: total });
			}
			else
			{
				Application.setBadges({ more: 0 });
			}
		}

		init()
		{
			const defaultMenuList = MoreMenu.getDefaultMenuList();
			this.menuNavigator.subscribeToEvents();

			return new Promise((resolve, reject) => {
				MoreMenu.subscribeMenuData(
					(cachedData) => this.updateMenuData(defaultMenuList, cachedData),
					(responseData) => {
						this.updateMenuData(defaultMenuList, responseData);
						resolve();
					},
					(error) => {
						reject(error);
					},
				);
			});
		}

		updateMenuData(defaultMenuList, menu)
		{
			const menuList = menu?.menuList;

			const list = Type.isArrayFilled(menuList)
				? MoreMenu.prepareMenuList(defaultMenuList, menuList)
				: defaultMenuList;

			this.menuNavigator.updateMenuList(list);
			this.updateBadges(list);
		}
	}

	const menuService = new MenuService();
	menuService
		.init()
		.catch((error) => {
			console.error('MenuService init error:', error);
		});

	qrauth.listenUniversalLink();

	BX.onViewLoaded(() => {
		layout.showComponent(new MoreMenu({
			layout,
			menuNavigator: menuService.menuNavigator,
			counters: menuService.currentCounters,
		}));
	});
})();
