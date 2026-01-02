/**
 * @module statemanager/redux/slices/settings/tools
 */
jn.define('statemanager/redux/slices/settings/tools', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { selectById } = require('statemanager/redux/slices/settings/selector');
	const { fetchIsCollabToolEnabled } = require('statemanager/redux/slices/settings/thunk');

	const loadIsCollabToolEnabled = (isForceLoad = false) => {
		const isLoaded = selectById(store.getState(), 'collab_enabled') !== undefined;

		if (!isForceLoad && isLoaded)
		{
			return Promise.resolve();
		}

		return dispatch(fetchIsCollabToolEnabled());
	};

	module.exports = { loadIsCollabToolEnabled };
});
