/**
 * @module im/messenger/const
 */
jn.define('im/messenger/const', (require, exports, module) => {

	const { EventType } = jn.require('im/messenger/const/event-type');
	const { FeatureFlag } = jn.require('im/messenger/const/feature-flag');
	const {
		RestMethod,
	} = jn.require('im/messenger/const/rest');
	const {
		ChatTypes,
		MessageStatus,
	} = jn.require('im/messenger/const/recent');
	const { MessageType } = jn.require('im/messenger/const/message-type');
	const { DialogType } = jn.require('im/messenger/const/dialog-type');

	module.exports = {
		EventType,
		FeatureFlag,
		ChatTypes,
		MessageStatus,
		RestMethod,
		MessageType,
		DialogType,
	};
});
