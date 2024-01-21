/**
 * @module crm/document/details/error-panel
 */
jn.define('crm/document/details/error-panel', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const CrmDocumentDetailsErrorPanel = ({ title, subtitle }) => View(
		{
			style: {
				backgroundColor: AppTheme.colors.bgContentPrimary,
				borderRadius: 12,
				marginHorizontal: 16,
				paddingVertical: 27,
				paddingHorizontal: 16,
				alignItems: 'center',
			},
		},
		title && Text({
			text: title,
			style: {
				fontSize: 18,
				color: AppTheme.colors.base1,
				textAlign: 'center',
				marginBottom: 12,
			},
		}),
		subtitle && Text({
			text: subtitle,
			style: {
				fontSize: 15,
				color: AppTheme.colors.base4,
				textAlign: 'center',
			},
		}),
	);

	module.exports = { CrmDocumentDetailsErrorPanel };
});
