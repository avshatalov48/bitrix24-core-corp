/**
 * @module im/messenger/controller/sidebar/chat/tabs/tab-view
 */

jn.define('im/messenger/controller/sidebar/chat/tabs/tab-view', (require, exports, module) => {
	const { SidebarParticipantsView } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-view');
	const { SidebarFilesView } = require('im/messenger/controller/sidebar/chat/tabs/files/view');
	const { SidebarLinksView } = require('im/messenger/controller/sidebar/chat/tabs/links/view');
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	const { Feature } = require('im/messenger/lib/feature');
	const { SidebarTab } = require('im/messenger/const');

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
				selectedTab: { id: props.hideParticipants ? SidebarTab.document : SidebarTab.participant },
			};
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
						backgroundColor: Theme.colors.bgContentPrimary,
						flex: 1,
					},
				},
				View(
					{},
					TabView({
						style: {
							height: 51,
							backgroundColor: Theme.colors.bgContentPrimary,
						},
						params: {
							styles: {
								tabTitle: {
									underlineColor: this.props.isCopilot
										? Theme.colors.accentMainCopilot
										: Theme.colors.accentMainPrimary,
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

								return;
							}

							BX.onCustomEvent('onCurrentTabSelected', tab.id);
						},
					}),
				),
				this.renderSelectedTab(),
			);
		}

		/**
		 * @return {object}
		 */
		getTitleTabs()
		{
			return {
				[SidebarTab.participant]: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_PARTICIPANTS'),
				[SidebarTab.document]: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_DOCUMENTS'),
				[SidebarTab.link]: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_LINKS'),
			};
		}

		/**
		 * @desc Build tabs data by object
		 * @return {{title: string, id: string}[]}
		 */
		buildTabsData()
		{
			return this.getAvailableTabs().map((tab) => {
				return {
					title: this.getTitleTabs()[tab],
					id: tab,
				};
			});
		}

		/**
		 * @return {string[]}
		 */
		getAvailableTabs()
		{
			const tabsSet = new Set(Object.values(SidebarTab));

			if (this.props.hideParticipants)
			{
				tabsSet.delete(SidebarTab.participant);
			}

			if (this.props.isCopilot || !Feature.isSidebarFilesEnabled)
			{
				tabsSet.delete(SidebarTab.document);
			}

			if (this.props.isCopilot || !Feature.isSidebarLinksEnabled)
			{
				tabsSet.delete(SidebarTab.link);
			}

			return [...tabsSet];
		}

		renderSelectedTab()
		{
			const { selectedTab } = this.state;
			switch (selectedTab.id)
			{
				case SidebarTab.participant:
					return this.renderParticipantsList();
				case 'tasks':
					return null;
				case SidebarTab.document:
					return this.renderFilesList();
				case SidebarTab.link:
					return this.renderLinksList();
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
				isCopilot: this.props.isCopilot,
				dialogId: this.props.dialogId,
				id: SidebarTab.participant,
			});
		}

		renderFilesList()
		{
			return new SidebarFilesView({
				dialogId: this.props.dialogId,
				id: SidebarTab.document,
			});
		}

		renderLinksList()
		{
			return new SidebarLinksView({
				dialogId: this.props.dialogId,
				id: SidebarTab.link,
			});
		}
	}

	module.exports = { SidebarTabView };
});
