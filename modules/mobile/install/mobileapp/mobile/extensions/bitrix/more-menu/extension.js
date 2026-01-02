/**
 * @module more-menu
 */
jn.define('more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { Haptics } = require('haptics');

	const { MoreMenuHeader } = require('more-menu/block/header');
	const { ToolsList } = require('more-menu/block/tools-list');
	const { SettingsList } = require('more-menu/block/settings-list');
	const { MoreMenuCompany } = require('more-menu/block/company');

	const { SearchList } = require('more-menu/search-list');
	const { MenuNavigator } = require('more-menu/navigator');
	const { MoreMenuPanel } = require('more-menu/ui/panel');

	const { LoadingScreenComponent } = require('layout/ui/loading-screen');

	const { RunActionExecutor } = require('rest/run-action-executor');

	const { usersUpserted } = require('statemanager/redux/slices/users');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const { PropTypes } = require('utils/validation');

	const { createTestIdGenerator } = require('utils/test');
	const { isEmpty } = require('utils/object');

	const MENU_LIST_ACTION_NAME = 'mobile.Menu.getMenu';
	const SECONDS_IN_DAY = 86400;
	const MORE_MENU_TEST_ID = 'more-menu';

	/**
	 * @class MoreMenu
	 */
	class MoreMenu extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {object} props.layout
		 * @param {MenuNavigator} props.menuNavigator
		 */
		constructor(props)
		{
			super(props);

			this.menuNavigator = props.menuNavigator;

			this.state = {
				menuList: [],
				loading: true,
			};

			this.getTestId = createTestIdGenerator({
				prefix: MORE_MENU_TEST_ID,
			});
		}

		/**
		 * @public
		 * @return {array}
		 */
		static getDefaultMenuList()
		{
			return [];
		}

		/**
		 * @param {array} defaultList
		 * @param {array} serverList
		 * @return {array}
		 */
		static prepareMenuList(defaultList, serverList) {
			if (!Type.isArrayFilled(serverList))
			{
				return defaultList;
			}

			const mergedSections = defaultList.map((defaultSection) => {
				const serverSection = serverList.find((section) => section.id === defaultSection.id);

				return this.mergeSection(defaultSection, serverSection);
			});

			const additionalServerSections = serverList
				.filter((serverSection) => !defaultList.some(
					(defaultSection) => defaultSection.id === serverSection.id,
				));

			const allSections = [...mergedSections, ...additionalServerSections];

			return allSections.sort((a, b) => (a?.sort || 0) - (b?.sort || 0));
		}

		/**
		 * @param {object} defaultSection
		 * @param {object|null} serverSection
		 * @return {object}
		 */
		static mergeSection(defaultSection, serverSection) {
			const defaultItems = Array.isArray(defaultSection.items) ? defaultSection.items : [];
			const serverItems = Array.isArray(serverSection?.items) ? serverSection?.items : [];

			const combinedItems = [...defaultItems, ...serverItems];
			const uniqueItems = this.removeDuplicateItems(combinedItems);

			return {
				...defaultSection,
				...serverSection,
				hidden: uniqueItems.length === 0,
				items: uniqueItems.sort((a, b) => (a?.sort || 0) - (b?.sort || 0)),
			};
		}

		/**
		 * @param {array} items
		 * @return {array}
		 */
		static removeDuplicateItems(items) {
			const seenIds = new Set();

			return items.reduce((acc, item) => {
				if (item.id && !seenIds.has(item.id))
				{
					seenIds.add(item.id);
					acc.push(item);
				}

				return acc;
			}, []);
		}

		componentDidMount()
		{
			this.loadMenuList(false, true)
				.then(() => {
					SearchList.setListeners({
						getMenuList: () => this.state.menuList,
						layout: this.layout,
						testId: this.getTestId('search-list'),
					});
				})
				.catch((error) => console.error(error));
		}

		get layout()
		{
			return this.props.layout;
		}

		render()
		{
			const {
				loading,
				menuList,
				currentShift,
				workTime,

				canEditProfile,
				canUseTimeMan,
				canUseCheckIn,
			} = this.state;

			if (loading && menuList.length === 0)
			{
				return View(
					{
						style: {
							flex: 1,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					new LoadingScreenComponent({
						backgroundColor: Color.bgContentPrimary.toHex(),
					}),
				);
			}

			return View(
				{
					style: {
						backgroundColor: Color.bgContentSecondary.toHex(),
					},
				},
				this.renderSubstrate(),
				RefreshView(
					{
						refreshing: loading,
						onRefresh: this.onRefresh,
						style: {
							flex: 1,
						},
					},
					View(
						{
							style: {
								backgroundColor: Color.bgContentSecondary.toHex(),
								paddingBottom: Indent.XL3.toNumber(),
							},
						},
						new MoreMenuHeader({
							testId: this.getTestId('header'),
							canEditProfile,
							canUseTimeMan,
							canUseCheckIn,
							currentShift,
							workTime,
						}),
						this.renderCompanyPanel(),
						this.renderToolsPanel(),
						this.renderSettingsPanel(),
					),
				),
			);
		}

		renderCompanyPanel()
		{
			const {
				company,
				license,
				supportBotId,

				canUseSupport,
				canInvite,
				canUseTelephony,
				shouldShowWhatsNew,
				helpdeskUrl,
			} = this.state;

			return MoreMenuPanel(
				{
					testId: this.getTestId('company-panel'),
					children: [
						new MoreMenuCompany({
							testId: this.getTestId('company'),
							company,
							license,
							supportBotId,
							layout: this.layout,
							canUseSupport,
							canInvite,
							canUseTelephony,
							shouldShowWhatsNew,
							helpdeskUrl,
						}),
					],
					style: {
						paddingHorizontal: 0,
					},
				},
			);
		}

		renderToolsPanel()
		{
			const { menuList } = this.state;

			if (!this.shouldShowToolsPanel(menuList))
			{
				return null;
			}

			return MoreMenuPanel(
				{
					testId: this.getTestId('tools-panel'),
					title: Loc.getMessage('MENU_TOOLS_PANEL_TITLE'),
					children: [
						new ToolsList({
							testId: this.getTestId('tools-list'),
							menuList,
							counters: this.props.counters,
						}),
					],
				},
			);
		}

		shouldShowToolsPanel(menuList)
		{
			return menuList.some((section) => section?.items.length > 0);
		}

		renderSettingsPanel()
		{
			return MoreMenuPanel(
				{
					testId: this.getTestId('settings-panel'),
					title: Loc.getMessage('MENU_SETTINGS_PANEL_TITLE'),
					children: [
						new SettingsList({
							testId: this.getTestId('settings-list'),
						}),
					],
				},
			);
		}

		renderSubstrate()
		{
			return View(
				{
					style: {
						position: 'absolute',
						width: '100%',
						height: '50%',
						top: 0,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
			);
		}

		onRefresh = () => {
			Haptics.impactLight();

			return this.loadMenuList(true, false)
				.catch((error) => console.error(error));
		};

		loadMenuList = (forceRefresh = false, useCache = true) => {
			return new Promise((resolve, reject) => {
				this.setState(
					{ loading: true },
					() => {
						const request = new RunActionExecutor(
							MENU_LIST_ACTION_NAME,
							{
								forceRefresh: forceRefresh ? 1 : 0,
								userId: env.userId,
								siteId: env.siteId,
							},
						)
							.setCacheId(`mobile-more-menu${env.userId}`)
							.setCacheTtl(SECONDS_IN_DAY)
							.setCacheHandler((cachedData) => {
								if (cachedData?.status === 'success')
								{
									const { preparedMenuList, state } = MoreMenu.mapResponseData(cachedData.data || {});
									this.menuNavigator.updateMenuList(preparedMenuList);

									this.setState(state, () => {
										this.processUsers(cachedData?.data);
									});
								}
							})
							.setHandler((response) => {
								if (response?.status !== 'success')
								{
									const preparedMenuList = this.state.menuList.length > 0
										? this.state.menuList
										: MoreMenu.getDefaultMenuList();

									this.setState({
										menuList: preparedMenuList,
										loading: false,
									});

									reject(new Error('Invalid response status'));

									return;
								}

								const { preparedMenuList, state } = MoreMenu.mapResponseData(response.data || {});
								this.menuNavigator.updateMenuList(preparedMenuList);

								this.setState(state, () => {
									this.processUsers(response?.data);
									resolve();
								});
							});

						request.call(useCache);
					},
				);
			});
		};

		processUsers(data)
		{
			const users = [];

			const user = data?.user;
			if (!isEmpty(user))
			{
				users.push(user);
			}

			const companyUsers = data?.company?.users || [];
			if (!isEmpty(companyUsers))
			{
				users.push(...companyUsers);
			}

			if (users.length > 0)
			{
				dispatch(usersUpserted(users));
			}
		}

		/**
		 * @public
		 * @param {function} onCache
		 * @param {function} onResponse
		 * @param {function} onError
		 */
		static subscribeMenuData(onCache, onResponse, onError)
		{
			const request = new RunActionExecutor(
				MENU_LIST_ACTION_NAME,
				{
					forceRefresh: 0,
					userId: env.userId,
					siteId: env.siteId,
				},
			)
				.setCacheId(`mobile-more-menu${env.userId}`)
				.setCacheTtl(SECONDS_IN_DAY)
				.setCacheHandler((cachedData) => {
					onCache?.(cachedData.data || []);
				})
				.setHandler((response) => {
					if (response?.status !== 'success')
					{
						onError?.(response);

						return;
					}

					onResponse?.(response.data || []);
				})
				.setSkipRequestIfCacheExists();

			request.call(true);

			return request;
		}

		static mapResponseData(data)
		{
			const defaultList = MoreMenu.getDefaultMenuList();
			const menuListRaw = data.menuList || [];
			const preparedMenuList = MoreMenu.prepareMenuList(defaultList, menuListRaw);

			const state = {
				loading: false,

				user: data.user || null,
				menuList: preparedMenuList,
				currentShift: data.currentShift || null,
				workTime: data.workTime || null,
				company: data.company || null,
				license: data.license || null,
				helpdeskUrl: data.helpdeskUrl || null,
				supportBotId: data.supportBotId || 0,

				canEditProfile: data.canEditProfile || false,
				canUseTimeMan: data.canUseTimeMan || false,
				canUseCheckIn: data.canUseCheckIn || false,
				canUseSupport: data.canUseSupport || false,
				canInvite: data.canInvite || false,
				canUseTelephony: data.canUseTelephony || false,
				shouldShowWhatsNew: data.shouldShowWhatsNew || false,
			};

			return {
				preparedMenuList,
				state,
			};
		}
	}

	MoreMenu.propTypes = {
		layout: PropTypes.object.isRequired,
		menuNavigator: PropTypes.instanceOf(MenuNavigator),
		counters: PropTypes.object,
	};

	module.exports = { MoreMenu };
});
