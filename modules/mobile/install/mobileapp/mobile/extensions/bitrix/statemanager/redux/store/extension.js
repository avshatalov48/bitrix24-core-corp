/**
 * @module statemanager/redux/store
 */
jn.define('statemanager/redux/store', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { enableBatching } = require('statemanager/redux/batched-actions');
	const { batchedSubscribe } = require('statemanager/redux/batched-subscribe');
	const { createStateSyncMiddleware, initBroadcastChannel } = require('statemanager/redux/middleware/state-sync');
	const { configureStore, combineReducers } = require('statemanager/redux/toolkit');
	const { debounce } = require('utils/function');
	const { analyticsSenderMiddleware } = require('statemanager/redux/middleware/analytics-sender');

	const { DEV_SETTINGS } = require('settings/developer/meta');
	const { DeveloperSettingsCacheManager } = require('settings/developer/cache-manager');

	// register user reducer in global ReducerRegistry
	require('statemanager/redux/slices/users');

	const isBeta = Application.isBeta();

	const middlewares = [createStateSyncMiddleware(), analyticsSenderMiddleware];

	if (isBeta)
	{
		const isReduxLoggerEnabled = DeveloperSettingsCacheManager.getSettingValueById(DEV_SETTINGS.enableReduxLogger.id);
		if (isReduxLoggerEnabled)
		{
			const { logger } = require('statemanager/redux/middleware/logger');

			middlewares.push(logger);
		}
	}

	const batchCombineReducers = (reducers) => enableBatching(combineReducers(reducers));

	const reducer = batchCombineReducers(ReducerRegistry.getReducers());
	const debounceNotify = debounce((notify) => notify(), 100);

	const store = configureStore({
		reducer,
		middleware: (getDefaultMiddleware) => {
			return [
				...getDefaultMiddleware({
					immutableCheck: isBeta,
					serializableCheck: isBeta,
				}),
				...middlewares,
			];
		},
		enhancers: [batchedSubscribe(debounceNotify)],
		devTools: isBeta,
	});

	ReducerRegistry.setChangeListener((reducers) => {
		store.replaceReducer(batchCombineReducers(reducers));
	});

	initBroadcastChannel(store);

	module.exports = store;
});
