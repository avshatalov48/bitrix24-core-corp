/**
 * @module utils/skeleton
 */
jn.define('utils/skeleton', (require, exports, module) => {
	const { Color } = require('tokens');
	const { ShimmerView } = require('layout/polyfill');

	const DEFAULT_BG = Color.base6.toHex();

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

	function CircleStack({ count, size = 24, offsetRatio = 0.6 })
	{
		const offset = size * offsetRatio;
		const containerWidth = size + offset * (count - 1);

		return View(
			{
				style: {
					position: 'relative',
					width: containerWidth,
					height: size,
				},
			},
			...Array.from({ length: count }, (_, index) => View(
				{
					key: index.toString(),
					style: {
						position: 'absolute',
						left: index * offset,
						zIndex: index,
					},
				},
				ShimmerView(
					{ animating: true },
					View({
						style: {
							width: size,
							height: size,
							borderRadius: size / 2,
							backgroundColor: DEFAULT_BG,
						},
					}),
				),
			)),
		);
	}

	module.exports = {
		Line,
		Circle,
		CircleStack,
	};
});
