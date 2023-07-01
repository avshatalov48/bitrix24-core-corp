/**
 * @module crm/timeline/ui/senders-selector/number-selector
 */
jn.define('crm/timeline/ui/senders-selector/number-selector', (require, exports, module) => {
	const { Loc } = require('loc');

	/**
	 * @class NumberSelector
	 */
	class NumberSelector
	{
		constructor({ sender, phoneId, onChangePhoneCallback })
		{
			this.sender = sender;
			this.phoneId = phoneId;
			this.onChangePhoneCallback = onChangePhoneCallback;

			this.onPhoneActionClick = this.onPhoneActionClick.bind(this);

			this.menu = new ContextMenu({
				testId: 'SENDERS_SELECTOR_NUMBER_MENU',
				actions: this.getActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_PHONE'),
				},
			});
		}

		getActions()
		{
			const actions = [];

			this.sender.fromList.forEach((item) => actions.push({
				id: item.id,
				title: item.name,
				isSelected: (item.id === this.phoneId),
				showSelectedImage: true,
				onClickCallback: this.onPhoneActionClick,
			}));

			return actions;
		}

		onPhoneActionClick(phoneId)
		{
			if (this.phoneId === phoneId)
			{
				return Promise.resolve();
			}

			this.phoneId = phoneId;
			this.menu.setSelectedActions([phoneId]);

			this.onChangePhoneCallback({ phoneId });

			return Promise.resolve();
		}

		show(parentWidget = PageManager)
		{
			void this.menu.show(parentWidget);
		}
	}

	module.exports = { NumberSelector };
});
