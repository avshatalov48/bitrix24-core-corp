/**
 * @module disk/statemanager/redux/slices/files/thunk
 */
jn.define('disk/statemanager/redux/slices/files/thunk', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('disk/statemanager/redux/slices/files/meta');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');

	const { RunActionExecutor } = require('rest/run-action-executor');
	const { isOnline } = require('device/connection');

	const condition = () => isOnline();

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});

	const rename = createAsyncThunk(
		`${sliceName}/rename`,
		({ objectId, newName }) => runActionPromise({
			action: 'disk.api.commonActions.rename',
			options: {
				objectId,
				newName,
				autoCorrect: true,
			},
		}),
		{ condition },
	);

	const remove = createAsyncThunk(
		`${sliceName}/remove`,
		({ objectId }) => runActionPromise({
			action: 'disk.api.commonActions.markDeleted',
			options: { objectId },
		}),
		{ condition },
	);

	const move = createAsyncThunk(
		`${sliceName}/move`,
		({ objectId, targetId }) => runActionPromise({
			action: 'disk.api.commonActions.move',
			options: { objectId, toFolderId: targetId },
		}),
		{ condition },
	);

	const copy = createAsyncThunk(
		`${sliceName}/copy`,
		({ objectId, targetId }) => runActionPromise({
			action: 'disk.api.commonActions.copyTo',
			options: { objectId, toFolderId: targetId },
		}),
		{ condition },
	);

	module.exports = {
		rename,
		remove,
		move,
		copy,
	};
});
