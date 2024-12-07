/**
 * @module imconnector/lib/ui/buttons/qr/qr-view
 */
jn.define('imconnector/lib/ui/buttons/qr/qr-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { EmptyButton } = require('imconnector/lib/ui/buttons/empty');
	class QrView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						paddingTop: 60,
						paddingLeft: 45,
						paddingRight: 45,
						paddingBottom: 43,
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							borderColor: AppTheme.colors.accentMainPrimary,
							borderWidth: 2,
							borderRadius: 12,
							width: 200,
							height: 200,
							alignItems: 'center',
							justifyContent: 'center',
							marginBottom: 36,
						},
					},
					Image({
						style: {
							width: 180,
							height: 180,
						},
						base64: this.props.image,
						resizeMode: 'cover',
					}),
				),
				Text({
					style: {
						color: AppTheme.colors.base1,
						fontSize: 17,
						fontWeight: '500',
						textAlign: 'center',
						numberOfLines: 0,
						ellipsize: 'end',
						marginBottom: 32,
					},
					text: Loc.getMessage('IMCONNECTORMOBILE_QR_BUTTON_VIEW_CONTENT'),
				}),
				EmptyButton({
					text: Loc.getMessage('IMCONNECTORMOBILE_QR_BUTTON_VIEW_BUTTON'),
					onClick: () => {
						this.props.layoutWidget.close();
					},
					style: {
						borderRadius: 20,
						width: 282,
						height: 45,
					},
				}),

			);
		}
	}

	module.exports = { QrView };
});
