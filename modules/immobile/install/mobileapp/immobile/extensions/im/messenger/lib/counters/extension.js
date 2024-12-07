/**
 * @module im/messenger/lib/counters
 */
jn.define('im/messenger/lib/counters', (require, exports, module) => {
	const { MessengerParams } = require('im/messenger/lib/params');
	const { CopilotCounters } = require('im/messenger/lib/counters/copilot-counters');
	const { ChatCounters } = require('im/messenger/lib/counters/chat-counters');
	const { ChannelCounters } = require('im/messenger/lib/counters/channel-counters');
	const COPILOT_COMPONENT_CODE = 'im.copilot.messenger';
	const CHANNEL_COMPONENT_CODE = 'im.channel.messenger';

	function createByComponent()
	{
		const componentCode = MessengerParams.getComponentCode();
		if (componentCode === COPILOT_COMPONENT_CODE)
		{
			return new CopilotCounters();
		}

		if (componentCode === CHANNEL_COMPONENT_CODE)
		{
			return new ChannelCounters();
		}

		return new ChatCounters();
	}

	module.exports = {
		Counters: createByComponent(),
	};
});
