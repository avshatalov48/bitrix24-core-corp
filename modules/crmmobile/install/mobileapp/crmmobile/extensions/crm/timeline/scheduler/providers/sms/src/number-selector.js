/**
 * @module crm/timeline/scheduler/providers/sms/number-selector
 */
jn.define('crm/timeline/scheduler/providers/sms/number-selector', (require, exports, module) => {
	const { Loc } = require('loc');

	/**
	 * @class NumberSelector
	 */
	class NumberSelector
	{
		constructor({ sender, currentPhone, onChangePhoneCallback })
		{
			this.sender = sender;
			this.currentPhone = currentPhone;
			this.onChangePhoneCallback = onChangePhoneCallback;

			this.onPhoneActionClick = this.onPhoneActionClick.bind(this);

			this.menu = new ContextMenu({
				testId: 'SMS_SETTINGS_NUMBER_MENU',
				actions: this.getActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_PHONE'),
				},
			});
		}

		getActions()
		{
			const actions = [];

			this.sender.fromList.forEach((item) => actions.push({
				id: item.id,
				title: item.name,
				isSelected: (item.name === this.currentPhone),
				showSelectedImage: true,
				onClickCallback: this.onPhoneActionClick,
			}));

			return actions;
		}

		onPhoneActionClick(phoneId)
		{
			const phoneItem = this.sender.fromList.find((item) => item.id === phoneId);
			const phone = phoneItem.name;

			if (this.currentPhone === phone)
			{
				return Promise.resolve();
			}

			this.currentPhone = phone;
			this.menu.setSelectedActions([phone]);

			this.onChangePhoneCallback({ phone });

			return Promise.resolve();
		}

		show(parentWidget = PageManager)
		{
			void this.menu.show(parentWidget);
		}
	}

	module.exports = { NumberSelector };
});
