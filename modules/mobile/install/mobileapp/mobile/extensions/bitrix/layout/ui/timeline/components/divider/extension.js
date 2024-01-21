/**
 * @module layout/ui/timeline/components/divider
 */

jn.define('layout/ui/timeline/components/divider', (require, exports, module) => {
	const AppTheme = require('apptheme');

	function Divider({ text, color, textColor = AppTheme.colors.baseWhiteFixed, counter = {}, showLine = true, onLayout })
	{
		return View(
			{
				onLayout,
				style: {
					flexDirection: 'row',
					justifyContent: 'center',
					marginBottom: 16,
				},
			},
			showLine && Line(),
			Badge({
				color,
				text,
				textColor,
				counter,
			}),
		);
	}

	function Line()
	{
		return View(
			{
				style: {
					height: 1,
					width: '100%',
					backgroundColor: AppTheme.colors.base6,
					position: 'absolute',
					top: 10,
				},
			},
		);
	}

	function Badge({ color, text, textColor, counter = {} })
	{
		return View(
			{
				style: {
					backgroundColor: color,
					borderRadius: 100,
					paddingHorizontal: 18,
					height: 21,
					flexDirection: 'row',
					justifyContent: 'center',
				},
			},
			Text({
				text,
				style: {
					color: textColor,
					fontSize: 11,
					fontWeight: '700',
				},
			}),
			Counter(counter),
		);
	}

	function Counter({ value, backgroundColor, borderColor })
	{
		value = parseInt(value, 10);
		if (!value)
		{
			return null;
		}

		backgroundColor = backgroundColor || AppTheme.colors.accentSoftElementRed1;
		borderColor = borderColor || backgroundColor;

		return View(
			{
				style: {
					backgroundColor,
					borderColor,
					borderWidth: 1,
					borderRadius: 100,
					paddingHorizontal: 7,
					height: 16,
					flexDirection: 'column',
					justifyContent: 'center',
					marginLeft: 4,
					marginTop: 4,
				},
			},
			Text({
				text: String(value),
				style: {
					color: AppTheme.colors.baseWhiteFixed,
					fontSize: 10,
					fontWeight: 'bold',
				},
			}),
		);
	}

	module.exports = { Divider }
});
