/**
 * @module calendar/layout/ui/loader
 */
jn.define('calendar/layout/ui/loader', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const CalendarLoader = () => View(
		{
			style: {
				alignItems: 'center',
				justifyContent: 'center',
				flexDirection: 'row',
				flexGrow: 1,
			},
		},
		Loader({
			style: {
				width: 50,
				height: 50,
			},
			tintColor: AppTheme.colors.base3,
			animating: true,
			size: 'large',
		}),
	);

	module.exports = { CalendarLoader };
});
