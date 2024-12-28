/**
 * @module im/messenger/controller/chat-composer/update/channel
 */
jn.define('im/messenger/controller/chat-composer/update/channel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DialogType, WidgetTitleParamsType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('chat-composer--channel');

	const { UpdateGroupChat } = require('im/messenger/controller/chat-composer/update/group-chat');
	const { ChannelView } = require('im/messenger/controller/chat-composer/lib/view/channel');

	/**
	 * @class UpdateChannel
	 */
	class UpdateChannel extends UpdateGroupChat
	{
		openChannelView({ titleType = WidgetTitleParamsType.entity } = {})
		{
			PageManager.openWidget('layout', {
				titleParams: {
					text: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_UPDATE_CHANNEL_TITLE'),
					type: titleType,
				},
				modal: true,
			})
				.then((widget) => {
					this.mainView = ChannelView.openToEdit(this.getDialogInfoProps());
					this.mainWidget = widget;
					this.mainWidget.showComponent(this.mainView);
					this.mainWidget.expandBottomSheet();
					this.mainWidget.setLeftButtons([]);
					this.mainWidget.setRightButtons([
						{
							id: 'cross',
							type: 'cross',
							callback: () => this.checkBeforeCloseWidget(),
						},
					]);
					this.mainWidget.setBackButtonHandler(() => {
						this.checkBeforeCloseWidget();

						return true;
					});
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.PageManager.openWidget.catch:`, error);
				});
		}

		/**
		 * @param {boolean} isOpenEntityType
		 * @return {DialogType}
		 */
		getTypeByEntityType(isOpenEntityType)
		{
			return isOpenEntityType ? DialogType.openChannel : DialogType.channel;
		}

		/**
		 * @return {string}
		 */
		getDialogTypeWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_CHANNEL_TITLE');
		}

		/**
		 * @return {string}
		 */
		getParticipantWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SUBSCRIBERS_TITLE');
		}
	}

	module.exports = { UpdateChannel };
});
