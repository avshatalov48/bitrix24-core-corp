/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/create-channel
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/create-channel', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { Loc } = require('loc');

	class CreateChannelBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			const desc1 = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHANNEL_CREATE_BANNER_DESC_1');
			const desc2 = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHANNEL_CREATE_BANNER_DESC_2');
			const userData = this.getOwnerChannel();
			this.message[0].text = `${desc1}\n\n${desc2}`;
			if (userData?.id && userData?.name)
			{
				this.message[0].text = `${desc1}\n\n${desc2} [USER=${userData.id}]${userData.name}[/USER]`;
			}
		}

		/**
		 * @return {?UsersModelState}
		 */
		getOwnerChannel()
		{
			const messageData = this.getModelMessage();
			if (messageData)
			{
				const dialogData = this.getCore().getStore().getters['dialoguesModel/getByChatId'](messageData.chatId);

				return this.getCore().getStore().getters['usersModel/getById'](dialogData.owner);
			}

			return {};
		}
	}

	module.exports = { CreateChannelBanner };
});
