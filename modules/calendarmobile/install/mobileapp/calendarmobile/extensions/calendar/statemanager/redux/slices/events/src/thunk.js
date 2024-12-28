/**
 * @module calendar/statemanager/redux/slices/events/thunk
 */
jn.define('calendar/statemanager/redux/slices/events/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');

	const saveEvent = createAsyncThunk(
		'calendar:events/save',
		async ({ data }, { rejectWithValue }) => {
			try
			{
				return await BX.ajax.runAction('calendar.api.calendarentryajax.editEntry', {
					data,
				});
			}
			catch (errorResponse)
			{
				return rejectWithValue(errorResponse);
			}
		},
	);

	const saveThisEvent = createAsyncThunk(
		'calendar:events/saveThis',
		async ({ data }, { rejectWithValue }) => {
			try
			{
				return await BX.ajax.runAction('calendar.api.calendarentryajax.editEntry', {
					data,
				});
			}
			catch (errorResponse)
			{
				return rejectWithValue(errorResponse);
			}
		},
	);

	const deleteEvent = createAsyncThunk(
		'calendar:events/deleteEvent',
		async ({ data }, { rejectWithValue }) => {
			try
			{
				return BX.ajax.runAction('calendar.api.calendarajax.deleteCalendarEntry', {
					data,
				});
			}
			catch (errorResponse)
			{
				return rejectWithValue(errorResponse);
			}
		},
	);

	const deleteThisEvent = createAsyncThunk(
		'calendar:events/deleteThisEvent',
		async ({ data }, { rejectWithValue }) => {
			try
			{
				return BX.ajax.runAction('calendar.api.calendarajax.excludeRecursionDate', {
					data,
				});
			}
			catch (errorResponse)
			{
				return rejectWithValue(errorResponse);
			}
		},
	);

	const deleteNextEvent = createAsyncThunk(
		'calendar:events/deleteNextEvent',
		async ({ data }, { rejectWithValue }) => {
			try
			{
				return BX.ajax.runAction('calendar.api.calendarajax.changeRecurciveEntryUntil', {
					data,
				});
			}
			catch (errorResponse)
			{
				return rejectWithValue(errorResponse);
			}
		},
	);

	const setMeetingStatus = createAsyncThunk(
		'calendar:events/setMeetingStatus',
		({ data }) => BX.ajax.runAction('calendarmobile.event.setMeetingStatus', {
			data,
		}),
	);

	module.exports = {
		saveEvent,
		saveThisEvent,
		deleteEvent,
		deleteThisEvent,
		deleteNextEvent,
		setMeetingStatus,
	};
});
