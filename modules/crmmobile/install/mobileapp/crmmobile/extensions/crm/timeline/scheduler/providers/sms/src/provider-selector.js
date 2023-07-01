/**
 * @module crm/timeline/scheduler/providers/sms/provider-selector
 */
jn.define('crm/timeline/scheduler/providers/sms/provider-selector', (require, exports, module) => {
	const { Loc } = require('loc');

	/**
	 * @class ProviderSelector
	 */
	class ProviderSelector
	{
		constructor({ senders, currentSender, onChangeSenderCallback, contactCenterUrl })
		{
			this.senders = senders;
			this.currentSender = currentSender;
			this.onChangeSenderCallback = onChangeSenderCallback;
			this.contactCenterUrl = contactCenterUrl;

			this.onProviderActionClick = this.onProviderActionClick.bind(this);
			this.onConnectOtherProviderClick = this.onConnectOtherProviderClick.bind(this);

			this.menu = new ContextMenu({
				testId: 'SMS_SETTINGS_PROVIDER_MENU',
				actions: this.getProviderMenuActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_PROVIDER'),
				},
			});
		}

		getProviderMenuActions()
		{
			const actions = [];

			this.senders.forEach((item) => actions.push(this.getProviderAction(item)));
			actions.push(this.getConnectOtherProviderAction());

			return actions;
		}

		getProviderAction(item)
		{
			const isSelected = (item.id === this.currentSender.id);

			return {
				id: item.id,
				sectionCode: 'available-providers',
				title: item.shortName,
				isSelected,
				showSelectedImage: isSelected,
				isDisabled: !item.canUse,
				onClickCallback: this.onProviderActionClick,
			};
		}

		onProviderActionClick(senderId)
		{
			const sender = this.findSenderById(senderId);
			if (this.currentSender !== sender)
			{
				this.currentSender = sender;
				this.menu.setSelectedActions([senderId]);

				const phone = sender.fromList[0].name;
				this.onChangeSenderCallback({
					sender,
					phone,
				});
			}

			return Promise.resolve();
		}

		getConnectOtherProviderAction()
		{
			return {
				id: 'connect-other-provider',
				sectionCode: 'other-providers',
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_CONNECT_OTHER_PROVIDER'),
				data: {
					svgIconAfter: {
						content: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.94083 2.46983V0H2.33333C1.04467 0 0 1.04467 0 2.33333V11.6667C0 12.9553 1.04467 14 2.33333 14H11.6667C12.9553 14 14 12.9553 14 11.6667V9.058H11.529L11.5294 10.3627L11.5216 10.4988C11.4542 11.079 10.9611 11.5294 10.3627 11.5294H3.63725L3.5012 11.5216C2.92097 11.4542 2.47059 10.9611 2.47059 10.3627V3.63725L2.47844 3.5012C2.54583 2.92097 3.03895 2.47059 3.63725 2.47059L4.94083 2.46983Z" fill="#828B95"/><path d="M14 0.4V6.68753C14 6.95479 13.6769 7.08865 13.4879 6.89967L11.1964 4.60833L7.96504 7.84044C7.84789 7.95761 7.65792 7.95763 7.54076 7.84046L6.21804 6.51775C6.1009 6.4006 6.10088 6.21067 6.21801 6.09351L9.44994 2.86067L7.10061 0.512169C6.91157 0.323195 7.04541 0 7.31271 0H13.6C13.8209 0 14 0.179086 14 0.4Z" fill="#828B95"/></svg>',
					},
				},
				onClickCallback: this.onConnectOtherProviderClick,
			};
		}

		onConnectOtherProviderClick()
		{
			qrauth.open({
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_TO_LOGIN_ON_DESKTOP_MSGVER_1'),
				redirectUrl: this.contactCenterUrl,
				layout: this.menu.layoutWidget,
			});

			return Promise.resolve({ closeMenu: false });
		}

		findSenderById(id)
		{
			return this.senders.find((sender) => sender.id === id);
		}

		show(parentWidget = PageManager)
		{
			this.menu.show(parentWidget);
		}
	}

	module.exports = { ProviderSelector };
});
