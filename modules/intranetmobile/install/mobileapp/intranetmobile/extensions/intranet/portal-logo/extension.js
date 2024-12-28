/**
 * @module intranet/portal-logo
 */
jn.define('intranet/portal-logo', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Color, Indent } = require('tokens');
	const { withCurrentDomain } = require('utils/url');
	const { Text } = require('ui-system/typography/text');

	/**
	 * @class PortalLogo
	 */
	class PortalLogo extends LayoutComponent
	{
		getLogo()
		{
			return Image(
				{
					resizeMode: 'contain',
					uri: currentDomain + this.props.portalLogo.logo.src,
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

			const imageUri = this.props.portalLogo.defaultLogo[color];

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
			const { title, logo24 } = this.props.portalLogo;
			const fontSize = title ? this.getTitleFontSize(title.length) : 24;

			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'row',
					},
				},
				Text({
					style: {
						fontSize,
					},
					accent: true,
					color: Color.base0,
					text: title,
				}),
				Text({
					style: {
						fontSize,
					},
					accent: true,
					color: Color.accentMainPrimary,
					text: logo24,
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

			if (this.props.portalLogo.logo)
			{
				logoView = this.getLogo();
			}
			else if (this.props.portalLogo.title)
			{
				logoView = this.getPortalTitle();
			}
			else
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
