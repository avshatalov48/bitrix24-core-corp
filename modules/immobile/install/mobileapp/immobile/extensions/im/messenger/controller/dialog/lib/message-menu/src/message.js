/**
 * @module im/messenger/controller/dialog/lib/message-menu/message
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/message', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		FileType,
		DialogType,
		FeatureFlag,
	} = require('im/messenger/const');
	/**
	 * @class MessageMenuMessage
	 */
	class MessageMenuMessage
	{
		/**
		 *
		 * @param {MessagesModelState} messageModel
		 * @param {FilesModelState || undefined} fileModel
		 * @param {DialoguesModelState} dialogModel
		 * @param {UsersModelState} userModel
		 * @param {boolean} isPinned
		 */
		constructor({ messageModel, fileModel, dialogModel, userModel, isPinned })
		{
			this.message = messageModel;
			this.file = fileModel;
			this.dialog = dialogModel;
			this.user = userModel;
			this.isPinned = isPinned;
		}

		isPossibleReact()
		{
			return this.dialog.type !== DialogType.copilot;
		}

		isPossibleReply()
		{
			return this.dialog.type !== DialogType.copilot;
		}

		isPossibleCopy()
		{
			return this.isText();
		}

		isPossiblePin()
		{
			return !this.isPinned;
		}

		isPossibleForward()
		{
			return true;
		}

		isPossibleSaveToLibrary()
		{
			return (this.isImage() || this.isVideo())
				&& !this.isDeleted()
				&& FeatureFlag.native.utilsSaveToLibrarySupported
			;
		}

		isPossibleShowProfile()
		{
			return !this.isYour() && !this.isSystem() && !this.isDialogCopilot() && !this.isBot();
		}

		isPossibleEdit()
		{
			return this.isYour() && !this.isDeleted() && !this.isSystem() && !this.isForward();
		}

		isPossibleDelete()
		{
			return this.isYour() && !this.isDeleted() && !this.isSystem();
		}

		isDeleted()
		{
			return this.message.params.IS_DELETED === 'Y';
		}

		isForward()
		{
			return !Type.isUndefined(this.message?.forward.id);
		}

		isVideo()
		{
			return this.file?.type === FileType.video;
		}

		isImage()
		{
			return this.file?.type === FileType.image;
		}

		isSystem()
		{
			return this.message.authorId === 0;
		}

		isText()
		{
			return this.message.text !== '';
		}

		isYour()
		{
			return this.message.authorId === MessengerParams.getUserId();
		}

		isDialogCopilot()
		{
			return this.dialog.type === DialogType.copilot;
		}

		isBot()
		{
			return this.user.bot;
		}
	}

	module.exports = { MessageMenuMessage };
});
