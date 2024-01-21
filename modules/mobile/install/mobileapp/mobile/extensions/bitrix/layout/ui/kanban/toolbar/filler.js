/**
 * @module layout/ui/kanban/toolbar/filler
 */
jn.define('layout/ui/kanban/toolbar/filler', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { mergeImmutable } = require('utils/object');
	const { ShimmerView } = require('layout/polyfill');

	const Filler = (width, customStyle = {}) => {
		const style = mergeImmutable({
			height: 6,
			width,
			marginTop: Application.getPlatform() === 'android' ? 10 : 8,
		}, customStyle);

		const { marginTop, marginBottom, width: finalWidth } = style;

		return View(
			{
				style: {
					marginTop,
					marginBottom,
					width: finalWidth,
				},
			},
			ShimmerView(
				{ animating: true },
				Line(finalWidth),
			),
		);
	};

	const Line = (width) => View({
		style: {
			width,
			height: 6,
			borderRadius: 3,
			backgroundColor: AppTheme.colors.base6,
		},
	});

	module.exports = { Filler };
});
