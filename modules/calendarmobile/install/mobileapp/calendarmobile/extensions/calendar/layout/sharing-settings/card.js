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
				backgroundColor: AppTheme.colors.base8,
				borderRadius: 12,
				marginBottom: 10,
			},
			...props,
		},
		...views,
	);

	module.exports = { SharingSettingsCard };
});
