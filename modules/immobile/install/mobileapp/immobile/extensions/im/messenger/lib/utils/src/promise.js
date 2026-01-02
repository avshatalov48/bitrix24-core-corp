/**
 * @module im/messenger/lib/utils/promise
 */
jn.define('im/messenger/lib/utils/promise', (require, exports, module) => {

	/**
	 * @return {{resolve: function, reject: function, promise: Promise}}
	 */
	function createPromiseWithResolvers()
	{
		let resolvePromise = () => {};

		let rejectPromise = () => {};
		const promise = new Promise((resolve, reject) => {
			resolvePromise = resolve;
			rejectPromise = reject;
		});

		return {
			promise,
			resolve: resolvePromise,
			reject: rejectPromise,
		};
	}

	module.exports = {
		createPromiseWithResolvers,
	};
});
