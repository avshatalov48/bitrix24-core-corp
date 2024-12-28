/**
 * @module im/messenger/controller/dialog-creator/dialog-creator
 */
jn.define('im/messenger/controller/dialog-creator/dialog-creator', (require, exports, module) => {
	/* global ChatUtils */
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { NavigationSelector } = require('im/messenger/controller/dialog-creator/navigation-selector');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const {
		RestMethod,
		DialogType,
		EventType,
		ComponentCode,
		BotCode,
		Analytics,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { AnalyticsEvent } = require('analytics');
	const { CopilotRoleSelector } = require('layout/ui/copilot-role-selector');
	const { ChannelCreator } = require('im/messenger/controller/channel-creator');
	const { Feature } = require('im/messenger/lib/feature');

	class DialogCreator
	{
		constructor(options = {})
		{
			this.store = serviceLocator.get('core').getStore();
			this.messagerInitService = serviceLocator.get('messenger-init-service');
			this.selector = () => {};
			this.bindMethods();
			this.subscribeInitMessengerEvent();
		}

		subscribeInitMessengerEvent()
		{
			this.messagerInitService.onInit(this.handleUserGet);
		}

		bindMethods()
		{
			this.handleUserGet = this.handleUserGet.bind(this);
		}

		open()
		{
			const userList = this.prepareItems(this.getUserList());

			NavigationSelector.open(
				{
					userList,
				},
			);
		}

		createChannelDialog()
		{
			ChannelCreator.open({
				userList: this.prepareItems(this.getUserList()),
				analytics: new AnalyticsEvent().setSection(Analytics.Section.channelTab),
			});
		}

		async createCollab()
		{
			if (!Feature.isCollabSupported)
			{
				Feature.showUnsupportedWidget();

				return;
			}

			try
			{
				const { openCollabCreate } = await requireLazy('collab/create');

				await openCollabCreate({
					// todo provide some analytics here
				});
			}
			catch (error)
			{
				console.error(error);
			}
		}

		createCopilotDialog()
		{
			this.sendAnalyticsStartCreateCopilotDialog();

			CopilotRoleSelector.open({
				showOpenFeedbackItem: true,
				openWidgetConfig: {
					backdrop: {
						mediumPositionPercent: 75,
						horizontalSwipeAllowed: false,
						onlyMediumPosition: false,
					},
				},
			})
				.then((result) => {
					Logger.log(`${this.constructor.name}.CopilotRoleSelector.result:`, result);
					const fields = {
						type: DialogType.copilot.toUpperCase(),
					};

					if (result?.role?.code)
					{
						fields.copilotMainRole = result?.role?.code;
					}

					this.callRestCreateCopilotDialog(fields);
				})
				.catch((error) => Logger.error(error));
		}

		sendAnalyticsStartCreateCopilotDialog()
		{
			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.copilot)
					.setEvent(Analytics.Event.clickCreateNew)
					.setType(Analytics.Type.copilot)
					.setSection(Analytics.Section.copilotTab);

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendAnalyticsStartCreateCopilotDialog.catch:`, e);
			}
		}

		callRestCreateCopilotDialog(fields)
		{
			BX.rest.callMethod(
				RestMethod.imV2ChatAdd,
				{ fields },
			).then((result) => {
				const chatId = parseInt(result.data().chatId, 10);
				if (chatId > 0)
				{
					setTimeout(
						() => {
							MessengerEmitter.emit(
								EventType.messenger.openDialog,
								{
									dialogId: `chat${chatId}`,
									context: OpenDialogContextType.chatCreation,
								},
								ComponentCode.imCopilotMessenger,
							);

							const analytics = new AnalyticsEvent()
								.setTool(Analytics.Tool.ai)
								.setCategory(Analytics.Category.chatOperations)
								.setEvent(Analytics.Event.createNewChat)
								.setType(Analytics.Type.ai)
								.setSection(Analytics.Section.copilotTab)
								.setP3(Analytics.CopilotChatType.private)
								.setP5(`chatId_${chatId}`);

							analytics.send();
						},
						200,
					);

					if (result.answer.error || result.error())
					{
						Logger.error(`${this.constructor.name}.callRestCreateCopilotDialog.result.error`, result.error());
					}
				}
			})
				.catch(
					(err) => {
						Logger.error(`${this.constructor.name}.callRestCreateCopilotDialog.catch:`, err);
					},
				);
		}

		getUserList()
		{
			/**
			 * @type {Array<UsersModelState>}
			 */
			const userItems = [];

			const recentUserList = ChatUtils.objectClone(this.store.getters['recentModel/getUserList']());
			const recentUserListIndex = {};
			if (Type.isArrayFilled(recentUserList))
			{
				recentUserList.forEach((recentUserChat) => {
					const userStateModel = this.store.getters['usersModel/getById'](recentUserChat.id);
					if (userStateModel)
					{
						recentUserListIndex[recentUserChat.id] = true;

						userItems.push(userStateModel);
					}
				});
			}

			const colleaguesList = ChatUtils.objectClone(this.store.getters['usersModel/getList']());
			if (Type.isArrayFilled(colleaguesList))
			{
				colleaguesList.forEach((user) => {
					if (recentUserListIndex[user.id])
					{
						return;
					}

					userItems.push(user);
				});
			}

			return userItems.filter((userItem) => {
				if (userItem.id === MessengerParams.getUserId())
				{
					return false;
				}

				if (userItem.connector)
				{
					return false;
				}

				if (userItem?.botData?.code)
				{
					return userItem?.botData?.code !== BotCode.copilot;
				}

				if (userItem.network)
				{
					return false;
				}

				return true;
			});
		}

		prepareItems(itemList)
		{
			return itemList.map((item) => {
				const chatTitle = ChatTitle.createFromDialogId(item.id);
				const chatAvatar = ChatAvatar.createFromDialogId(item.id);

				return {
					data: {
						id: item.id,
						title: chatTitle.getTitle(),
						subtitle: chatTitle.getDescription(),
						avatarUri: chatAvatar.getAvatarUrl(),
						avatarColor: item.color,
						avatar: chatAvatar.getListItemAvatarProps(),
					},
					type: 'chats',
					selected: false,
					disable: false,
					isWithPressed: true,
				};
			});
		}

		/**
		 * @param {immobileTabChatLoadResult} data
		 */
		handleUserGet(data)
		{
			if (data?.userData)
			{
				this.store.dispatch('usersModel/set', [data.userData]);
			}
		}
	}

	module.exports = { DialogCreator };
});
