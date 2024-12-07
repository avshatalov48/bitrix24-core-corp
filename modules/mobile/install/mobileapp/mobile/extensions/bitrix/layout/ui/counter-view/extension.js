/**
 * @module layout/ui/counter-view
 */
jn.define('layout/ui/counter-view', (require, exports, module) => {
	const { Color } = require('tokens');

	const CounterView = (value, props = {}) => {
		const {
			isDouble = false,
			firstColor = Color.accentMainAlert.toHex(),
			secondColor = Color.accentMainSuccess.toHex(),
		} = props;

		return View(
			{
				testId: 'counter-view',
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-end',
					minWidth: isDouble ? 28 : 20,
					marginRight: 1,
				},
			},
			isDouble && View(
				{
					testId: 'counter-view-second',
					style: {
						position: 'absolute',
						zIndex: -1,
						width: 20,
						height: 20,
					},
				},
				View(
					{
						testId: `counter-view-first-${secondColor}`,
						style: {
							position: 'absolute',
							height: 20,
							width: 24,
							zIndex: -2,
							backgroundColor: secondColor,
							borderRadius: 10,
							right: 0,
						},
					},
				),
				View(
					{
						style: {
							position: 'absolute',
							height: 20,
							width: 20,
							zIndex: -1,
							backgroundColor: Color.bgContentPrimary.toHex(),
							borderRadius: 10,
							right: 2,
						},
					},
				),
			),
			View(
				{
					testId: 'counter-view-first',
					style: {
						backgroundColor: firstColor,
						borderRadius: 10,
						paddingLeft: 7,
						paddingRight: 7,
						height: 20,
						minWidth: 20,
						justifyContent: 'center',
						alignItems: 'center',
						marginRight: isDouble ? 4 : 0,
						zIndex: 0,
					},
				},
				Text({
					testId: `counter-view-first-${firstColor}`,
					style: {
						color: Color.baseWhiteFixed.toHex(),
						fontSize: 12,
						textAlign: 'center',
					},
					text: String(value),
				}),
			),
		);
	};

	module.exports = { CounterView };
});
