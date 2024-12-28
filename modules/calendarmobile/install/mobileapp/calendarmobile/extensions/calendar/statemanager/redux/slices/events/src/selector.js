/**
 * @module calendar/statemanager/redux/slices/events/selector
 */
jn.define('calendar/statemanager/redux/slices/events/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');

	const { sliceName, eventsAdapter } = require('calendar/statemanager/redux/slices/events/meta');
	const { RecursionParser } = require('calendar/statemanager/redux/slices/events/recursion-parser');
	const { DateHelper } = require('calendar/date-helper');
	const { EventMeetingStatus, CalendarType } = require('calendar/enums');

	const {
		selectAll,
		selectById,
	} = eventsAdapter.getSelectors((state) => state[sliceName]);

	// eslint-disable-next-line sonarjs/cognitive-complexity
	const sorted = (events) => events.sort((a, b) => {
		if (a.isFullDay !== b.isFullDay)
		{
			return a.isFullDay ? -1 : 1;
		}

		if (a.isFullDay && b.isFullDay)
		{
			if (a.dateFromTs === b.dateFromTs)
			{
				return a.id - b.id;
			}

			return a.dateFromTs - b.dateFromTs;
		}

		if (a.dateFromTs === b.dateFromTs)
		{
			if (a.dateToTs === b.dateToTs)
			{
				return a.id - b.id;
			}

			return a.dateToTs - b.dateToTs;
		}

		return a.dateFromTs - b.dateFromTs;
	});

	const selectByIdAndDate = createDraftSafeSelector(
		(state) => state,
		(state, { eventId }) => selectById(state, eventId),
		(state, { dateFromTs }) => dateFromTs,
		(state, event, dateFromTs) => {
			if (!event)
			{
				return null;
			}

			if (!event.recurrenceRule)
			{
				return event;
			}

			const date = new Date(dateFromTs);
			const dayCode = DateHelper.getDayCode(date);
			const recurrence = RecursionParser
				.parseRecursion(event, getDateLimits(date))
				.find((recEvent) => {
					return DateHelper.getDayCode(new Date(recEvent.dateFromTs)) === dayCode;
				})
			;

			if (recurrence)
			{
				return recurrence;
			}

			// Trying to find recurrent instance in store
			return selectByRecurrenceId(state, { recurrenceId: event.parentId }).find((recRelatedEvent) => {
				return DateHelper.getDayCode(new Date(recRelatedEvent.dateFromTs)) === dayCode;
			}) ?? event;
		},
	);

	const selectAllParsed = createDraftSafeSelector(
		(state) => selectAll(state),
		(state, { fromLimit }) => fromLimit,
		(state, { toLimit }) => toLimit,
		(allEvents, fromLimit, toLimit) => allEvents.flatMap((event) => {
			return RecursionParser.parseRecursion(event, { fromLimit, toLimit });
		}),
	);

	const selectByIds = createDraftSafeSelector(
		(state, { parseRecursion }) => (parseRecursion ? selectAllParsed(state, getWholeLifeLimits()) : selectAll(state)),
		(state, { ids }) => ids,
		(allEvents, ids) => sorted(
			allEvents.filter((event) => ids.includes(event.id)),
		),
	);

	const selectByDate = createDraftSafeSelector(
		(state, { date }) => selectAllParsed(state, getMonthLimits(date)),
		(state, { date }) => getDateLimits(date).fromLimit,
		(state, { date }) => getDateLimits(date).toLimit,
		(state, { sectionIds }) => sectionIds,
		(state, { showDeclined }) => showDeclined,
		(allEvents, fromLimit, toLimit, sectionIds, showDeclined) => sorted(
			allEvents
				.filter((event) => event.dateToTs > fromLimit
					&& event.dateFromTs < toLimit
					&& (
						showDeclined
						|| event.meetingStatus !== EventMeetingStatus.DECLINED
					)
					&& sectionIds.includes(event.sectionId))
			,
		),
	);

	const selectByMonth = createDraftSafeSelector(
		(state, { date }) => selectAllParsed(state, getMonthLimits(date)),
		(state, { date }) => getMonthLimits(date).fromLimit,
		(state, { date }) => getMonthLimits(date).toLimit,
		(state, { sectionIds }) => sectionIds,
		(state, { showDeclined }) => showDeclined,
		(allEvents, fromLimit, toLimit, sectionIds, showDeclined) => allEvents
			.filter((event) => event.dateToTs > fromLimit
				&& event.dateFromTs < toLimit
				&& (
					showDeclined
					|| event.meetingStatus !== EventMeetingStatus.DECLINED
				)
				&& sectionIds.includes(event.sectionId))
		,
	);

	const selectByParentId = createDraftSafeSelector(
		(state) => selectAll(state),
		(state, { parentId }) => parentId,
		(state, { userId }) => userId,
		(allEvents, parentId, userId) => allEvents
			.find((event) => event.parentId === parentId
				&& event.ownerId === userId
				&& event.calType === CalendarType.USER)
		,
	);

	const selectByRecurrenceId = createDraftSafeSelector(
		(state) => selectAll(state),
		(state, { recurrenceId }) => recurrenceId,
		(allEvents, recurrenceId) => allEvents.filter((event) => event.recurrenceId === recurrenceId),
	);

	const getWholeLifeLimits = () => ({
		fromLimit: new Date(0).getTime(),
		toLimit: new Date(2038, 0).getTime(),
	});

	const getDateLimits = (date) => ({
		fromLimit: new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime(),
		toLimit: new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1).getTime(),
	});

	const getMonthLimits = (date) => ({
		fromLimit: new Date(date.getFullYear(), date.getMonth()).getTime(),
		toLimit: new Date(date.getFullYear(), date.getMonth() + 1).getTime(),
	});

	module.exports = {
		selectById,
		selectByIdAndDate,
		selectByIds,
		selectByDate,
		selectByMonth,
		selectByParentId,
		selectByRecurrenceId,
	};
});
