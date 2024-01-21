/**
 * @module im/messenger/core
 */
jn.define('im/messenger/core', (require, exports, module) => {
	const { CoreApplication } = require('im/messenger/core/application');

	module.exports = {
		CoreApplication,
		core: new CoreApplication(),
	};
});
