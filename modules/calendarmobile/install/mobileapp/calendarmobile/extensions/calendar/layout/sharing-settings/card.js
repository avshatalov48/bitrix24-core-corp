/**
 * @module calendar/layout/sharing-settings/card
 */
jn.define('calendar/layout/sharing-settings/card', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const SharingSettingsCard = (props, ...views) => View(
		{
			style: {
				paddingHorizontal: 12,
				paddingTop: 12,
				paddingBottom: 14,
				backgroundColor: AppTheme.colors.bgContentPrimary,
				borderRadius: 12,
				marginBottom: 15,
			},
			...props,
		},
		...views,
	);

	module.exports = { SharingSettingsCard };
});
