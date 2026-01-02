/**
 * @module im/messenger-v2/lib/wait-view-loaded
 */
jn.define('im/messenger-v2/lib/wait-view-loaded', (require, exports, module) => {
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils/promise');

	async function waitViewLoaded()
	{
		const {
			resolve,
			promise,
		} = createPromiseWithResolvers();

		BX.onViewLoaded(() => {
			resolve();
		});

		return promise;
	}

	module.exports = { waitViewLoaded };
});
