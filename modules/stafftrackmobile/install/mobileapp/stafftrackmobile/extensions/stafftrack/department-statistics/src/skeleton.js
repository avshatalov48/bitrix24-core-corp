/**
 * @module stafftrack/department-statistics/skeleton
 */
jn.define('stafftrack/department-statistics/skeleton', (require, exports, module) => {
	const { Indent, Corner } = require('tokens');
	const { Line, Circle } = require('utils/skeleton');
	const { Area } = require('ui-system/layout/area');

	const PercentLine = (width, height, marginTop = 0, marginBottom = 0, borderRadius = 27) => View(
		{
			style: { width },
		},
		Line(null, height, marginTop, marginBottom, borderRadius),
	);

	const Skeleton = () => View(
		{
			style: {
				flex: 1,
			},
		},
		Header(),
		Content(),
	);

	const Header = () => Area(
		{
			isFirst: true,
			style: {
				alignItems: 'center',
				flexDirection: 'row',
			},
		},
		PercentLine(23, 10),
		View(
			{
				style: {
					flex: 1,
					marginLeft: Indent.XL.toNumber(),
				},
			},
			PercentLine('50%', 10),
		),
	);

	const Content = () => Area(
		{
			isFirst: true,
			style: {
				flex: 1,
			},
		},
		StatisticsRangeMode(),
		TodayStatistics(),
	);

	const StatisticsRangeMode = () => View(
		{
			style: {
				flexDirection: 'row',
			},
		},
		View(
			{
				style: {
					flex: 1,
					marginRight: Indent.XS.toNumber(),
				},
			},
			PercentLine(null, 31, 0, 0, Corner.XS.toNumber()),
		),
		View(
			{
				style: {
					flex: 1,
				},
			},
			PercentLine(null, 31, 0, 0, Corner.XS.toNumber()),
		),
	);

	const TodayStatistics = () => View(
		{
			style: {
				flex: 1,
			},
		},
		DaySum(),
		TableStatistics(7),
	);

	const DaySum = () => View(
		{
			style: {
				flexDirection: 'row',
				paddingVertical: Indent.XL4.toNumber(),
			},
		},
		Icon(),
		View(
			{
				style: {
					flex: 1,
				},
			},
			PercentLine('50%', 15, Indent.XS.toNumber(), Indent.XL.toNumber()),
			PercentLine('80%', 8, Indent.S.toNumber(), Indent.M.toNumber()),
			PercentLine('60%', 8, 0, Indent.XL3.toNumber()),
			PercentLine('100%', 6, Indent.XS.toNumber(), 0),
		),
	);

	const Icon = () => View(
		{
			style: {
				marginRight: Indent.XL4.toNumber(),
				padding: 20,
			},
		},
		View(
			{},
			Line(64, 64, 0, 0, 15),
		),
	);

	const TableStatistics = (amount) => View(
		{
			style: {
				flex: 1,
			},
		},
		View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
					paddingBottom: Indent.L.toNumber(),
				},
			},
			PercentLine('15%', 8, Indent.XS.toNumber(), Indent.XS.toNumber()),
			PercentLine('20%', 8, Indent.XS.toNumber(), Indent.XS.toNumber()),
		),
		...Array.from({ length: amount }).map(() => Item()),
	);

	const Item = () => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				alignItems: 'center',
				paddingVertical: Indent.S.toNumber(),
				marginBottom: Indent.XS.toNumber(),
			},
		},
		StatisticsUser(),
		PercentLine('10%', 8),
	);

	const StatisticsUser = () => View(
		{
			style: {
				flexDirection: 'row',
				alignItems: 'center',
				flex: 1,
			},
		},
		View(
			{
				style: {
					marginRight: Indent.M.toNumber(),
				},
			},
			Circle(24),
		),
		PercentLine('40%', 8),
	);

	module.exports = { Skeleton, TableStatistics };
});
