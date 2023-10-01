/**
 * @module im/messenger/controller/sidebar/tab-view
 */
jn.define('im/messenger/controller/sidebar/tab-view', (require, exports, module) => {
	const { SidebarParticipantsView } = require('im/messenger/controller/sidebar/participants-view');

	class SidebarTabView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				tabItems: props.tabItems,
				selectedTab: props.selectedTab,
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
						backgroundColor: '#FFFFFF',
						flex: 1,
					},
				},
				View(
					{
						// onPan: () => {
						// this.emit('onPan'); // TODO this is disabled, let's wait for a better solution animate scroll tabs
						// },
					},
					TabView({
						style: {
							height: 44,
							backgroundColor: '#FFFFFF',
						},
						params: {
							styles: {
								tabTitle: {
									underlineColor: '#11A9D9',
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
			const isFreshCache = this.props.participantsCache.isFresh();
			const participantsCache = this.props.participantsCache.get('participants');

			let participants = [];
			if (isFreshCache && participantsCache && participantsCache.length > 0)
			{
				participants = participantsCache;
			}
			const isRefreshing = participants.length === 0;

			return new SidebarParticipantsView({
				participants,
				permissions: this.props.permissions,
				isRefreshing,
				ref: (ref) => this.participantsTab = ref,
				parentEmitter: this.emitter,
				loc: this.props.loc,
			});
		}
	}

	module.exports = { SidebarTabView };
});
