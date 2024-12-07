/**
 * @module statemanager/vuex-manager
 */
jn.define('statemanager/vuex-manager', (require, exports, module) => {

	const { StateStorage } = require('statemanager/vuex-manager/storage/base');
	const { MutationManager } = require('statemanager/vuex-manager/mutation-manager');
	const {
		StateStorageSaveStrategy,
		VuexManager,
	} = require('statemanager/vuex-manager/vuex-manager');

	module.exports = {
		StateStorage,
		StateStorageSaveStrategy,
		MutationManager,
		VuexManager,
	};
});
