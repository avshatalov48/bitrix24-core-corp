/**
 * @module more-menu/block/settings-list
 */
jn.define('more-menu/block/settings-list', (require, exports, module) => {
	const { List } = require('more-menu/ui/list');
	const { Loc } = require('loc');
	const {
		handleItemClick,
		getUpdateSectionsWithCounters,
	} = require('more-menu/utils');
	const { Tourist } = require('tourist');

	const SETTINGS_LIST = [
		{
			id: 'settings',
			code: 'settings',
			sort: 800,
			hidden: false,
			items: [
				{
					id: 'bottom_menu',
					imageName: 'bottom_menu',
					title: Loc.getMessage('MENU_SETTINGS_SECTION_BOTTOM_MENU'),
					sort: 100,
					path: '/settings/tab.presets',
					params: {
						counter: 'menu_tab_presets',
					},
				},
				{
					id: 'notifications',
					imageName: 'notification',
					title: Loc.getMessage('MENU_SETTINGS_SECTION_NOTIFICATIONS'),
					sort: 200,
					path: '/settings/notifications',
					params: {
						analytics: {
							tool: 'settings',
							category: 'notification_settings',
							event: 'start_page',
							c_section: 'ava_menu',
						},
					},
				},
				{
					id: 'settings',
					imageName: 'settings',
					title: Loc.getMessage('MENU_SETTINGS_SECTION_SETTINGS_MSGVER_1'),
					sort: 300,
					path: '/settings/general',
					params: {
						analytics: {
							tool: 'settings',
							category: 'settings',
							event: 'switch_account',
							c_section: 'ava_menu',
						},
					},
				},
				{
					id: 'go_to_web',
					imageName: 'go_to',
					title: Loc.getMessage('MENU_SETTINGS_SECTION_GO_TO_WEB'),
					sort: 500,
					path: '/settings/go-to-web',
					params: {
						title: Loc.getMessage('MENU_SETTINGS_SECTION_GO_TO_WEB_COMPONENT_TITLE'),
						hintText: Loc.getMessage('MENU_SETTINGS_SECTION_GO_TO_WEB_COMPONENT_HINT_TEXT'),
						analyticsSection: 'menu',
					},
				},
				{
					id: 'change_portal',
					imageName: 'change_order',
					title: Loc.getMessage('MENU_BITRIX24_SECTION_CHANGE_PORTAL'),
					sort: 600,
					path: '/change-portal/',
					params: {
						analytics: {
							tool: 'intranet',
							category: 'activation',
							event: 'switch_account',
							c_section: 'ava_menu',
						},
					},
				},
			],
		},
	];

	/**
	 * @class SettingsList
	 */
	class SettingsList extends LayoutComponent
	{
		/**
		 * @param props
		 * @param {string} props.testId
		 */
		constructor(props)
		{
			super(props);

			this.currentCounters = this.initCounters();

			this.state = {
				sections: getUpdateSectionsWithCounters(SETTINGS_LIST, this.currentCounters),
			};

			this.subscribeToUserCounters = this.subscribeToUserCounters.bind(this);
		}

		componentDidMount()
		{
			BX.addCustomEvent('onSetUserCounters', this.subscribeToUserCounters);
		}

		subscribeToUserCounters(counters)
		{
			if (counters[env.siteId])
			{
				this.updateCounters(counters[env.siteId]);
			}
		}

		updateCounters(newCounters)
		{
			this.currentCounters = {
				...this.currentCounters,
				...newCounters,
			};

			const updatedSections = getUpdateSectionsWithCounters(this.state.sections, this.currentCounters);

			this.setState({
				sections: updatedSections,
			});
		}

		render()
		{
			return new List({
				testId: this.props.testId || 'more-menu-settings-list',
				structure: this.state.sections,
				onItemClick: handleItemClick,
			});
		}

		initCounters()
		{
			return {
				menu_tab_presets: Tourist.firstTime('visited_tab_presets') ? 1 : 0,
			};
		}
	}

	module.exports = { SettingsList };
});
