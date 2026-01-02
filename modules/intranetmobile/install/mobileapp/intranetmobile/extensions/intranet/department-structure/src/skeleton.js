/**
 * @module intranet/department-structure/src/skeleton
 */
jn.define('intranet/department-structure/src/skeleton', (require, exports, module) => {
	const { Line, Circle } = require('utils/skeleton');
	const { Card } = require('ui-system/layout/card');
	const { Color, Indent, Corner } = require('tokens');

	const LEFT_PADDING_STEP = 20;

	const renderSkeleton = () => {
		return View(
			{},
			renderCompanySkeleton(),
			renderSkeletonCard(0),
			renderSkeletonCard(1),
			renderSkeletonCard(2, true),
		);
	};

	const renderCompanySkeleton = () => {
		return View(
			{
				style: {
					marginBottom: Indent.XL.toNumber(),
				},
			},
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Circle(32),
				View(
					{
						style: {
							flex: 1,
							justifyContent: 'center',
							paddingHorizontal: Indent.M.toNumber(),
						},
					},
					Line(160, 14, 0, 0, 7),
				),
			),
		);
	};

	const renderSkeletonCard = (depth = 0, accent = false) => {
		const shouldRenderLine = depth > 0;

		return View(
			{},
			shouldRenderLine && renderShortLine(depth),
			View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				shouldRenderLine && renderLine(depth),
				Card(
					{
						testId: 'department-card-skeleton',
						border: true,
						style: {
							flex: 1,
							borderColor: accent
								? Color.accentSoftBorderBlue.toHex()
								: Color.bgSeparatorPrimary.toHex(),
						},
					},
					Line(200, 16, 0, Indent.S.toNumber(), 9),
					Line(100, 12, 0, Indent.XL.toNumber(), 9),
					View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'center',
								alignItems: 'center',
								marginTop: Indent.S.toNumber(),
							},
						},
						Circle(34),
						View(
							{
								style: {
									flex: 1,
									paddingHorizontal: Indent.S.toNumber(),
								},
							},
							Line(100, 16, 0, Indent.M.toNumber(), 7),
							Line(200, 12, 0, 0, 5),
						),
					),
				),
			),
		);
	};

	const renderLine = (depth = 1) => {
		const marginLeft = (depth - 0.5) * LEFT_PADDING_STEP;
		const borderColor = Color.bgSeparatorSecondary.toHex();

		return View({
			style: {
				height: '50%',
				borderLeftWidth: 1,
				borderBottomWidth: 1,
				borderLeftColor: borderColor,
				borderBottomColor: borderColor,
				borderBottomLeftRadius: Corner.S.toNumber(),
				width: LEFT_PADDING_STEP / 2,
				marginLeft,
			},
		});
	};

	const renderShortLine = (depth = 1) => {
		const marginLeft = (depth - 0.5) * LEFT_PADDING_STEP;
		const borderColor = Color.bgSeparatorSecondary.toHex();

		return View({
			style: {
				height: Indent.XL.toNumber(),
				borderLeftWidth: 1,
				borderLeftColor: borderColor,
				width: LEFT_PADDING_STEP / 2,
				marginLeft,
			},
		});
	};

	module.exports = {
		renderSkeleton,
	};
});
