/**
 * @module calendar/statemanager/redux/slices/events
 */
jn.define('calendar/statemanager/redux/slices/events', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');

	const { sliceName, eventsAdapter, initialState } = require('calendar/statemanager/redux/slices/events/meta');
	const { Model } = require('calendar/statemanager/redux/slices/events/model');

	const {
		selectByIdAndDate,
		selectByIds,
		selectByDate,
		selectByMonth,
		selectByParentId,
		selectById,
		selectByRecurrenceId,
	} = require('calendar/statemanager/redux/slices/events/selector');

	const {
		saveEvent,
		saveThisEvent,
		setMeetingStatus,
		deleteEvent,
		deleteThisEvent,
		deleteNextEvent,
	} = require('calendar/statemanager/redux/slices/events/thunk');

	const {
		savePending,
		saveFulfilled,
		saveRejected,
		baseFulfilled,
		baseRejected,
		setMeetingStatusPending,
		deleteEventPending,
		deleteThisEventPending,
		deleteNextEventPending,
	} = require('calendar/statemanager/redux/slices/events/extra-reducer');

	const calendarSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			eventsAdded: (state, { payload }) => {
				const events = payload.map((event) => Model.fromEventData(event, state.entities[event.ID]));

				eventsAdapter.upsertMany(state, events);
			},
			eventDeleted: (state, { payload }) => {
				const { eventId } = payload;

				eventsAdapter.removeOne(state, eventId);
			},
			eventMeetingStatusChanged: (state, { payload }) => {
				const { eventId, userMeetingStatus, userId } = payload;

				const event = state.entities[eventId];
				const meetingStatus = userId === Number(env.userId) ? userMeetingStatus : event.meetingStatus;
				const attendees = event.attendees.map(({ id, status }) => ({
					id,
					status: id === Number(userId) ? userMeetingStatus : status,
				}));

				eventsAdapter.upsertOne(state, { ...event, meetingStatus, attendees });
			},
			eventFilesChanged: (state, { payload }) => {
				const { eventId, files } = payload;

				const event = state.entities[eventId];
				const hasUploadedFiles = false;

				eventsAdapter.upsertOne(state, { ...event, files, hasUploadedFiles });
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(saveEvent.pending, savePending)
				.addCase(saveEvent.fulfilled, saveFulfilled)
				.addCase(saveEvent.rejected, saveRejected)
				.addCase(saveThisEvent.fulfilled, baseFulfilled)
				.addCase(saveThisEvent.rejected, baseRejected)
				.addCase(setMeetingStatus.pending, setMeetingStatusPending)
				.addCase(deleteEvent.pending, deleteEventPending)
				.addCase(deleteEvent.fulfilled, baseFulfilled)
				.addCase(deleteEvent.rejected, baseRejected)
				.addCase(deleteThisEvent.pending, deleteThisEventPending)
				.addCase(deleteThisEvent.fulfilled, baseFulfilled)
				.addCase(deleteThisEvent.rejected, baseRejected)
				.addCase(deleteNextEvent.pending, deleteNextEventPending)
				.addCase(deleteNextEvent.fulfilled, baseFulfilled)
				.addCase(deleteNextEvent.rejected, baseRejected)
			;
		},
	});

	const { reducer, actions } = calendarSlice;

	const {
		eventsAdded,
		eventDeleted,
		eventMeetingStatusChanged,
		eventFilesChanged,
	} = actions;

	ReducerRegistry.register(sliceName, reducer);

	module.exports = {
		// reducers
		eventsAdded,
		eventDeleted,
		eventMeetingStatusChanged,
		eventFilesChanged,

		// selectors
		selectByIdAndDate,
		selectByIds,
		selectByDate,
		selectByMonth,
		selectByParentId,
		selectById,
		selectByRecurrenceId,

		// actions
		saveEvent,
		saveThisEvent,
		deleteEvent,
		deleteThisEvent,
		deleteNextEvent,
		setMeetingStatus,
	};
});
