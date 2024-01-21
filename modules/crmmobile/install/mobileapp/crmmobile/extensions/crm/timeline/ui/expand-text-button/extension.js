/**
 * @module crm/timeline/ui/expand-text-button
 */
jn.define('crm/timeline/ui/expand-text-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const ExpandTextButton = ({ onClick, backgroundColor, text }) => View(
		{
			onClick,
			style: {
				position: 'absolute',
				width: device.screen.width,
				height: 24,
				bottom: 0,
				left: -20,
				flexDirection: 'row',
				justifyContent: 'center',
			},
		},
		View(
			{
				style: {
					backgroundColor,
					borderRadius: 64,
					borderWidth: 1,
					borderColor: AppTheme.colors.bgSeparatorPrimary,
					paddingVertical: 4,
					paddingHorizontal: 12,
					minWidth: 140,
				},
			},
			Text({
				text,
				style: {
					color: AppTheme.colors.base3,
					fontSize: 13,
					textAlign: 'center',
				},
			}),
		),
	);

	module.exports = { ExpandTextButton };
});
