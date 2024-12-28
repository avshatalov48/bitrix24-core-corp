/**
 * @module calendar/event-list-view/list-skeleton
 */
jn.define('calendar/event-list-view/list-skeleton', (require, exports, module) => {
	const { Indent, Corner, Color } = require('tokens');
	const { Line } = require('utils/skeleton');
	const { Area } = require('ui-system/layout/area');

	const PercentLine = (width, height, borderRadius = 27) => View(
		{
			style: { width },
		},
		Line(null, height, 0, 0, borderRadius),
	);

	const ListSkeleton = () => View(
		{},
		ListHeader(),
		ListContent(13),
	);

	const ListHeader = () => Area(
		{
			isFirst: true,
			excludePaddingSide: {
				horizontal: true,
			},
		},
		Line(155, 11, Indent.L.toNumber(), 0, 27),
	);

	const ListContent = (amount) => View(
		{
			style: {
				flexDirection: 'column',
			},
		},
		...Array.from({ length: amount }).map(() => ListItem()),
	);

	const ListItem = () => View(
		{
			style: {
				flexDirection: 'row',
				paddingVertical: Indent.XL.toNumber(),
				borderTopWidth: 1,
				borderTopColor: Color.bgSeparatorSecondary.toHex(),
			},
		},
		Line(6, 36, 0, 0, Corner.S.toNumber()),
		ListItemInfo(),
		ListItemTime(),
	);

	const ListItemInfo = () => View(
		{
			style: {
				flex: 1,
				paddingHorizontal: Indent.XL.toNumber(),
				flexDirection: 'column',
			},
		},
		View(
			{
				style: {
					flex: 1,
				},
			},
			PercentLine('65%', 11),
		),
		View(
			{
				style: {
					flex: 1,
					paddingTop: Indent.XS2.toNumber(),
					paddingBottom: Indent.XS.toNumber(),
				},
			},
			PercentLine('35%', 9),
		),
	);

	const ListItemTime = () => View(
		{
			style: {
				justifyContent: 'center',
				flexDirection: 'column',
				marginVertical: Indent.XS.toNumber(),
			},
		},
		PercentLine(30, 9),
		View(
			{
				style: {
					marginTop: Indent.S.toNumber(),
				},
			},
			PercentLine(30, 9),
		),
	);

	module.exports = { ListSkeleton };
});
