/**
 * @module statemanager/redux/store
 */
jn.define('statemanager/redux/store', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { usersReducer } = require('statemanager/redux/slices/users');
	const { enableBatching } = require('statemanager/redux/batched-actions');
	const { batchedSubscribe } = require('statemanager/redux/batched-subscribe');
	const { createStateSyncMiddleware, initBroadcastChannel } = require('statemanager/redux/state-sync');
	const { configureStore, combineReducers } = require('statemanager/redux/toolkit');
	const { debounce } = require('utils/function');

	const isBeta = Application.isBeta();

	const middlewares = [createStateSyncMiddleware()];

	if (isBeta)
	{
		const { logger } = require('statemanager/redux/logger');

		middlewares.push(logger);
	}

	const batchCombineReducers = (reducers) => enableBatching(combineReducers(reducers));

	const reducer = batchCombineReducers({
		[usersReducer.name]: usersReducer,
		...ReducerRegistry.getReducers(),
	});

	// 15 ms = 66 fps
	const debounceNotify = debounce((notify) => notify(), 15);

	const store = configureStore({
		reducer,
		// eslint-disable-next-line unicorn/prefer-spread
		middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(middlewares),
		enhancers: [batchedSubscribe(debounceNotify)],
		devTools: isBeta,
	});

	ReducerRegistry.setChangeListener((reducers) => {
		store.replaceReducer(batchCombineReducers(reducers));
	});

	initBroadcastChannel(store);

	module.exports = store;
});
