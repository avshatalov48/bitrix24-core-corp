/**
 * @module im/messenger/const
 */
jn.define('im/messenger/const', (require, exports, module) => {

	const { EventType } = require('im/messenger/const/event-type');
	const { FeatureFlag } = require('im/messenger/const/feature-flag');
	const {
		RestMethod,
	} = require('im/messenger/const/rest');
	const {
		ChatTypes,
		MessageStatus,
	} = require('im/messenger/const/recent');
	const {
		UserExternalType,
	} = require('im/messenger/const/user');
	const {
		MessageType,
		OwnMessageStatus,
	} = require('im/messenger/const/message');
	const { ReactionType } = require('im/messenger/const/reaction-type');
	const { DialogType } = require('im/messenger/const/dialog-type');
	const { FileStatus } = require('im/messenger/const/file-status');
	const {
		FileType,
		FileEmojiType,
	} = require('im/messenger/const/file-type');
	const { Color } = require('im/messenger/const/color');

	module.exports = {
		EventType,
		FeatureFlag,
		ChatTypes,
		MessageStatus,
		RestMethod,
		MessageType,
		OwnMessageStatus,
		ReactionType,
		DialogType,
		FileStatus,
		FileType,
		FileEmojiType,
		UserExternalType,
		Color,
	};
});
