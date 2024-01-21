/* MIT License

Copyright (c) 2016 Terry Appleby

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
 * @module statemanager/redux/batched-subscribe
 */
jn.define('statemanager/redux/batched-subscribe', (require, exports, module) => {
	function batchedSubscribe(batch)
	{
		if (typeof batch !== 'function')
		{
			throw new TypeError('Expected batch to be a function.');
		}

		let currentListeners = [];
		let nextListeners = currentListeners;

		function ensureCanMutateNextListeners()
		{
			if (nextListeners === currentListeners)
			{
				nextListeners = [...currentListeners];
			}
		}

		function subscribe(listener)
		{
			if (typeof listener !== 'function')
			{
				throw new TypeError('Expected listener to be a function.');
			}

			let isSubscribed = true;

			ensureCanMutateNextListeners();
			nextListeners.push(listener);

			return function unsubscribe() {
				if (!isSubscribed)
				{
					return;
				}

				isSubscribed = false;

				ensureCanMutateNextListeners();
				const index = nextListeners.indexOf(listener);
				nextListeners.splice(index, 1);
			};
		}

		function notifyListeners()
		{
			const listeners = currentListeners = nextListeners;
			for (const listener of listeners)
			{
				listener();
			}
		}

		function notifyListenersBatched()
		{
			batch(notifyListeners);
		}

		return (next) => (...args) => {
			const store = next(...args);
			const subscribeImmediate = store.subscribe;

			function dispatch(...dispatchArgs)
			{
				const res = store.dispatch(...dispatchArgs);
				notifyListenersBatched();

				return res;
			}

			return {
				...store,
				dispatch,
				subscribe,
				subscribeImmediate,
			};
		};
	}

	module.exports = {
		batchedSubscribe,
	};
});
