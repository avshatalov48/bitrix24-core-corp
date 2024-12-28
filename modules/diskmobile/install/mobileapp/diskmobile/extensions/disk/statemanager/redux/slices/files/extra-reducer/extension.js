/**
 * @module disk/statemanager/redux/slices/files/extra-reducer
 */
jn.define('disk/statemanager/redux/slices/files/extra-reducer', (require, exports, module) => {
	const { showErrorToast } = require('toast');
	const { fileListAdapter } = require('disk/statemanager/redux/slices/files/meta');
	const { FileModel } = require('disk/statemanager/redux/slices/files/model/file');
	const { Uuid } = require('utils/uuid');

	const renamePending = (state, action) => {
		const { objectId, newName } = action.meta.arg;
		const object = state.entities[objectId];
		action.meta.arg.oldName = object.name;

		if (object)
		{
			fileListAdapter.upsertOne(state, {
				...object,
				name: newName,
			});
		}
	};

	const renameFulfilled = (state, action) => {
		const { objectId, oldName } = action.meta.arg;
		const { errors } = action.payload;
		if (errors && errors.length > 0)
		{
			const object = state.entities[objectId];
			if (object)
			{
				fileListAdapter.upsertOne(state, {
					...object,
					name: oldName,
				});
			}
			showErrorToast(errors[0]);
		}
	};

	const removePending = (state, action) => {
		const objectId = action.meta.arg.objectId;
		const object = state.entities[objectId];

		fileListAdapter.upsertOne(state, {
			...object,
			isRemoved: true,
		});
	};

	const removeFulfilled = (state, action) => {
		const objectId = action.meta.arg.objectId;
		const object = state.entities[objectId];

		const { errors } = action.payload;
		if (errors && errors.length > 0)
		{
			fileListAdapter.upsertOne(state, {
				...object,
				isRemoved: false,
			});
			showErrorToast(errors[0]);
		}
		else
		{
			fileListAdapter.removeOne(state, objectId);
		}
	};

	const movePending = (state, action) => {
		const { objectId, targetId } = action.meta.arg;

		const object = state.entities[objectId];
		action.meta.arg.oldParentId = object.parentId;

		fileListAdapter.upsertOne(state, {
			...object,
			parentId: targetId,
		});
	};

	const moveFulfilled = (state, action) => {
		const { objectId, onFulfilledSuccess } = action.meta.arg;
		const object = state.entities[objectId];

		const { errors } = action.payload;
		if (errors && errors.length > 0)
		{
			fileListAdapter.upsertOne(state, {
				...object,
				parentId: action.meta.arg.oldParentId,
			});
			showErrorToast(errors[0]);
		}
		else
		{
			onFulfilledSuccess?.();
		}
	};

	const copyPending = (state, action) => {
		const { objectId, targetId } = action.meta.arg;

		const object = state.entities[objectId];

		const newObjectId = Uuid.getV4();
		action.meta.arg.newObjectId = newObjectId;

		fileListAdapter.upsertOne(state, {
			...object,
			id: newObjectId,
			parentId: targetId,
		});
	};

	const copyFulfilled = (state, action) => {
		const { newObjectId, onFulfilledSuccess } = action.meta.arg;

		const { errors } = action.payload;
		if (errors && errors.length > 0)
		{
			fileListAdapter.removeOne(state, newObjectId);
			showErrorToast(errors[0]);
		}
		else
		{
			const newObject = FileModel.prepareReduxFileFromServerFile(action.payload.data.object);
			fileListAdapter.updateOne(state, { id: newObjectId, changes: newObject });

			onFulfilledSuccess?.();
		}
	};

	module.exports = {
		renamePending,
		renameFulfilled,
		removePending,
		removeFulfilled,
		movePending,
		moveFulfilled,
		copyPending,
		copyFulfilled,
	};
});
