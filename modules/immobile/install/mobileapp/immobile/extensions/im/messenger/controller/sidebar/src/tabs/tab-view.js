/**
 * @module im/messenger/controller/sidebar/tabs/tab-view
 */
jn.define('im/messenger/controller/sidebar/tabs/tab-view', (require, exports, module) => {
	const { SidebarParticipantsView } = require(
		'im/messenger/controller/sidebar/tabs/participants/participants-view',
	);
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');

	/**
	 * @class SidebarTabView
	 * @typedef {LayoutComponent<SidebarTabViewProps, SidebarTabViewState>} SidebarTabView
	 */
	class SidebarTabView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarTabViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				tabItems: this.buildTabsData(),
				isNotes: props.isNotes,
				selectedTab: { id: 0 },
			};
		}

		/**
		 * @desc Build tabs data by object
		 * @return {object[]}
		 */
		buildTabsData()
		{
			const defaultTabs = [
				{
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_PARTICIPANTS'),
					counter: 0,
					id: 'participants',
				},
				// TODO uncommit this when layouts and scenery are ready
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_TASKS'), counter: 1, id: 'tasks' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_MEETINGS'), counter: 2, id: 'meetings' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_LINKS'), counter: 3, id: '3' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_MEDIA'), counter: 4, id: '4' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_FILES'), counter: 5, id: '5' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_AUDIO'), counter: 6, id: '6' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_SAVE'), counter: 7, id: '7' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_BRIEFS'), counter: 8, id: '8' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_SIGN'), counter: 9, id: '9' },
			];

			if (this.props.isNotes)
			{
				return defaultTabs.filter((tab) => tab.id !== 'participants');
			}

			return defaultTabs;
		}

		render()
		{
			if (this.state.tabItems.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						flex: 1,
					},
				},
				View(
					{},
					TabView({
						style: {
							height: 44,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
						params: {
							styles: {
								tabTitle: {
									underlineColor: AppTheme.colors.accentMainPrimary,
								},
							},
							items: this.state.tabItems,
						},
						onTabSelected: (tab, changed, options) => {
							if (changed) // on first render, the onTabSelected event automatically starts with options.action = 'code'
							{
								const platform = Application.getPlatform();
								if (platform === 'ios')
								{
									const newState = { selectedTab: tab };
									this.setState(newState);
								}

								if (platform !== 'ios' && options.action !== 'code')
								{
									const newState = { selectedTab: tab };
									this.setState(newState);
								}
							}
						},
					}),
				),
				this.renderSelectedTab(),
			);
		}

		renderSelectedTab()
		{
			const { selectedTab } = this.state;
			switch (selectedTab.id)
			{
				case 'participants':
					return this.renderParticipantsList();
				case 'tasks':
					return null;
				case 'meetings':
					return null;
				default:
					return this.renderParticipantsList();
			}
		}

		renderParticipantsList()
		{
			// const isFreshCache = this.props.participantsCache.isFresh();
			// const participantsCache = this.props.participantsCache.get('participants');

			// let participants = [];
			// if (isFreshCache && participantsCache && participantsCache.length > 0)
			// {
			// 	participants = participantsCache;
			// }
			// const isRefreshing = participants.length === 0;

			return new SidebarParticipantsView({
				isNotes: this.props.isNotes,
				dialogId: this.props.dialogId,
			});
		}
	}

	module.exports = { SidebarTabView };
});
