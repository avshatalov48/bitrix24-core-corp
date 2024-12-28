/**
 * @module calendar/event-edit-form/layout/slot-list-skeleton
 */
jn.define('calendar/event-edit-form/layout/slot-list-skeleton', (require, exports, module) => {
	const { Indent, Component } = require('tokens');
	const { Line } = require('utils/skeleton');

	const slotHeight = 58;

	const SlotSkeleton = () => Line(
		null,
		slotHeight - Indent.XL.toNumber(),
		0,
		Indent.XL.toNumber(),
		Component.cardCorner.toNumber(),
	);

	const SlotListSkeleton = () => View(
		{
			style: {
				flex: 1,
			},
		},
		...Array.from({ length: 8 }).map(() => SlotSkeleton()),
	);

	module.exports = { SlotListSkeleton };
});
