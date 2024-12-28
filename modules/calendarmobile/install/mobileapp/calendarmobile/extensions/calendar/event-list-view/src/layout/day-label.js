/**
 * @module calendar/event-list-view/layout/day-label
 */
jn.define('calendar/event-list-view/layout/day-label', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Text3 } = require('ui-system/typography/text');
	const { Area } = require('ui-system/layout/area');

	const { DateHelper } = require('calendar/date-helper');
	const { State } = require('calendar/event-list-view/state');

	const DayLabel = (props) => Area(
		{
			isFirst: false,
			excludePaddingSide: {
				horizontal: true,
			},
			testId: `day_label_${props.date}`,
		},
		Text3({
			text: DateHelper.getDateHeaderString(props.date),
			color: State.isSearchMode ? Color.base1 : Color.base4,
		}),
	);

	module.exports = { DayLabel };
});
