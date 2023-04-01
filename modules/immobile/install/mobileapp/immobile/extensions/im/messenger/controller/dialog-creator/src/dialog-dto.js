/**
 * @module im/messenger/controller/dialog-creator/dialog-dto
 */
jn.define('im/messenger/controller/dialog-creator/dialog-dto', (require, exports, module) => {

	class DialogDTO
	{
		constructor()
		{
			/** @private */
			this.type = null;
			this.recipientList = null;
			this.title = null;
			this.avatar = null;
			this.avatarPreview = null;
		}

		/**
		 * @param {string} type
		 */
		setType(type)
		{
			this.type = type;

			return this;
		}

		getType()
		{
			return this.type;
		}

		setRecipientList(recipientList)
		{
			this.recipientList = recipientList;

			return this;
		}

		getRecipientList()
		{
			return this.recipientList ? this.recipientList : [];
		}

		setTitle(title)
		{
			this.title = title;

			return this;
		}

		setAvatar(avatar)
		{
			this.avatar = avatar;

			return this;
		}

		setAvatarPreview(avatarPreview)
		{
			this.avatarPreview = avatarPreview;

			return this;
		}

		getAvatarPreview()
		{
			return this.avatarPreview;
		}

		getAvatar()
		{
			return this.avatar;
		}

		getTitle()
		{
			return this.title ? this.title : '';
		}

		getResult()
		{
			return {
				chatType: this.type,
				recipientList: this.recipientList,
				chatTitle: this.title,
				chatAvatar: this.avatar,
			}
		}
	}

	module.exports = { DialogDTO };
});