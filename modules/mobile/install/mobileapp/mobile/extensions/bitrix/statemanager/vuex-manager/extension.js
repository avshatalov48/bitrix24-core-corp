/**
 * @module statemanager/vuex-manager
 */
jn.define('statemanager/vuex-manager', (require, exports, module) => {

	const { StateStorage } = require('statemanager/vuex-manager/storage/base');
	const { VuexManager } = require('statemanager/vuex-manager/vuex-manager');

	module.exports = {
		StateStorage,
		VuexManager,
	};
});
