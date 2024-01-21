/**
 * @module im/messenger/lib/promotion/release-view
 */
jn.define('im/messenger/lib/promotion/release-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');

	/**
	 * @class ReleaseView
	 */
	class ReleaseView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		render() {
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgNavigation,
						paddingLeft: 12,
						paddingRight: 12,
						alignItems: 'center',
						justifyContent: 'center',
						flexDirection: 'column',
						align: 'center',
					},
				},
				Video(
					{
						style: {
							width: '100%',
							height: '73%',
							backgroundColor: AppTheme.colors.bgNavigation,
							borderRadius: 14,
							alignSelf: 'center',
						},
						scaleMode: 'fill',
						uri: this.props.url,
						enableControls: false,
						loop: true,
					},
				),
				this.renderButton(),
			);
		}

		renderButton()
		{
			const minimalHeight = device.screen.height - this.props.videoHeight;
			if (minimalHeight <= 60)
			{
				return null;
			}

			return Button({
				style: {
					backgroundColor: {
						default: AppTheme.colors.accentMainPrimary,
						pressed: AppTheme.colors.accentSoftElementBlue1,
					},
					color: AppTheme.colors.baseWhiteFixed,
					height: 50,
					alignSelf: 'center',
					borderRadius: 8,
					marginTop: '5%',
					fontSize: 18,
					paddingLeft: 30,
					paddingRight: 30,
					fontWeight: '600',
					align: 'center',
				},
				text: Loc.getMessage('IMMOBILE_MESSENGER_PROMO_BUTTON'),
				onClick: () => {
					if (!PageManager.getNavigator().isActiveTab())
					{
						PageManager.getNavigator().makeTabActive();
					}

					this.props.widget.close();
				},
			});
		}
	}

	module.exports = { ReleaseView };
});
