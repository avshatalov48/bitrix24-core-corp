/**
 * @module im/messenger/const/copilot-button
 */
jn.define('im/messenger/const/copilot-button', (require, exports, module) => {
	const CopilotButtonType = {
		promptSend: 'promt-send',
		promptEdit: 'promt-edit',
		copy: 'copy',
	};

	const CopilotPromptType = {
		default: 'default',
		simpleTemplate: 'simpleTemplate',
	};

	module.exports = { CopilotButtonType, CopilotPromptType };
});
