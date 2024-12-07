/**
 * @module crm/timeline/scheduler/providers/sharing/skeleton
 */
jn.define('crm/timeline/scheduler/providers/sharing/skeleton', (require, exports, module) => {
	const { Line, Circle } = require('utils/skeleton');

	const PercentLine = (width, height, marginTop = 0, marginBottom = 0, borderRadius = 27) => View(
		{
			style: { width },
		},
		Line(null, height, marginTop, marginBottom, borderRadius),
	);

	const Skeleton = () => View(
		{
			style: {
				paddingHorizontal: 20,
				paddingTop: 34,
			},
		},
		Switcher(),
		Settings(),
		Buttons(),
	);

	const Switcher = () => View(
		{},
		View(
			{
				style: style.rowWithButton,
			},
			PercentLine('50%', 10),
			Circle(20),
		),
		PercentLine('90%', 50, 10, 20, 6),
		PercentLine('60%', 9),
	);

	const Settings = () => View(
		{},
		Rule(),
		Members(),
	);

	const Rule = () => View(
		{
			style: {
				marginVertical: 30,
			},
		},
		View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			View(
				{
					style: {
						width: 40,
					},
				},
				Circle(20),
			),
			PercentLine('60%', 9),
		),
		View(
			{
				style: {
					paddingLeft: 40,
				},
			},
			PercentLine('80%', 9, 5),
		),
	);

	const Members = () => View(
		{},
		View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			View(
				{
					style: {
						width: 40,
					},
				},
				Circle(20),
			),
			PercentLine('80%', 9),
		),
		View(
			{
				style: {
					marginTop: 10,
					paddingLeft: 40,
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			View(
				{
					style: {
						marginRight: 10,
					},
				},
				Circle(36),
			),
			Circle(36),
		),
	);

	const Buttons = () => View(
		{
			style: {
				alignItems: 'center',
			},
		},
		PercentLine('80%', 48, 30, 15, 6),
		PercentLine('50%', 10),
	);

	const style = {
		rowWithButton: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			alignItems: 'center',
		},
		row: {
			flexDirection: 'row',
			alignItems: 'center',
		},
	};

	module.exports = { Skeleton };
});
