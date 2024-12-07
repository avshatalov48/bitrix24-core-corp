/**
 * @module im/messenger/lib/ui/base/checkbox/style
 */
jn.define('im/messenger/lib/ui/base/checkbox/style', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const checkboxStyle = {
		size: 24,
		borderColor: Theme.colors.base5,
		alignContent: 'center',
		justifyContent: 'center',
		icon: {
			enable: Theme.colors.accentMainPrimary,
			disable: Theme.colors.accentSoftGray2,
		},
	};

	module.exports = { checkboxStyle };
});