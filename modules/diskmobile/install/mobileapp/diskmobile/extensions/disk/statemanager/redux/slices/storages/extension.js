/**
 * @module disk/statemanager/redux/slices/storages
 */
jn.define('disk/statemanager/redux/slices/storages', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createEntityAdapter, createSlice } = require('statemanager/redux/toolkit');

	const reducerName = 'disk:storages';
	const storagesAdapter = createEntityAdapter();

	const {
		selectById,
		selectAll,
	} = storagesAdapter.getSelectors((state) => state[reducerName]);

	const prepareStorage = ({ id, name, rootObjectId, type, entityId }) => ({
		id: Number(id),
		name,
		rootObjectId,
		type,
		entityId,
	});

	const storageSlice = createSlice({
		name: reducerName,
		initialState: storagesAdapter.getInitialState(),
		reducers: {
			storagesUpserted: {
				reducer: storagesAdapter.upsertMany,
				prepare: (storages) => ({
					payload: storages.map((storage) => prepareStorage(storage)),
				}),
			},
			storagesAdded: {
				reducer: storagesAdapter.addMany,
				prepare: (storages) => ({
					payload: storages.map((storage) => prepareStorage(storage)),
				}),
			},
		},
	});

	const {
		storagesUpserted,
		storagesAdded,
	} = storageSlice.actions;

	ReducerRegistry.register(reducerName, storageSlice.reducer);

	module.exports = {
		storagesUpserted,
		storagesAdded,
		selectById,
		selectAll,
	};
});
