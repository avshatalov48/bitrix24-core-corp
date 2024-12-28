/**
 * @module tasks/statemanager/redux/slices/tasks/thunk
 */
jn.define('tasks/statemanager/redux/slices/tasks/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { isOnline } = require('device/connection');
	const { selectRelatedTasksById } = require('tasks/statemanager/redux/slices/tasks/selector');

	const { Views } = require('tasks/statemanager/redux/types');
	const { selectStages } = require('tasks/statemanager/redux/slices/kanban-settings/selector');
	const { selectById } = require('tasks/statemanager/redux/slices/stage-settings/selector');

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});
	const condition = () => isOnline();

	const create = createAsyncThunk(
		'tasks:tasks/create',
		async ({ taskId, serverFields, relatedTaskId = null }, store) => {
			const response = await runActionPromise({
				action: 'tasksmobile.Task.add',
				options: { fields: serverFields },
			});

			if (response.status === 'success' && relatedTaskId)
			{
				const currentRelatedTasks = selectRelatedTasksById(store.getState(), relatedTaskId);
				const currentRelatedTasksIds = currentRelatedTasks.map((task) => task.id);
				const newRelatedTasks = [...currentRelatedTasksIds, response.data.task.id];

				await store.dispatch(updateRelatedTasks({
					taskId: relatedTaskId,
					newRelatedTasks: [response.data.task.id],
					deletedRelatedTasks: [],
					relatedTasks: newRelatedTasks,
				}));
			}

			return response;
		},
		{ condition },
	);

	const update = createAsyncThunk(
		'tasks:tasks/update',
		({ taskId, serverFields, withStageData }) => runActionPromise({
			action: 'tasksmobile.Task.update',
			options: { taskId, fields: serverFields, withStageData },
		}),
		{ condition },
	);

	const updateDeadline = createAsyncThunk(
		'tasks:tasks/updateDeadline',
		({ taskId, deadline }) => runActionPromise({
			action: 'tasksmobile.Task.updateDeadline',
			options: {
				taskId,
				deadline: (deadline ? (new Date(deadline)).toISOString() : null),
			},
		}),
		{
			condition,
			getPendingMeta: (action, store) => {
				// prepare stages for deadline view to update task-stages in case of deadline change
				// this is needed to prepare data for pending reducers
				const stageIds = selectStages(store.getState(), Views.DEADLINE);
				const deadlineStages = stageIds.map((id) => selectById(store.getState(), id)).filter(Boolean);

				return {
					stages: deadlineStages,
				};
			},
		},
	);

	const delegate = createAsyncThunk(
		'tasks:tasks/delegate',
		({ taskId, userId }) => runActionPromise({
			action: 'tasksmobile.Task.delegate',
			options: { taskId, userId },
		}),
		{ condition },
	);

	const follow = createAsyncThunk(
		'tasks:tasks/follow',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.follow',
			options: { taskId },
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
			action: 'tasksmobile.Task.startTimer',
			options: { taskId },
		}),
		{ condition },
	);

	const pauseTimer = createAsyncThunk(
		'tasks:tasks/pauseTimer',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.pauseTimer',
			options: { taskId },
		}),
		{ condition },
	);

	const start = createAsyncThunk(
		'tasks:tasks/start',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.start',
			options: { taskId },
		}),
		{ condition },
	);

	const take = createAsyncThunk(
		'tasks:tasks/take',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.take',
			options: { taskId },
		}),
		{ condition },
	);

	const pause = createAsyncThunk(
		'tasks:tasks/pause',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.pause',
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

	const defer = createAsyncThunk(
		'tasks:tasks/defer',
		({ taskId }) => runActionPromise({
			action: 'tasksmobile.Task.defer',
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

	const updateSubTasks = createAsyncThunk(
		'tasks:tasks/updateSubTasks',
		({ parentId, newSubTasks, deletedSubTasks }) => runActionPromise({
			action: 'tasksmobile.Task.updateParentIdToTaskIds',
			options: { parentId, newSubTasks, deletedSubTasks },
		}),
		{ condition },
	);

	const updateRelatedTasks = createAsyncThunk(
		'tasks:tasks/updateRelatedTasks',
		({ taskId, newRelatedTasks, deletedRelatedTasks, relatedTasks }) => runActionPromise({
			action: 'tasksmobile.Task.updateRelatedTasks',
			options: { taskId, newRelatedTasks, deletedRelatedTasks, relatedTasks },
		}),
		{ condition },
	);

	module.exports = {
		create,
		update,
		updateDeadline,
		delegate,
		follow,
		unfollow,
		startTimer,
		pauseTimer,
		start,
		take,
		pause,
		complete,
		renew,
		defer,
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
		updateSubTasks,
		updateRelatedTasks,
	};
});
