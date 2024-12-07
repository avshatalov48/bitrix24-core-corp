/**
 * @module crm/timeline/ui/senders-selector/from-selector
 */
jn.define('crm/timeline/ui/senders-selector/from-selector', (require, exports, module) => {
	const { Loc } = require('loc');

	const SENDER_TYPE_EMAIL = 'EMAIL';

	/**
	 * @class FromSelector
	 */
	class FromSelector
	{
		constructor({ sender, fromId, onChangeFromCallback })
		{
			this.sender = sender;
			this.fromId = fromId;
			this.onChangeFromCallback = onChangeFromCallback;

			this.onFromActionClick = this.onFromActionClick.bind(this);

			let title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_PHONE');
			if (sender?.typeId === SENDER_TYPE_EMAIL)
			{
				title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_EMAIL');
			}

			this.menu = new ContextMenu({
				testId: 'crmmobile-senders-selector-from-menu',
				actions: this.getActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title,
				},
			});
		}

		getActions()
		{
			const actions = [];

			this.sender.fromList.forEach((item) => actions.push({
				id: item.id,
				title: item.name,
				isSelected: (item.id === this.fromId),
				showSelectedImage: true,
				onClickCallback: this.onFromActionClick,
			}));

			return actions;
		}

		onFromActionClick(fromId)
		{
			if (this.fromId === fromId)
			{
				return Promise.resolve();
			}

			this.fromId = fromId;
			this.menu.setSelectedActions([fromId]);

			this.onChangeFromCallback({ fromId });

			return Promise.resolve();
		}

		show(parentWidget = PageManager)
		{
			void this.menu.show(parentWidget);
		}
	}

	module.exports = { FromSelector };
});
