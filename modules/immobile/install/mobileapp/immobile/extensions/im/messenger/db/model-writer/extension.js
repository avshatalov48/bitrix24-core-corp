/**
 * @module im/messenger/db/model-writer
 */
jn.define('im/messenger/db/model-writer', (require, exports, module) => {
	const { VuexModelWriter } = require('im/messenger/db/model-writer/vuex');

	module.exports = {
		VuexModelWriter,
	};
});
