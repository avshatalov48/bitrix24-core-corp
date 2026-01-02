/**
 * @module bizproc/skeleton/workflow-details
 */
jn.define('bizproc/skeleton/workflow-details', (require, exports, module) => {
	const { Color, Corner, Indent } = require('tokens');
	const { Line } = require('utils/skeleton');

	const workflowDetailsSkeleton = () => {
		const corner = Corner.L.toNumber();

		return View(
			{
				style: { backgroundColor: Color.base7.toHex() },
				safeArea: { bottom: true },
			},
			View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						flexDirection: 'column',
						paddingTop: Indent.XL4.toNumber(),
						paddingBottom: Indent.XL3.toNumber(),
						paddingLeft: Indent.XL3.toNumber(),
						minHeight: device.screen.height * 0.85 - 250,
					},
				},
				View(
					{ style: { flexGrow: 1 } },
					View(
						{},
						Line(159, 10, 0, Indent.XL.toNumber(), corner),
						Line(196, 10, 0, Indent.XL.toNumber(), corner),
						Line(307, 4, Indent.L.toNumber(), Indent.XL.toNumber(), corner),
						Line(278, 4, 0, Indent.XL.toNumber(), corner),
						Line(307, 4, 0, Indent.XL.toNumber(), corner),
						Line(175, 4, 0, Indent.XL.toNumber(), corner),
					),
					View(
						{
							style: {
								padding: Indent.XL.toNumber(),
								marginTop: Indent.XL.toNumber(),
								marginRight: Indent.XL3.toNumber(),
								borderRadius: Corner.L.toNumber(),
								borderWidth: 1,
								borderColor: Color.base7.toHex(),
							},
						},
						Line(219, 4, Indent.XS.toNumber(), Indent.XL3.toNumber(), corner),
						View({ style: { height: 1, backgroundColor: Color.base7.toHex() } }),
						Line(74, 4, Indent.XL2.toNumber(), Indent.XL2.toNumber(), corner),
						Line(149, 8, 0, Indent.XL2.toNumber(), corner),
						View({ style: { height: 1, backgroundColor: Color.base7.toHex() } }),
						Line(44, 4, Indent.XL2.toNumber(), Indent.XL2.toNumber(), corner),
						Line(216, 8, 0, Indent.XS2.toNumber(), corner),
					),
				),
				View(
					{
						style: { flexDirection: 'row', alignItems: 'center', marginTop: Indent.XL3.toNumber() },
					},
					button(),
					button(),
					View({
						style: {
							height: 19,
							width: 1,
							backgroundColor: Color.base7.toHex(),
							marginRight: Indent.XL.toNumber(),
						},
					}),
					button(),
				),
			),
			View(
				{
					style: {
						position: 'absolute',
						backgroundColor: Color.bgContentPrimary.toHex(),
						borderRadius: Corner.M.toNumber(),
						width: 230,
						height: 36,
						bottom: 36 + device.screen.safeArea.bottom,
						alignSelf: 'center',
					},
				},
			),
		);
	};

	const button = () => {
		return View(
			{
				style: {
					marginRight: Indent.XL.toNumber(),
					borderRadius: Corner.M.toNumber(),
					borderWidth: 1,
					borderColor: Color.base7.toHex(),
					justifyContent: 'center',
					alignItems: 'center',
					width: 122,
					height: 36,
				},
			},
			Line(60, 8, 0, 0, Corner.XL.toNumber()),
		);
	};

	module.exports = {
		workflowDetailsSkeleton,
	};
});
