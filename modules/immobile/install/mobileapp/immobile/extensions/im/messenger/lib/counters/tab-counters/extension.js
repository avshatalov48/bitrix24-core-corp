/**
 * @module im/messenger/lib/counters/tab-counters
 */
jn.define('im/messenger/lib/counters/tab-counters', (require, exports, module) => {
	const { ComponentCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { CopilotCounters } = require('im/messenger/lib/counters/tab-counters/copilot');
	const { ChatCounters } = require('im/messenger/lib/counters/tab-counters/chat');
	const { ChannelCounters } = require('im/messenger/lib/counters/tab-counters/channel');
	const { CollabCounters } = require('im/messenger/lib/counters/tab-counters/collab');

	function createTabCountersByComponent()
	{
		const componentCode = MessengerParams.getComponentCode();
		if (componentCode === ComponentCode.imCopilotMessenger)
		{
			return new CopilotCounters();
		}

		if (componentCode === ComponentCode.imChannelMessenger)
		{
			return new ChannelCounters();
		}

		if (componentCode === ComponentCode.imCollabMessenger)
		{
			return new CollabCounters();
		}

		return new ChatCounters();
	}

	module.exports = {
		createTabCountersByComponent,
	};
});
