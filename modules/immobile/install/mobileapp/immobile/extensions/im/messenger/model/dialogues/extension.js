/**
 * @module im/messenger/model/dialogues
 */
jn.define('im/messenger/model/dialogues', (require, exports, module) => {
	const { dialoguesModel } = require('im/messenger/model/dialogues/model');
	const { dialogDefaultElement } = require('im/messenger/model/dialogues/default-element');
	const { copilotDefaultElement } = require('im/messenger/model/dialogues/copilot/default-element');
	const { collabDefaultElement } = require('im/messenger/model/dialogues/collab/default-element');

	module.exports = {
		dialoguesModel,

		dialogDefaultElement,
		copilotDefaultElement,
		collabDefaultElement,
	};
});
