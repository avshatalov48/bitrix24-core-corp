/**
 * @module im/messenger/controller/dialog/lib/message-menu/message
 */

jn.define('im/messenger/controller/dialog/lib/message-menu/message', (require, exports, module) => {
	const {
		DialogType,
		FeatureFlag,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { MessageHelper, DialogHelper } = require('im/messenger/lib/helper');

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
		 * @param {boolean} isUserSubscribed
		 */
		constructor({
			messageModel,
			fileModel,
			dialogModel,
			userModel,
			isPinned,
			isUserSubscribed,
		})
		{
			this.message = messageModel;
			this.file = fileModel;
			this.dialog = dialogModel;
			this.user = userModel;
			this.isPinned = isPinned;
			this.isUserSubscribed = isUserSubscribed;
			this.messageHelper = MessageHelper.createByModel(messageModel, fileModel ?? []);
			this.dialogHelper = DialogHelper.createByModel(dialogModel);
		}

		isPossibleReact()
		{
			// return this.dialog.type !== DialogType.copilot; TODO experimental solution
			return true;
		}

		isPossibleReply()
		{
			if (this.#isChannel() || this.dialog.type === DialogType.copilot)
			{
				return false;
			}

			if (Number(this.dialog.parentMessageId) === Number(this.message.id))
			{
				return false;
			}

			return ChatPermission.isCanReply(this.dialog);
		}

		isPossibleCopy()
		{
			if (this.isDialogCopilot())
			{
				return this.#isText() && !this.#isDeleted();
			}

			return this.#isText();
		}

		isPossibleCopyLink()
		{
			if (this.isDialogCopilot() || this.#isComment())
			{
				return false;
			}

			return true;
		}

		isPossiblePin()
		{
			if (this.dialog.type === DialogType.comment)
			{
				return false;
			}

			if (!ChatPermission.isCanPost(this.dialog))
			{
				return false;
			}

			return !this.isPinned;
		}

		isPossibleUnpin()
		{
			if (this.dialog.type === DialogType.comment)
			{
				return false;
			}

			if (!ChatPermission.isCanPost(this.dialog))
			{
				return false;
			}

			return this.isPinned;
		}

		isPossibleForward()
		{
			if (this.dialog.type === DialogType.comment)
			{
				return false;
			}

			return true;
		}

		isPossibleCreate()
		{
			if (this.#isChannel())
			{
				return false;
			}

			return true;
		}

		isPossibleSaveToLibrary()
		{
			return (this.#isImage() || this.#isVideo() || this.#isAudio())
				&& !this.#isDeleted()
				&& !this.#isGallery()
				&& FeatureFlag.native.utilsSaveToLibrarySupported
			;
		}

		isPossibleShowProfile()
		{
			return !this.#isYour() && !this.#isSystem() && !this.isDialogCopilot() && !this.#isBot();
		}

		isPossibleCallFeedback()
		{
			return !this.#isYour() && !this.#isSystem() && this.isDialogCopilot();
		}

		isPossibleMultiselect()
		{
			if (!Feature.isMultiSelectAvailable)
			{
				return false;
			}

			if (this.dialog.type === DialogType.comment)
			{
				return false;
			}

			return !this.message.sending;
		}

		isPossibleEdit()
		{
			if (this.isDialogCopilot())
			{
				return this.#isYour() && !this.#isDeleted() && !this.#isSystem() && !this.#isForward()
				&& !this.#isMessageToCopilot();
			}

			return this.#isYour() && !this.#isDeleted() && !this.#isSystem() && !this.#isForward();
		}

		isPossibleDelete()
		{
			if (this.#isYour())
			{
				return !this.#isDeleted();
			}

			if (ChatPermission.isCanDeleteOtherMessage(this.dialog))
			{
				return !this.#isDeleted();
			}

			return false;
		}

		isPossibleSubscribe()
		{
			return this.#isChannel() && !this.isUserSubscribed && !this.#isSystem() && !this.#isEmojiOrSmileOnly();
		}

		isPossibleUnsubscribe()
		{
			return this.#isChannel() && this.isUserSubscribed && !this.#isSystem() && !this.#isEmojiOrSmileOnly();
		}

		isPossibleResend()
		{
			return this.#isSendError();
		}

		#isDeleted()
		{
			return this.messageHelper.isDeleted;
		}

		#isForward()
		{
			return this.messageHelper.isForward;
		}

		#isGallery()
		{
			return this.messageHelper.isGallery;
		}

		#isVideo()
		{
			return this.messageHelper.isVideo;
		}

		#isImage()
		{
			return this.messageHelper.isImage;
		}

		#isAudio()
		{
			return this.messageHelper.isAudio;
		}

		#isSystem()
		{
			return this.messageHelper.isSystem;
		}

		#isText()
		{
			return this.messageHelper.isText;
		}

		#isYour()
		{
			return this.messageHelper.isYour;
		}

		isDialogCopilot()
		{
			return this.dialog.type === DialogType.copilot;
		}

		#isBot()
		{
			return this.user.bot;
		}

		#isMessageToCopilot()
		{
			return !(this.message.text.includes('[USER') || this.message.text.includes('[user'));
		}

		isAdmin()
		{
			return this.dialogHelper.isCurrentUserOwner;
		}

		#isChannel()
		{
			return this.dialogHelper.isChannel;
		}

		#isComment()
		{
			return this.dialogHelper.isComment;
		}

		#isEmojiOrSmileOnly()
		{
			return this.messageHelper.isEmojiOnly || this.messageHelper.isSmileOnly;
		}

		#isSendError()
		{
			return this.message.error === true;
		}
	}

	module.exports = { MessageMenuMessage };
});
