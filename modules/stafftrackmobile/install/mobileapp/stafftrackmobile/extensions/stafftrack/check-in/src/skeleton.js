/**
 * @module stafftrack/check-in/skeleton
 */
jn.define('stafftrack/check-in/skeleton', (require, exports, module) => {
	const { Indent, Corner } = require('tokens');
	const { Line, Circle } = require('utils/skeleton');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');

	const PercentLine = (width, height, borderRadius = 27) => View(
		{
			style: { width },
		},
		Line(null, height, 0, 0, borderRadius),
	);

	const Skeleton = () => Box(
		{
			safeArea: {
				bottom: true,
			},
			style: {
				flex: 1,
			},
		},
		Header(),
		Content(),
		Buttons(),
	);

	const Header = () => Area(
		{
			isFirst: true,
			style: {
				flexDirection: 'row',
				alignItems: 'center',
				justifyContent: 'space-between',
			},
		},
		PercentLine('50%', 9),
		HeaderRight(),
	);

	const HeaderRight = () => View(
		{
			style: {
				flex: 1,
				flexDirection: 'row',
				alignItems: 'center',
				justifyContent: 'flex-end',
			},
		},
		PercentLine('60%', 20),
		View(
			{
				style: {
					marginLeft: Indent.L.toNumber(),
				},
			},
			PercentLine(23, 10),
		),
	);

	const Content = () => Area(
		{
			isFirst: true,
			style: {
				flex: 1,
			},
		},
		Message(),
		Map(),
	);

	const Message = () => View(
		{
			style: {
				flexDirection: 'column',
				paddingVertical: Indent.L.toNumber(),
			},
		},
		Switcher(),
		MessageBody(),
	);

	const Switcher = () => View(
		{
			style: {
				alignItems: 'center',
				flexDirection: 'row',
			},
		},
		Line(30, 15, 0, 0, 27),
		View(
			{
				style: {
					flex: 1,
					marginLeft: Indent.L.toNumber(),
				},
			},
			PercentLine('90%', 9),
		),
	);

	const MessageBody = () => View(
		{
			style: {
				flexDirection: 'row',
				paddingTop: Indent.XL2.toNumber(),
			},
		},
		Circle(36),
		View(
			{
				style: {
					flex: 1,
					paddingHorizontal: Indent.M.toNumber(),
				},
			},
			PercentLine('100%', 37, Corner.M.toNumber()),
		),
		Line(36, 37, 0, 0, Corner.M.toNumber()),
	);

	const Map = () => View(
		{
			style: {
				flexDirection: 'column',
				paddingVertical: Indent.XL3.toNumber(),
			},
		},
		MapImage(),
		Address(),
		View(
			{
				style: {
					paddingTop: Indent.XL2.toNumber(),
				},
			},
			Switcher(),
		),
	);

	const MapImage = () => View(
		{
			style: {
				flexDirection: 'row',
				paddingTop: Indent.L.toNumber(),
			},
		},
		PercentLine('100%', 100, Corner.S.toNumber()),
	);

	const Address = () => View(
		{
			style: {
				flexDirection: 'row',
				paddingTop: Indent.L.toNumber(),
			},
		},
		PercentLine('90%', 9),
	);

	const Buttons = () => Area(
		{
			isFirst: true,
		},
		View(
			{
				style: {
					flexDirection: 'row',
				},
			},
			PercentLine('100%', 50, Corner.S.toNumber()),
		),
		View(
			{
				style: {
					paddingTop: Indent.L.toNumber(),
					height: 42,
					alignItems: 'center',
					justifyContent: 'center',
				},
			},
			Line(150, 9, 0, 0, 27),
		),
	);

	module.exports = { Skeleton };
});
