/**
 * @module tasks/statemanager/redux/slices/tasks-results/thunk
 */
jn.define('tasks/statemanager/redux/slices/tasks-results/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { isOnline } = require('device/connection');
	const { sliceName } = require('tasks/statemanager/redux/slices/tasks-results/meta');

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});
	const condition = () => isOnline();

	const fetch = createAsyncThunk(
		`${sliceName}:taskResult/fetch`,
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Result.list',
			options: { taskId },
		}),
		{ condition },
	);

	const create = createAsyncThunk(
		`${sliceName}:taskResult/create`,
		({ taskId, commentData }) => runActionPromise({
			action: 'tasksmobile.Result.add',
			options: { taskId, commentData },
		}),
		{ condition },
	);

	const update = createAsyncThunk(
		`${sliceName}:taskResult/update`,
		({ taskId, commentId, commentData }) => runActionPromise({
			action: 'tasksmobile.Result.update',
			options: { taskId, commentId, commentData },
		}),
		{ condition },
	);

	const remove = createAsyncThunk(
		`${sliceName}:taskResult/remove`,
		({ commentId }) => runActionPromise({
			action: 'tasksmobile.Result.delete',
			options: { commentId },
		}),
		{ condition },
	);

	module.exports = {
		fetch,
		create,
		update,
		remove,
	};
});
