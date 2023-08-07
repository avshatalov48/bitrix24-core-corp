/**
 * @module utils/skeleton
 */
jn.define('utils/skeleton', (require, exports, module) => {
	const { ShimmerView } = require('layout/polyfill');

	function line(width, height, marginTop = 0, marginBottom = 0, borderRadius = null)
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
			backgroundColor: '#dfe0e3',
		};

		return View(
			{ style: viewStyles },
			ShimmerView(
				{ animating: true },
				View({ style: lineStyles }),
			),
		);
	}

	module.exports = {
		line,
	};
});
