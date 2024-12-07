/**
 * @module im/messenger/controller/channel-creator/step/add-subscribers
 */
jn.define('im/messenger/controller/channel-creator/step/add-subscribers', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	const { UserSearchController } = require('im/messenger/controller/search');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MultiSelector } = require('im/messenger/lib/ui/selector');

	const { Step } = require('im/messenger/controller/channel-creator/step/base');

	class AddSubscribersStep extends Step
	{
		/**
		 * @return WidgetTitleParamsType
		 */
		static getTitleParams()
		{
			return {
				text: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_ADD_SUBSCRIBERS_TITLE'),
			};
		}

		/**
		 * @return Array<PageManagerButton>
		 */
		getRightButtons()
		{
			return [
				{
					id: 'createChannel',
					name: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_CREATE'),
					callback: () => {
						this.goToNextStep();
					},
					color: Theme.colors.accentMainLink === '#FFFFFF'
						? Theme.colors.accentMainLinks
						: Theme.colors.accentMainLink
					,
				},
			];
		}

		getStepData()
		{
			return {
				recipientList: this.getSelectedItems(),
			};
		}

		constructor(props, layoutWidget)
		{
			super(props, layoutWidget);
			/** @type {MultiSelector} */
			this.selectorRef = new MultiSelector(
				{
					recentText: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_STEP_ADD_SUBSCRIBERS_LIST_BADGE'),
					itemList: props.userList ?? [],
					searchMode: 'inline',
					carouselSize: 'L',
					onSearchShow: () => this.searchShow(),
					onSearchClose: () => this.searchClose(),
					onChangeText: (text) => this.search(text),
					isFakeTabsEnabled: Theme.isDesignSystemSupported && MessengerParams.get('HUMAN_RESOURCES_STRUCTURE_AVAILABLE', 'N') === 'Y',
					ref: (ref) => this.selectorRef = ref,
				},
			);

			this.searchController = new UserSearchController(this.selectorRef);
		}

		render()
		{
			return this.selectorRef;
		}

		search(query)
		{
			if (query === '')
			{
				this.selectorRef.showMainContent();

				return;
			}
			this.searchController.setSearchText(query);
		}

		searchShow()
		{
			this.searchController.open();
		}

		searchClose() {
			this.selectorRef.showMainContent();
			this.selectorRef.disableShadow();
		}

		getSelectedItems()
		{
			return this.selectorRef.getSelectedItems();
		}
	}

	module.exports = {
		AddSubscribersStep,
	};
});
