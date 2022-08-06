/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/element/chat-title
 */
jn.define('im/messenger/lib/element/chat-title', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { Loc } = jn.require('loc');
	const { DialogHelper } = jn.require('im/messenger/lib/helper');

	const DialogSpecialType = Object.freeze({
		group: 'chat',
		channel: 'open',
	});

	/**
	 * @class ChatTitle
	 */
	class ChatTitle
	{
		static createFromDialogId(dialogId, options = {})
		{
			return new this(dialogId, options = {});
		}

		constructor(dialogId, options = {})
		{
			this.name = null;
			this.description = null;

			if (DialogHelper.isDialogId(dialogId))
			{
				this.createDialogTitle(dialogId);
			}
			else
			{
				this.createUserTitle(dialogId);
			}
		}

		createDialogTitle(dialogId)
		{
			const dialog = MessengerStore.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return;
			}

			this.name = dialog.name;

			//TODO: add special types like announcement, call etc.
			if (dialog.type && dialog.type === DialogSpecialType.channel)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_CHANNEL');
			}
			else
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_GROUP');
			}
		}

		createUserTitle(userId)
		{
			const user = MessengerStore.getters['usersModel/getUserById'](userId);
			if (!user)
			{
				return;
			}

			this.name = user.name;
			if (Type.isStringFilled(user.work_position))
			{
				this.description = user.work_position;
			}
			else
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_EMPLOYEE');
			}
		}

		getTitleParams()
		{
			const titleParams = {};

			if (this.name)
			{
				titleParams.text = this.name;
			}

			if (this.description)
			{
				titleParams.detailText = this.description;
			}

			return titleParams;
		}
	}

	module.exports = {
		ChatTitle,
	};
});
