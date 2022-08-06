/**
 * @module statemanager/vuex-manager
 */
jn.define('statemanager/vuex-manager', (require, exports, module) => {

	const { StateStorage } = jn.require('statemanager/vuex-manager/storage/base');
	const { VuexManager } = jn.require('statemanager/vuex-manager/vuex-manager');

	module.exports = {
		StateStorage,
		VuexManager,
	};
});
