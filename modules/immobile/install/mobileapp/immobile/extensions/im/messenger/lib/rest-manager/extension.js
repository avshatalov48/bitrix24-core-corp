/**
 * @module im/messenger/lib/rest-manager
 */
jn.define('im/messenger/lib/rest-manager', (require, exports, module) => {

	const { RestManager } = jn.require('im/messenger/lib/rest-manager/rest-manager');

	module.exports = {
		RestManager: new RestManager(),
	};
});
