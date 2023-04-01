/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/controller/dialog/dialog
 */
jn.define('im/messenger/controller/dialog/dialog', (require, exports, module) => {

	const { Type } = require('type');
	const { Loc } = require('loc');
	const {
		EventType,
		FeatureFlag,
		DialogType,
	} = require('im/messenger/const');
	const {
		MessageService,
		DialogService,
		RecentService,
	} = require('im/messenger/service');
	const {
		ChatAvatar,
		ChatTitle,
	} = require('im/messenger/lib/element');

	const { Controller } = require('im/messenger/controller/base');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { PageNavigation } = require('im/messenger/lib/page-navigation');
	const { Logger } = require('im/messenger/lib/logger');
	const { Uuid } = require('utils/uuid');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEvent } = require('im/messenger/lib/event');
	const { Counters } = require('im/messenger/lib/counters');
	const { WebDialog } = require('im/messenger/controller/dialog/web');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { HeaderMenu } = require('im/messenger/controller/dialog/header/menu');

	/**
	 * @class Dialog
	 */
	class Dialog extends Controller
	{
		constructor(options = {})
		{
			super(options);

			this.pageNavigation = null;
			this.quoteMessage = null;
			this.view = null;

			this.onSubmit = this.sendMessage.bind(this);
			this.onLoadMore = this.loadNextPage.bind(this);
			this.onLike = this.like.bind(this);
			this.onReply = this.reply.bind(this);
			this.onCancelReply = this.cancelReply.bind(this);
			this.onViewableMessagesChanged = this.viewableMessagesChanged.bind(this);
			this.onScrollToNewMessages = this.scrollToNewMessages.bind(this);
			this.onClose = this.close.bind(this);

			this.onMessageAdd = this.drawPage.bind(this);
			this.onMessagePush = this.drawPushMessage.bind(this);
			this.onMessageUpdate = this.drawUpdateMessage.bind(this);
			this.onMessageLike = this.drawLike.bind(this);
		}

		subscribeViewEvents()
		{
			this.view
				.on(EventType.dialog.submit, this.onSubmit)
				.on(EventType.dialog.loadMore, this.onLoadMore)
				.on(EventType.dialog.like, this.onLike)
				.on(EventType.dialog.reply, this.onReply)
				.on(EventType.dialog.cancelReply, this.onCancelReply)
				.on(EventType.dialog.viewableMessagesChanged, this.onViewableMessagesChanged)
				.on(EventType.dialog.scrollToNewMessages, this.onScrollToNewMessages)
				.on(EventType.view.close, this.onClose)
			;
		}

		unsubscribeViewEvents()
		{
			this.view
				.off(EventType.dialog.submit, this.onSubmit)
				.off(EventType.dialog.loadMore, this.onLoadMore)
				.off(EventType.dialog.like, this.onLike)
				.off(EventType.dialog.reply, this.onReply)
				.off(EventType.dialog.cancelReply, this.onCancelReply)
				.off(EventType.dialog.viewableMessagesChanged, this.onViewableMessagesChanged)
				.off(EventType.dialog.scrollToNewMessages, this.onScrollToNewMessages)
				.off(EventType.view.close, this.onClose)
			;
		}

		subscribeStoreEvents()
		{
			MessengerStoreManager
				.on('messagesModel/add', this.onMessageAdd)
				.on('messagesModel/push', this.onMessagePush)
				.on('messagesModel/update', this.onMessageUpdate)
				.on('messagesModel/setLikes', this.onMessageLike)
			;
		}

		unsubscribeStoreEvents()
		{
			MessengerStoreManager
				.off('messagesModel/add', this.onMessageAdd)
				.off('messagesModel/push', this.onMessagePush)
				.off('messagesModel/update', this.onMessageUpdate)
				.off('messagesModel/setLikes', this.onMessageLike)
			;
		}

		open(options)
		{
			const {
				dialogId,
				dialogTitleParams,
			} = options;

			MessengerStore.dispatch('applicationModel/setDialogId', dialogId);

			this.readRecent(dialogId);

			MessengerStore.dispatch('recentModel/like', {
				id: dialogId,
				liked: false,
			});

			const chatSettings = Application.storage.getObject('settings.chat', {
				nativeDialogEnable: false,
			});
			const isOpenlinesChat = dialogTitleParams && dialogTitleParams.chatType === 'lines';
			if (
				!FeatureFlag.dialog.nativeSupported
				|| !chatSettings.nativeDialogEnable
				|| isOpenlinesChat
			)
			{
				this.openWebDialog(options);

				return;
			}

			this.pageNavigation = new PageNavigation({
				itemsPerPage: 50,
			});

			let titleParams = null;
			if (dialogTitleParams)
			{
				titleParams = {
					text: dialogTitleParams.name,
					detailText: dialogTitleParams.description,
					imageUrl: dialogTitleParams.avatar,
					useLetterImage: true,
				};

				if (!dialogTitleParams.imageUrl || dialogTitleParams.imageUrl === '')
				{
					titleParams.imageColor = dialogTitleParams.color;
				}
			}

			this.createView(titleParams);
		}

		openLine(options)
		{
			this.openWebDialog(options);
		}

		getDialogId()
		{
			return MessengerStore.getters['applicationModel/getDialogId'];
		}

		createView(titleParams = null)
		{
			if (!titleParams)
			{
				titleParams = this.getTitleParams();
			}

			PageManager.openWidget(
				'chat.dialog',
				{
					onReady: view => this.onViewReady(view),
					onError: error => Logger.error(error),
					titleParams,
				},
			);
		}

		onViewReady(view)
		{
			this.view = view;

			this.drawHeaderButtons();

			this.view.setInputPlaceholder(Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT'));

			this.subscribeViewEvents();
			this.subscribeStoreEvents();

			this.loadNextPage();
		}

		getTitleParams()
		{
			const dialogId = this.getDialogId();
			const avatar = ChatAvatar.createFromDialogId(dialogId);
			const title = ChatTitle.createFromDialogId(dialogId);

			return {
				...avatar.getTitleParams(),
				...title.getTitleParams(),
				callback: '1',
			};
		}

		drawHeaderButtons()
		{
			const isDialogWithUser = !DialogHelper.isDialogId(this.getDialogId());
			if (isDialogWithUser)
			{
				this.drawUserHeaderButtons();
				return;
			}

			this.drawDialogHeaderButtons();
		}

		drawUserHeaderButtons()
		{
			const dialogId = this.getDialogId();
			const userData = MessengerStore.getters['usersModel/getUserById'](dialogId);
			if (!userData)
			{
				return;
			}

			if (
				!userData
				|| userData.bot
				|| userData.network
				|| MessengerParams.getUserId() === Number(dialogId)
			)
			{
				return;
			}

			this.view.setRightButtons([
				{
					type: 'call_audio',
					callback: this.createAudioCall.bind(this),
				},
				{
					type: 'call_video',
					badgeCode: 'call_video',
					callback: this.createVideoCall.bind(this),
				},
			]);
		}

		drawDialogHeaderButtons()
		{
			const dialogId = this.getDialogId();
			const dialogData = MessengerStore.getters['dialoguesModel/getById'](dialogId);
			if (!dialogData)
			{
				return;
			}

			if (!dialogData.restrictions.call)
			{
				return;
			}

			const maxParticipants = 24;
			if (
				dialogData.userCounter > maxParticipants
				|| dialogData.entityType === 'VIDEOCONF' && dialogData.entityData1 === 'BROADCAST'
			)
			{
				if (dialogData.type === DialogType.call)
				{
					return;
				}

				if (!dialogData.restrictions.extend)
				{
					return;
				}

				this.view.setRightButtons([{
					type: 'user_plus',
					callback: () => {},
				}]);

				return;
			}

			this.view.setRightButtons([
				{
					type: 'call_audio',
					callback: this.createAudioCall.bind(this),
				},
				{
					type: 'call_video',
					badgeCode: 'call_video',
					callback: this.createVideoCall.bind(this),
				},
			]);
		}

		close()
		{
			MessengerStore.dispatch('applicationModel/setDialogId', 0)
				.then(() => {
					this.unsubscribeStoreEvents();
					this.unsubscribeViewEvents();

					this.quoteMessage = null;

					this.view.back();
				})
			;
		}

		viewableMessagesChanged(indexList = [], messageList = [])
		{
			if (indexList.includes(1))
			{
				this.view.hideScrollToNewMessagesButton();
				return;
			}

			this.view.showScrollToNewMessagesButton();
		}

		scrollToNewMessages()
		{
			//TODO: scroll to last unread message or to last message

			const withAnimation = true;

			this.view.scrollToMessageByIndex(0, withAnimation);
		}

		sendMessage(text)
		{
			if (text === '')
			{
				return;
			}

			this.view.clearInput();

			if (this.quoteMessage)
			{
				text = DialogConverter.toQuote(this.quoteMessage, text);
				this.cancelReply();
			}

			const uuid = Uuid.getV4();

			const message = {
				authorId: MessengerParams.getUserId(),
				dialogId: this.getDialogId(),
				text,
				unread: false,
				templateId: uuid,
			};

			this.pushMessage({
				dialogId: this.getDialogId(),
				message,
			}).then(() => {
				//TODO: chatbackground::task::add

				// BX.postComponentEvent('chatbackground::task::add', [
				// 	'sendMessage|' + uuid,
				// 	[
				// 		RestMethod.imMessageAdd,
				// 		{
				// 			'TEMPLATE_ID': uuid,
				// 			'DIALOG_ID': this.getDialogId(,
				// 			'MESSAGE': message.text,
				// 		}
				// 	],
				// 	message
				// ], 'background');

				MessageService.send({
					dialogId: this.getDialogId(),
					text,
					messageType: 'self',
					templateId: uuid,
				}).then((response) => {
					this.pushMessage({
						dialogId: this.getDialogId(),
						message: {
							id: response.data(),
							...message,
						},
					});
				});
			});
		}

		like(index, message, like)
		{
			// const messageId = message.id;
			//
			// MessageService.like({ messageId });
			//
			// MessengerStore.dispatch('messagesModel/setLikes', {
			// 	dialogId: this.getDialogId(),
			// 	messageId,
			// 	//likeList
			// });
		}

		reply(index, message)
		{
			this.quoteMessage = message;
			this.view.setInputQuote(this.quoteMessage);
		}

		cancelReply()
		{
			this.quoteMessage = null;
			this.view.removeInputQuote();
		}

		loadNextPage()
		{
			if (!this.pageNavigation.hasNextPage || this.pageNavigation.isPageLoading)
			{
				return;
			}

			this.pageNavigation.turnPage();
			this.pageNavigation.isPageLoading = true;

			if (this.pageNavigation.currentPage === 1)
			{
				const firstPage =
					MessengerStore.getters['messagesModel/getDialogPage'](
						this.getDialogId(),
						1,
						this.pageNavigation.itemsPerPage
					)
				;

				if (firstPage.length > 0)
				{
					this.drawPage();
					this.view.setCanLoadMore(true);
				}
			}

			this.getPageFromService().then((response) => {
				const messages = response.data().messages;

				const messagesPayload = {
					dialogId: this.getDialogId(),
					messages,
				};

				const usersPayload = response.data().users;

				if (messages.length === 0)
				{
					this.pageNavigation.hasNextPage = false;
					this.pageNavigation.isPageLoading = false;

					this.view.setCanLoadMore(false);

					return;
				}

				this.saveUsers(usersPayload)
					.then(() => {
						this.saveMessages(messagesPayload);
					})
				;

				this.pageNavigation.isPageLoading = false;

				if (messages.length < this.pageNavigation.itemsPerPage)
				{
					this.pageNavigation.hasNextPage = false;
					this.view.setCanLoadMore(false);
				}
			});
		}

		getPageFromService()
		{
			const options = {
				dialogId: this.getDialogId(),
				limit: this.pageNavigation.itemsPerPage,
			};

			if (this.pageNavigation.currentPage > 1)
			{
				options.toMessageId = MessengerStore.getters['messagesModel/getLastMessageIdByPage'](
					this.getDialogId(),
					this.pageNavigation.currentPage,
					this.pageNavigation.itemsPerPage,
				);
			}

			return DialogService.getMessageList(options);
		}

		drawPage()
		{
			let messages = MessengerStore.getters['messagesModel/getDialogPage'](
				this.getDialogId(),
				this.pageNavigation.currentPage,
				this.pageNavigation.itemsPerPage
			);

			messages.map((message) => {
				const user = MessengerStore.getters['usersModel/getUserById'](message.authorId);

				message.author_name = (user && user.name) ? user.name : '';

				return message;
			});

			messages = DialogConverter.toMessageList(messages);

			if (this.pageNavigation.currentPage === 1)
			{
				this.view.setMessages(messages);
				return;
			}

			this.view.pushMessages(messages);
		}

		drawPushMessage(mutation, state)
		{
			Logger.log('drawPushMessage', mutation, state);

			const pushMessage = mutation.payload.message;

			if (this.getDialogId() !== String(pushMessage.dialogId))
			{
				return;
			}

			const message = DialogConverter.toMessageItem(pushMessage);

			this.view.addMessage(message);
			this.view.scrollToNewMessage();
		}

		drawUpdateMessage(mutation, state)
		{
			Logger.log('drawPushMessage', mutation, state);

			const pushMessage = mutation.payload.message;

			if (this.getDialogId() !== String(pushMessage.dialogId))
			{
				return;
			}

			const message = DialogConverter.toMessageItem(pushMessage);

			this.view.updateMessageById(pushMessage.id, message);
		}

		drawLike(mutation, state)
		{
			// this.view.updateMessageByIndex(
			// 	mutation.payload.index,
			// 	DialogConverter.toMessageItem(this.memory.getMessageByIndex(mutation.payload.index))
			// );
		}

		saveMessages(messages)
		{
			return MessengerStore.dispatch('messagesModel/add', messages);
		}

		pushMessage(message)
		{
			return MessengerStore.dispatch('messagesModel/push', message);
		}

		saveUsers(users)
		{
			return MessengerStore.dispatch('usersModel/set', users);
		}

		deleteCurrentDialog()
		{
			const dialogId = MessengerStore.getters['applicationModel/getDialogId'];

			MessengerStore.dispatch('recentModel/delete', { id: dialogId })
				.then(() => Counters.update())
			;

			MessengerStore.dispatch('dialoguesModel/delete', { id: dialogId });
			MessengerStore.dispatch('usersModel/delete', { id: dialogId });
		}

		openWebDialog(options)
		{
			return new Promise(resolve => {
				if (Type.isStringFilled(options.userCode))
				{
					WebDialog.getOpenlineDialogByUserCode(options.userCode).then(dialog => {
						options.dialogId = dialog.dialog_id;
						WebDialog.open(options);
					});

					return;
				}

				WebDialog.open(options);
				resolve();
			});
		}

		static getOpenDialogParams(options = {})
		{
			const {
				dialogId,
				dialogTitleParams,
			} = options;

			return WebDialog.getOpenDialogParams(dialogId, dialogTitleParams);
		}

		static getOpenLineParams(options = {})
		{
			const {
				userCode,
				dialogTitleParams
			} = options;

			return WebDialog.getOpenLineParams(userCode, dialogTitleParams);
		}

		createAudioCall()
		{
			Calls.createAudioCall(this.getDialogId());
		}

		createVideoCall()
		{
			Calls.createVideoCall(this.getDialogId());
		}

		readRecent(dialogId)
		{
			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			MessengerStore.dispatch('recentModel/set', [{
				id: dialogId,
				unread: false,
			}]).then(() => {
				new MessengerEvent(EventType.messenger.renderRecent).send();

				Counters.update();
			});

			RecentService.read({
				dialogId,
			})
				.catch((result) =>
				{
					Logger.error('Recent item read error: ', result.error());

					MessengerStore.dispatch('recentModel/set', [recentItem]).then(() => {
						new MessengerEvent(EventType.messenger.renderRecent).send();

						Counters.update();
					});
				})
			;
		}
	}

	module.exports = { Dialog };
});
