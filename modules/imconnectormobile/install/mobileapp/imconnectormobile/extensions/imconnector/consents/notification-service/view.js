/**
 * @module imconnector/consents/notification-service/view
 */
jn.define('imconnector/consents/notification-service/view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CompleteButton } = require('imconnector/lib/ui/buttons/complete');
	const { EmptyButton } = require('imconnector/lib/ui/buttons/empty');

	/**
	 * @class NotificationServiceConsentView
	 */
	class NotificationServiceConsentView extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						alignItems: 'stretch',
						justifyContent: 'space-between',
					},
					safeArea: {
						bottom: true,
						top: true,
					},
				},
				Text({
					style: {
						fontSize: 16,
						fontWeight: '500',
						textAlign: 'center',
						marginBottom: 19,
						marginTop: 20,
						marginRight: 29,
						marginLeft: 18,
					},
					text: this.props.title,
				}),
				View(
					{
						style: {
							flex: 1,
						},
					},
					WebView({
						style: {
							backgroundColor: '#FFFFFF',
							flex: 1,
							fontSize: 14,
							fontWeight: '300',
						},
						scrollDisabled: false,
						data: {
							content: `<html><body>${this.props.content}</body></html>`,
							mimeType: 'text/html',
							charset: 'UTF-8',
						},
					}),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							paddingLeft: 18,
							paddingRight: 18,
							paddingTop: 12,
							paddingBottom: 15,
							justifyContent: 'space-evenly',
							alignItems: 'center',
						},
					},
					View(
						{
							style: {
								marginRight: 9,
							},
						},
						EmptyButton({
							text: Loc.getMessage('IMCONNECTORMOBILE_CONSENT_NOTIFICATION_SERVICE_NOT_AGREE'),
							onClick: () => this.props.callback(false),
							style: {
								borderRadius: 20,
								width: 165,
								height: 42,
							},
						}),
					),
					CompleteButton({
						withoutIcon: true,
						text: Loc.getMessage('IMCONNECTORMOBILE_CONSENT_NOTIFICATION_SERVICE_AGREE'),
						style: {
							borderRadius: 20,
							width: 165,
							height: 42,
						},
						onClick: () => this.props.callback(true),
					}),
				),
			);
		}
	}

	module.exports = { NotificationServiceConsentView };
});
