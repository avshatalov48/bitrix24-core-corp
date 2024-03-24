/**
 * @module crm/crm-mode/wizard/layouts/mode/label
 */
jn.define('crm/crm-mode/wizard/layouts/mode/label', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @function label
	 */
	const label = ({ text, color, backgroundColor }) => View(
		{
			style: {
				flexDirection: 'row',
			},
		},
		View(
			{
				style: {
					backgroundColor,
					paddingVertical: isAndroid ? 3 : 5,
					paddingHorizontal: 7,
					borderWidth: 2,
					borderColor: AppTheme.colors.bgContentPrimary,
					borderRadius: 60,
				},
			},
			Text(
				{
					text,
					style: {
						fontWeight: '600',
						fontSize: 11,
						color,
					},
				},
			),
		),
	);

	module.exports = { label };
});
