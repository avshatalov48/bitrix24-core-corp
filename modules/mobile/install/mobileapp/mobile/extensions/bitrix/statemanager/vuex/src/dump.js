/**
 * @module statemanager/vuex/reactivity
 */
jn.define('statemanager/vuex/reactivity', (require, exports, module) => {

	function reactive(target) {
		return target;
	}

	function watch() {}

	function inject() {}

	module.exports = { inject, reactive, watch };
});