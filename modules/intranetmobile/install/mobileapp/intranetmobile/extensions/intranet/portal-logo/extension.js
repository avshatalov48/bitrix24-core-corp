/**
 * @module intranet/portal-logo
 */
jn.define('intranet/portal-logo', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Color, Indent } = require('tokens');
	const { withCurrentDomain } = require('utils/url');

	/**
	 * @class PortalLogo
	 */
	class PortalLogo extends LayoutComponent
	{
		static getPortalLogo()
		{
			return BX.rest.callMethod('intranet.portal.getLogo');
		}

		getLogo()
		{
			return Image(
				{
					resizeMode: 'contain',
					uri: this.props.logo,
					style: {
						height: this.props.width ?? 50,
						width: this.props.width ?? 200,
					},
				},
			);
		}

		getDefaultLogo()
		{
			const color = AppTheme.id === 'light' ? 'black' : 'white';

			const imageUri = this.props.defaultLogo[color];

			return Image({
				svg: {
					uri: encodeURI(withCurrentDomain(imageUri)),
				},
				resizeMode: 'contain',
				style: {
					height: 39,
					width: 147,
				},
			});
		}

		getPortalTitle()
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'row',
					},
				},
				Text({
					style: {
						fontSize: this.props.title ? this.getTitleFontSize(this.props.title.length) : 24,
						fontWeight: 'bold',
						color: Color.base0.toHex(),
					},
					text: this.props.title,
				}),
				Text({
					style: {
						fontWeight: 'bold',
						fontSize: this.props.title ? this.getTitleFontSize(this.props.title.length) : 24,
						color: Color.accentMainPrimary.toHex(),
					},
					text: this.props.logo24,
				}),
			);
		}

		getTitleFontSize(titleLength)
		{
			if (!titleLength)
			{
				return 24;
			}

			const size = 288 / titleLength;

			return size < 24 ? size : 24;
		}

		render()
		{
			let logoView = null;

			if (this.props.logo)
			{
				logoView = this.getLogo();
			}
			else if (this.props.title)
			{
				logoView = this.getPortalTitle();
			}
			else if (this.props.defaultLogo)
			{
				logoView = this.getDefaultLogo();
			}

			return View(
				{
					style: {
						width: this.props.width ?? 200,
						height: this.props.height ?? 50,
						alignSelf: 'center',
						marginBottom: this.props.marginBottom ?? Indent.M.toNumber(),
						marginTop: this.props.marginTop ?? Indent.S.toNumber(),
						display: 'flex',
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				logoView,
			);
		}
	}

	module.exports = {
		PortalLogo,
	};
});
