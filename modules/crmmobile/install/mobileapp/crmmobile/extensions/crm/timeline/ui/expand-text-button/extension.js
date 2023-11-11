/**
 * @module crm/timeline/ui/expand-text-button
 */
jn.define('crm/timeline/ui/expand-text-button', (require, exports, module) => {
	const { transparent } = require('utils/color');
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
		Shadow(
			{
				style: {
					borderRadius: 64,
				},
				radius: 2,
				color: transparent('#000', 0.08),
				offset: {
					x: 0,
					y: 1,
				},
			},
			View(
				{
					style: {
						backgroundColor,
						borderRadius: 64,
						borderWidth: 1,
						borderColor: transparent('#000', 0.1),
						paddingVertical: 4,
						paddingHorizontal: 12,
						minWidth: 140,
					},
				},
				Text({
					text,
					style: {
						color: '#828B95',
						fontSize: 13,
						textAlign: 'center',
					},
				}),
			),
		),
	);

	module.exports = { ExpandTextButton };
});
