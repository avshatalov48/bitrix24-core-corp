/**
 * @module calendar/statemanager/redux/slices/events/extra-reducer
 */
jn.define('calendar/statemanager/redux/slices/events/extra-reducer', (require, exports, module) => {
	const { Type } = require('type');
	const { isEqual } = require('utils/object');

	const { eventsAdapter } = require('calendar/statemanager/redux/slices/events/meta');
	const { Model } = require('calendar/statemanager/redux/slices/events/model');

	const savePending = (state, action) => {
		const { reduxFields } = action.meta.arg;
		const hasUploadedFiles = false;

		return eventsAdapter.upsertOne(state, { ...Model.getDefault(), ...reduxFields, hasUploadedFiles });
	};

	const saveFulfilled = (state, action) => {
		const { eventId, reduxFields, resolve } = action.meta.arg;
		const response = action.payload;

		const existingEvent = state.entities[eventId];
		const savedEvent = response.data.eventList.shift();
		const savedEventIsParentEvent = (typeof eventId === 'number') && (Number(savedEvent.ID) !== eventId);

		const preparedEvent = Model.fromEventData(savedEvent, existingEvent);
		if (reduxFields?.hasUploadedFiles)
		{
			preparedEvent.hasUploadedFiles = true;
		}

		if (!savedEventIsParentEvent && !isEqual(existingEvent, preparedEvent))
		{
			eventsAdapter.updateOne(state, { id: eventId, changes: preparedEvent });
		}

		resolve(response);
	};

	const saveRejected = (state, action) => {
		const { eventId, reject } = action.meta.arg;
		const response = action.payload;

		eventsAdapter.removeOne(state, eventId);

		reject(response);
	};

	const baseFulfilled = (state, action) => {
		const { resolve } = action.meta.arg;
		const response = action.payload;

		resolve(response);
	};

	const baseRejected = (state, action) => {
		const { reject } = action.meta.arg;
		const response = action.payload;

		reject(response);
	};

	const setMeetingStatusPending = (state, action) => {
		const { data } = action.meta.arg;

		const event = state.entities[data.eventId];
		const meetingStatus = data.status;
		const attendees = event.attendees.map(({ id, status }) => ({
			id,
			status: id === Number(env.userId) ? data.status : status,
		}));

		return eventsAdapter.upsertOne(state, { ...event, meetingStatus, attendees });
	};

	const deleteEventPending = (state, action) => {
		const { data } = action.meta.arg;

		const eventId = data.eventId;
		eventsAdapter.removeOne(state, eventId);
	};

	const deleteThisEventPending = (state, action) => {
		const { eventId, excludeDate } = action.meta.arg;
		const event = state.entities[eventId];
		let excludedDates = event.excludedDates;

		if (Type.isArray(excludedDates))
		{
			excludedDates.push(excludeDate);
		}
		else
		{
			excludedDates = [excludeDate];
		}

		eventsAdapter.upsertOne(state, { ...event, excludedDates });
	};

	const deleteNextEventPending = (state, action) => {
		const { eventId, untilDate, untilDateTs } = action.meta.arg;
		const event = state.entities[eventId];

		if (Type.isPlainObject(event.recurrenceRule))
		{
			const recurrenceRule = {
				...event.recurrenceRule,
				UNTIL: untilDate,
				UNTIL_TS: untilDateTs,
			};
			eventsAdapter.upsertOne(state, { ...event, recurrenceRule });
		}
	};

	module.exports = {
		savePending,
		saveFulfilled,
		saveRejected,
		baseFulfilled,
		baseRejected,
		setMeetingStatusPending,
		deleteEventPending,
		deleteThisEventPending,
		deleteNextEventPending,
	};
});
