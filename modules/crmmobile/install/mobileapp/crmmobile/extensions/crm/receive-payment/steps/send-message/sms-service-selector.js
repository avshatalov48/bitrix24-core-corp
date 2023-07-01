/**
 * @module crm/receive-payment/steps/send-message/sms-service-selector
 */
jn.define('crm/receive-payment/steps/send-message/sms-service-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class SmsServiceSelector
	 */
	class SmsServiceSelector extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state.selectedSmsSenderId = props.selectedSmsSenderId;
		}

		render()
		{
			const sendingMethodText = Loc.getMessage('M_RP_SM_SENDING_VIA_SMS_SERVICE');
			const selectedSmsSender = this.props.smsSenders.find((sender) => sender.id === this.state.selectedSmsSenderId);
			const selectedSmsSenderName = selectedSmsSender.name;

			let menu;
			const actions = [];
			this.props.smsSenders.forEach((smsSender) => {
				actions.push({
					id: smsSender.id,
					title: smsSender.name,
					onClickCallback: (id) => {
						menu.close(() => {
							this.setState({ selectedSmsSenderId: id });
							this.props.onSelect(id);
						});

						return Promise.resolve({ closeMenu: false });
					},
				});
			});
			actions.push({
				id: 'settings',
				title: Loc.getMessage('M_RP_SM_SMS_SERVICE_SETTINGS_TITLE'),
				data: {
					svgIconAfter: {
						type: ContextMenuItem.ImageAfterTypes.WEB,
					},
				},
				onClickCallback: (itemId) => {
					menu.close(() => {
						qrauth.open({
							title: Loc.getMessage('M_RP_SM_SMS_SERVICE_SETTINGS_TITLE'),
							redirectUrl: '/saleshub/',
						});
					});
				},
			});
			menu = new ContextMenu({
				actions,
				params: {
					title: Loc.getMessage('M_RP_SM_SMS_SERVICE_MENU_TITLE'),
					showActionLoader: false,
					showCancelButton: true,
				},
			});

			return View(
				{
					style: {
						flexDirection: 'row',
						width: '100%',
						marginTop: 14,
						marginBottom: 20,
						justifyContent: 'center',
						flexWrap: 'wrap',
						paddingHorizontal: 16,
					},
				},
				Text({
					style: {
						color: '#a8adb4',
						fontSize: 12,
					},
					text: `${sendingMethodText} `,
				}),
				View(
					{
						onClick: () => {
							menu.show();
						},
					},
					Text({
						style: {
							color: '#828B95',
							fontSize: 12,
							borderBottomWidth: 1,
							borderBottomColor: '#cdd1d5',
							borderStyle: 'dash',
							borderDashSegmentLength: 3,
							borderDashGapLength: 3,
						},
						text: selectedSmsSenderName,
					}),
				),
			);
		}
	}

	module.exports = { SmsServiceSelector };
});
