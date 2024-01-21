/**
 * @module im/messenger/lib/converter/recent
 */
jn.define('im/messenger/lib/converter/recent', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { core } = require('im/messenger/core');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Uuid } = require('utils/uuid');
	const {
		RecentItem,
		ChatItem,
		UserItem,
		CallItem,
		AnnouncementItem,
		ExtranetItem,
		Support24NotifierItem,
		Support24QuestionItem,
		CurrentUserItem,
		BotItem,
		SupportBotItem,
		ConnectorUserItem,
		ExtranetUserItem,
		InvitedUserItem,
		NetworkUserItem,
	} = require('im/messenger/lib/element');
	const {
		DialogType,
		BotType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('recent--converter');

	/**
	 * @class RecentConverter
	 */
	class RecentConverter
	{
		// TODO: Migrate and refactor ChatDataConverter to new converters
		constructor()
		{
			ChatDataConverter.init({
				userId: MessengerParams.getUserId(),
				listType: 'recent',
				generalChatId: MessengerParams.getGeneralChatId(),
			});
		}

		toList(recentItems)
		{
			const listItems = [];

			recentItems.forEach((item) => {
				listItems.push(this.toItem(item));
			});

			return listItems;
		}

		toItem(item)
		{
			const modelItem = core.getStore().getters['recentModel/getById'](item.id);
			const dialog = core.getStore().getters['dialoguesModel/getById'](modelItem.id);
			if (!dialog)
			{
				logger.error(`RecentConverter.toItem: there is no dialog "${item.id}" in model`);

				return new RecentItem(modelItem);
			}

			if (DialogHelper.isDialogId(dialog.dialogId))
			{
				return this.toChatItem(item);
			}

			return this.toUserItem(modelItem);
		}

		toChatItem(modelItem)
		{
			const dialog = core.getStore().getters['dialoguesModel/getById'](modelItem.id);
			if (!dialog)
			{
				logger.error(`RecentConverter.toChatItem: there is no dialog "${modelItem.id}" in model`);

				return new RecentItem(modelItem);
			}

			if (dialog.extranet === true)
			{
				return new ExtranetItem(modelItem);
			}

			if (dialog.type === DialogType.announcement)
			{
				return new AnnouncementItem(modelItem);
			}

			if (dialog.type === DialogType.support24Notifier)
			{
				return new Support24NotifierItem(modelItem);
			}

			if (dialog.type === DialogType.support24Question)
			{
				return new Support24QuestionItem(modelItem);
			}

			return new ChatItem(modelItem);
		}

		toUserItem(modelItem)
		{
			const user = core.getStore().getters['usersModel/getById'](modelItem.id);
			if (!user)
			{
				logger.error(`RecentConverter.toUserItem: there is no user "${modelItem.id}" in model`);

				return new RecentItem(modelItem);
			}

			if (user.id === core.getUserId())
			{
				return new CurrentUserItem(modelItem);
			}

			// eslint-disable-next-line es/no-optional-chaining
			if (modelItem?.invitation?.isActive === true && user.lastActivityDate !== false)
			{
				return new InvitedUserItem(modelItem);
			}

			if (user.botData.type === BotType.support24)
			{
				return new SupportBotItem(modelItem);
			}

			if (user.network === true)
			{
				return new NetworkUserItem(modelItem);
			}

			if (user.bot === true)
			{
				return new BotItem(modelItem);
			}

			if (user.extranet === true)
			{
				return new ExtranetUserItem(modelItem);
			}

			if (user.connector === true)
			{
				return new ConnectorUserItem(modelItem);
			}

			return new UserItem(modelItem);
		}

		toCallItem(callStatus, call)
		{
			const listItem = new CallItem(callStatus, call);

			const dialogId = call.associatedEntity.id;
			const recentItem = core.getStore().getters['recentModel/getById'](dialogId);
			if (recentItem && recentItem.color)
			{
				listItem.color = recentItem.color;
			}

			return listItem;
		}

		// TODO: moved from old im.recent, need to refactor
		fromPushToModel(element)
		{
			let newElement = {};
			const recentItem = core.getStore().getters['recentModel/getById'](element.id);
			if (recentItem)
			{
				newElement = clone(recentItem);
			}
			else
			{
				newElement = {
					avatar: {},
					user: { id: 0 },
					message: {},
					counter: 0,
					blocked: false,
					writing: false,
					liked: false,
				};
				if (element.id.toString().indexOf('chat') == 0)
				{
					newElement.type = 'chat';
					newElement.id = element.id;
					newElement.chat = {};
					if (typeof element.chat === 'undefined')
					{
						return false;
					}
				}
				else
				{
					newElement.type = 'user';
					newElement.id = parseInt(element.id);
					newElement.user = {};
					if (typeof element.user === 'undefined')
					{
						return false;
					}
				}

				if (typeof element.message === 'undefined')
				{
					return false;
				}
			}

			if (typeof element.message !== 'undefined')
			{
				let id = element.message.id;
				if (!Number.isInteger(element.message.id) && !Uuid.isV4(element.message.id))
				{
					id = parseInt(id);
				}

				newElement.message.id = id;
				newElement.message.text = ChatMessengerCommon.purifyText(element.message.text, element.message.params);
				newElement.message.author_id = element.message.senderId && element.message.system !== 'Y' ? element.message.senderId : 0;
				newElement.message.date = new Date(element.message.date);
				newElement.message.file = element.message.params && element.message.params.FILE_ID ? element.message.params.FILE_ID.length > 0 : false;
				newElement.message.attach = element.message.params && element.message.params.ATTACH ? element.message.params.ATTACH : false;
				newElement.message.status = element.message.status ? element.message.status : '';
				newElement.message.subTitleIcon = element.message.subTitleIcon ? element.message.subTitleIcon : '';
			}

			if (typeof element.counter !== 'undefined')
			{
				newElement.counter = element.counter;
			}

			if (typeof element.writing !== 'undefined')
			{
				newElement.writing = element.writing;
			}

			if (typeof element.user !== 'undefined')
			{
				element.user.id = parseInt(element.user.id);
				if (element.user.id > 0)
				{
					newElement.user = element.user = this.getUserDataFormat(element.user);

					if (newElement.type == 'user')
					{
						newElement.avatar = element.user.avatar;
						newElement.color = element.user.color;
						newElement.title = element.user.name;
					}
				}
				else
				{
					newElement.user = element.user;
				}
			}

			if (newElement.type == 'chat' && typeof element.chat !== 'undefined')
			{
				element.chat.id = parseInt(element.chat.id);
				element.chat.date_create = new Date(element.chat.date_create);
				newElement.chat = element.chat;

				newElement.avatar = element.chat.avatar;
				newElement.color = element.chat.color;
				newElement.title = element.chat.name;

				if (element.chat.type == 'lines' && element.lines != 'undefined')
				{
					if (typeof newElement.lines === 'undefined')
					{
						newElement.lines = {};
					}
					newElement.lines.id = parseInt(element.lines.id);
					newElement.lines.status = parseInt(element.lines.status);
				}
			}

			return newElement;
		}

		getUserDataFormat(user)
		{
			user = ChatDataConverter.getUserDataFormat(user);

			if (user.id > 0)
			{
				if (typeof (user.name) !== 'undefined')
				{
					user.name = ChatUtils.htmlspecialcharsback(user.name);
				}

				if (typeof (user.last_name) !== 'undefined')
				{
					user.last_name = ChatUtils.htmlspecialcharsback(user.last_name);
				}

				if (typeof (user.first_name) !== 'undefined')
				{
					user.first_name = ChatUtils.htmlspecialcharsback(user.first_name);
				}

				if (typeof (user.work_position) !== 'undefined')
				{
					user.work_position = ChatUtils.htmlspecialcharsback(user.work_position);
				}
			}

			return user;
		}
	}

	module.exports = {
		RecentConverter: new RecentConverter(),
	};
});
