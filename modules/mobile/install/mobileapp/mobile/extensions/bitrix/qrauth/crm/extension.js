/**
 * @module qrauth/crm
 */
jn.define('qrauth/crm', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/qrauth/crm/';
	/**
	 * @class CRMDescriptionLayout
	 */

	const styles = {
		crmIcon: {
			width: 38,
			height: 38,
			alignItems: 'center',
			justifyContent: 'center',
			backgroundColor: AppTheme.colors.accentSoftElementBlue1,
			marginRight: 12,
			borderRadius: 19,
		},
	};

	class CRMDescriptionLayout extends LayoutComponent
	{
		render()
		{
			return View(
				{
					backgroundColor: AppTheme.colors.bgContentPrimary,
					justifyContent: 'center',
				},
				View(
					{
						style: {
							paddingRight: 20,
							paddingTop: 20,
							paddingBottom: 20,
							paddingLeft: 10,
							flexDirection: 'column',
						},
					},
					this.titleBlock(),
					this.subtitleBlock(),
				),
				View({
					style:
						{
							height: 1,
							margin: 12,
							opacity: 0.3,
							backgroundColor: AppTheme.colors.base5,
						},
				}),
			);
		}

		subtitleBlock()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 10,
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							alignContent: 'stretch',
							display: 'flex',
							alignItems: 'center',
							flexDirection: 'row',
							width: 38,
							marginRight: 12,
						},
					},
					View({
						style: {
							width: 2,
							height: '100%',
							backgroundColor: AppTheme.colors.accentExtraDarkblue,
							opacity: 0.5,
							borderRadius: 1,
						},
					}),
				),
				Text({
					style: { flexShrink: 2, fontSize: 13, paddingTop: 2, paddingBottom: 2, opacity: 0.45 },
					text: BX.message('CRM_DESKTOP_OPEN'),
				}),
			);
		}

		titleBlock()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},

				View({
					style: styles.crmIcon,
					flexGrow: 1,
				}, Image({
					style: {
						resizeMode: 'contain',
						width: '50%',
						height: '50%',
					},
					svg: { uri: `${currentDomain}${pathToExtension}images/crm_pl.svg?2` },
				})),
				Text({
					style: { flexShrink: 2, fontSize: 16, color: AppTheme.colors.base1 },
					text: BX.message('CRM_TITLE'),
				}),
			);
		}
	}

	module.exports = {
		CRMDescriptionLayout,
	};
});

(function() {
	const require = (ext) => jn.require(ext);
	const { CRMDescriptionLayout } = require('qrauth/crm');

	jnexport([CRMDescriptionLayout, 'CRMDescriptionLayout']);
})();
