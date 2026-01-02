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
		backgroundColor: {
			unchecked: {
				enabled: Theme.colors.baseWhiteFixed,
				disable: Theme.colors.baseWhiteFixed,
			},
			checked: {
				enabled: Theme.colors.accentMainPrimary,
				disabled: Theme.colors.accentSoftGray2,
			},
		},
		icon: {
			color: Theme.colors.baseWhiteFixed,
		},
	};

	module.exports = { checkboxStyle };
});
