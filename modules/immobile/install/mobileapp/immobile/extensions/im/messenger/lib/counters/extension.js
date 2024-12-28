/**
 * @module im/messenger/lib/counters
 */
jn.define('im/messenger/lib/counters', (require, exports, module) => {
	const { ComponentCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { CopilotCounters } = require('im/messenger/lib/counters/copilot-counters');
	const { ChatCounters } = require('im/messenger/lib/counters/chat-counters');
	const { ChannelCounters } = require('im/messenger/lib/counters/channel-counters');
	const { CollabCounters } = require('im/messenger/lib/counters/collab-counters');

	function createByComponent()
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
		Counters: createByComponent(),
	};
});
