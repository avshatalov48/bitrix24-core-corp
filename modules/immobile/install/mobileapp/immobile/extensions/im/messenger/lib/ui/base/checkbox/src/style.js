/**
 * @module im/messenger/lib/ui/base/checkbox/style
 */
jn.define('im/messenger/lib/ui/base/checkbox/style', (require, exports, module) => {

	const AppTheme = require('apptheme');
	const checkboxStyle = {
		size: 24,
		borderColor: AppTheme.colors.base5,
		alignContent: 'center',
		justifyContent: 'center',
		icon: {
			enable: AppTheme.colors.accentMainPrimary,
			disable: AppTheme.colors.accentSoftGray2,
		},
	};

	module.exports = { checkboxStyle };
});