/**
 * @module im/messenger-v2/controller/navigation
 */
jn.define('im/messenger-v2/controller/navigation', (require, exports, module) => {
	const { NavigationController } = require('im/messenger-v2/controller/navigation/controller');
	const { NavigationApiHandler } = require('im/messenger-v2/controller/navigation/api-handler');

	module.exports = { NavigationController, NavigationApiHandler };
});
