/**
 * @module utils/skeleton
 */
jn.define('utils/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Feature } = require('feature');
	const { ShimmerView } = require('layout/polyfill');

	const DEFAULT_BG = Feature.isAirStyleSupported() ? AppTheme.realColors.base6 : AppTheme.colors.base6;

	function Line(width, height, marginTop = 0, marginBottom = 0, borderRadius = null)
	{
		const viewStyles = {
			width,
			height,
		};

		if (marginTop)
		{
			viewStyles.marginTop = marginTop;
		}

		if (marginBottom)
		{
			viewStyles.marginBottom = marginBottom;
		}

		if (borderRadius === null)
		{
			borderRadius = height / 2;
		}

		const lineStyles = {
			width,
			height,
			borderRadius,
			backgroundColor: DEFAULT_BG,
		};

		return View(
			{ style: viewStyles },
			ShimmerView(
				{ animating: true },
				View({ style: lineStyles }),
			),
		);
	}

	const Circle = (size = 24) => ShimmerView(
		{ animating: true },
		View(
			{
				style: {
					width: size,
					height: size,
					borderRadius: Math.ceil(size / 2),
					backgroundColor: DEFAULT_BG,
				},
			},
		),
	);

	module.exports = {
		Line,
		Circle,
	};
});
