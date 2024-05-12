/**
 * @module im/messenger/lib/counters
 */
jn.define('im/messenger/lib/counters', (require, exports, module) => {
	const { MessengerParams } = require('im/messenger/lib/params');
	const { CopilotCounters } = require('im/messenger/lib/counters/copilot-counters');
	const { ChatCounters } = require('im/messenger/lib/counters/chat-counters');
	const COPILOT_COMPONENT_CODE = 'im.copilot.messenger';

	function createByComponent()
	{
		const componentCode = MessengerParams.get('COMPONENT_CODE');
		if (componentCode === COPILOT_COMPONENT_CODE)
		{
			return new CopilotCounters();
		}

		return new ChatCounters();
	}

	module.exports = {
		Counters: createByComponent(),
	};
});
