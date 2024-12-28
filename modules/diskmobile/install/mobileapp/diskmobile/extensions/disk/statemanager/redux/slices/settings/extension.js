/**
 * @module disk/statemanager/redux/slices/settings
 */
jn.define('disk/statemanager/redux/slices/settings', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { Cache } = require('disk/cache');

	const reducerName = 'disk:settings';
	const initialState = {
		showFileExtension: Cache.get('show-file-extension', false),
	};

	const settingsSlice = createSlice({
		name: reducerName,
		initialState,
		reducers: {
			setShowFileExtension: (state, { payload }) => {
				Cache.set('show-file-extension', payload);
				state.showFileExtension = payload;
			},
		},
	});

	const {
		setShowFileExtension,
	} = settingsSlice.actions;

	const selectShowFileExtension = (state) => state[reducerName].showFileExtension;

	ReducerRegistry.register(reducerName, settingsSlice.reducer);

	module.exports = {
		setShowFileExtension,
		selectShowFileExtension,
	};
});
