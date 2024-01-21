/**
 * @module tasks/statemanager/redux/slices/tasks/thunk
 */
jn.define('tasks/statemanager/redux/slices/tasks/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { isOnline } = require('device/connection');

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});
	const condition = () => isOnline();

	const updateDeadline = createAsyncThunk(
		'tasks:tasks/updateDeadline',
		({ taskId, deadline }) => runActionPromise({
			action: 'tasksmobile.Task.updateDeadline',
			options: {
				taskId,
				deadline: (deadline ? (new Date(deadline)).toISOString() : null),
			},
		}),
		{ condition },
	);

	const delegate = createAsyncThunk(
		'tasks:tasks/delegate',
		({ taskId, userId }) => runActionPromise({
			action: 'tasks.task.delegate',
			options: { taskId, userId },
		}),
		{ condition },
	);

	const unfollow = createAsyncThunk(
		'tasks:tasks/unfollow',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.unfollow',
			options: { taskId },
		}),
		{ condition },
	);

	const startTimer = createAsyncThunk(
		'tasks:tasks/startTimer',
		({ taskId }) => runActionPromise({
			action: 'tasks.task.startTimer',
			options: { taskId },
		}),
		{ condition },
	);

	const pauseTimer = createAsyncThunk(
		'tasks:tasks/pauseTimer',
		({ taskId }) => runActionPromise({
			action: 'tasks.task.pauseTimer',
			options: { taskId },
		}),
		{ condition },
	);

	const start = createAsyncThunk(
		'tasks:tasks/start',
		({ taskId }) => runActionPromise({
			action: 'tasks.task.start',
			options: { taskId },
		}),
		{ condition },
	);

	const pause = createAsyncThunk(
		'tasks:tasks/pause',
		({ taskId }) => runActionPromise({
			action: 'tasks.task.pause',
			options: { taskId },
		}),
		{ condition },
	);

	const complete = createAsyncThunk(
		'tasks:tasks/complete',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.complete',
			options: { taskId },
		}),
		{ condition },
	);

	const renew = createAsyncThunk(
		'tasks:tasks/renew',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.renew',
			options: { taskId },
		}),
		{ condition },
	);

	const approve = createAsyncThunk(
		'tasks:tasks/approve',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.approve',
			options: { taskId },
		}),
		{ condition },
	);

	const disapprove = createAsyncThunk(
		'tasks:tasks/disapprove',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.disapprove',
			options: { taskId },
		}),
		{ condition },
	);

	const ping = createAsyncThunk(
		'tasks:tasks/ping',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.ping',
			options: { taskId },
		}),
		{ condition },
	);

	const pin = createAsyncThunk(
		'tasks:tasks/pin',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.pin',
			options: { taskId },
		}),
		{ condition },
	);

	const unpin = createAsyncThunk(
		'tasks:tasks/unpin',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.unpin',
			options: { taskId },
		}),
		{ condition },
	);

	const mute = createAsyncThunk(
		'tasks:tasks/mute',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.mute',
			options: { taskId },
		}),
		{ condition },
	);

	const unmute = createAsyncThunk(
		'tasks:tasks/unmute',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.unmute',
			options: { taskId },
		}),
		{ condition },
	);

	const addToFavorites = createAsyncThunk(
		'tasks:tasks/addToFavorites',
		({ taskId }) => runActionPromise({
			action: 'tasks.task.favorite.add',
			options: { taskId },
		}),
		{ condition },
	);

	const removeFromFavorites = createAsyncThunk(
		'tasks:tasks/removeFromFavorites',
		({ taskId }) => runActionPromise({
			action: 'tasks.task.favorite.remove',
			options: { taskId },
		}),
		{ condition },
	);

	const remove = createAsyncThunk(
		'tasks:tasks/remove',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.remove',
			options: { taskId },
		}),
		{ condition },
	);

	const read = createAsyncThunk(
		'tasks:tasks/read',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.read',
			options: { taskId },
		}),
		{ condition },
	);

	const readAllForRole = createAsyncThunk(
		'tasks:tasks/readAllForRole',
		({ fields }) => runActionPromise({
			action: 'tasks.viewedGroup.user.markAsRead',
			options: { fields },
		}),
		{ condition },
	);

	const readAllForProject = createAsyncThunk(
		'tasks:tasks/readAllForProject',
		({ fields }) => runActionPromise({
			action: 'tasks.viewedGroup.project.markAsRead',
			options: { fields },
		}),
		{ condition },
	);

	module.exports = {
		updateDeadline,
		delegate,
		unfollow,
		startTimer,
		pauseTimer,
		start,
		pause,
		complete,
		renew,
		approve,
		disapprove,
		ping,
		pin,
		unpin,
		mute,
		unmute,
		addToFavorites,
		removeFromFavorites,
		remove,
		read,
		readAllForRole,
		readAllForProject,
	};
});
