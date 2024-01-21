/* MIT License

Copyright (c) 2016 Tim Shelburne

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE. */

/**
 * @module statemanager/redux/batched-actions
 */
jn.define('statemanager/redux/batched-actions', (require, exports, module) => {
	const BATCH = 'BATCHING_REDUCER.BATCH';

	function batchActions(actions, type = BATCH)
	{
		return { type, meta: { batch: true }, payload: actions };
	}

	function enableBatching(reduce)
	{
		return function batchingReducer(state, action) {
			if (action && action.meta && action.meta.batch)
			{
				return action.payload.reduce(batchingReducer, state);
			}

			return reduce(state, action);
		};
	}

	function batchDispatchMiddleware(store)
	{
		function dispatchChildActions(store, action)
		{
			if (action.meta && action.meta.batch)
			{
				action.payload.forEach((childAction) => {
					dispatchChildActions(store, childAction);
				});
			}
			else
			{
				store.dispatch(action);
			}
		}

		return function(next) {
			return function(action) {
				if (action && action.meta && action.meta.batch)
				{
					dispatchChildActions(store, action);
				}

				return next(action);
			};
		};
	}

	module.exports = {
		batchActions,
		enableBatching,
		batchDispatchMiddleware,
	};
});
