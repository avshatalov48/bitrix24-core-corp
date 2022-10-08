/**
 * Bitrix im dialog mobile
 * Dialog vue component
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {Logger} from "im.lib.logger";
import { DialogState, EventType, RestMethod } from "im.const";
import {Utils} from "im.lib.utils";
import "im.component.dialog";
import "im.view.quotepanel";

import {EventEmitter} from "main.core.events";

import {LoadingStatus} from './loading-status';
import {ErrorStatus} from './error-status';
import {EmptyStatus} from './empty-status';
import {MobileSmiles} from './mobile-smiles';

import { MobileQuoteHandler } from './event-handler/mobile-quote-handler';
import { MobileReactionHandler } from './event-handler/mobile-reaction-handler';
import { MobileReadingHandler } from './event-handler/mobile-reading-handler';
import { Timer } from 'im.lib.timer';

/**
 * @notice Do not clone this component! It is under development.
 */
BitrixVue.component('bx-mobile-im-component-dialog',
{
	components: {LoadingStatus, ErrorStatus, EmptyStatus, MobileSmiles},
	data: function()
	{
		return {
			dialogState: 'none'
		};
	},
	computed:
	{
		EventType: () => EventType,
		localize()
		{
			return BitrixVue.getFilteredPhrases(['MOBILE_CHAT_', 'IM_UTILS_'], this.$root.$bitrixMessages);
		},
		widgetClassName()
		{
			let className = [];

			className.push('bx-mobile');

			if (Utils.platform.isIos())
			{
				className.push('bx-mobile-ios');
			}
			else
			{
				className.push('bx-mobile-android');
			}

			return className.join(' ');
		},
		quotePanelData()
		{
			let result = {
				id: 0,
				title: '',
				description: '',
				color: ''
			};

			if (!this.isMessageLoaded || !this.dialog.quoteId)
			{
				return result;
			}

			let message = this.$store.getters['messages/getMessage'](this.dialog.chatId, this.dialog.quoteId);
			if (!message)
			{
				return result;
			}

			let user = this.$store.getters['users/get'](message.authorId);
			let files = this.$store.getters['files/getList'](this.dialog.chatId);
			let editId = this.$store.getters['dialogues/getEditId'](this.dialog.dialogId);

			return {
				id: this.dialog.quoteId,
				title: editId? this.$Bitrix.Loc.getMessage('MOBILE_CHAT_EDIT_TITLE') : (message.params.NAME ? message.params.NAME : (user ? user.name: '')),
				color: user? user.color: '',
				description: Utils.text.purify(message.text, message.params, files, this.$Bitrix.Loc.getMessages())
			};
		},

		isDialog()
		{
			return Utils.dialog.isChatId(this.dialog.dialogId);
		},

		isGestureQuoteSupported()
		{
			if (this.dialog && this.dialog.type === 'announcement' && !this.dialog.managerList.includes(this.application.common.userId))
			{
				return false;
			}

			return ChatPerformance.isGestureQuoteSupported();
		},
		isDarkBackground()
		{
			return this.application.options.darkBackground;
		},
		isMessageLoaded()
		{
			let timeout = ChatPerformance.getDialogShowTimeout();

			let result = this.messageCollection && this.messageCollection.length > 0;
			if (result)
			{
				if (timeout > 0)
				{
					clearTimeout(this.dialogStateTimeout);
					this.dialogStateTimeout = setTimeout(() => {
						this.dialogState = 'show';
					}, timeout);
				}
				else
				{
					this.dialogState = 'show';
				}
			}
			else if (this.dialog && this.dialog.init)
			{
				if (timeout > 0)
				{
					clearTimeout(this.dialogStateTimeout);
					this.dialogStateTimeout = setTimeout(() => {
						this.dialogState = 'empty';
					}, timeout);
				}
				else
				{
					this.dialogState = 'empty';
				}
			}
			else
			{
				this.dialogState = 'loading';
			}

			return result;
		},

		dialog()
		{
			const dialog = this.$store.getters['dialogues/get'](this.application.dialog.dialogId);

			return dialog || this.$store.getters['dialogues/getBlank']();
		},
		chatId()
		{
			if (this.application)
			{
				return this.application.dialog.chatId;
			}
		},
		diskFolderId()
		{
			return this.application.dialog.diskFolderId;
		},

		isDialogShowingMessages()
		{
			const messagesNotEmpty = this.messageCollection && this.messageCollection.length > 0;
			if (messagesNotEmpty)
			{
				this.dialogState = DialogState.show;
			}
			else if (this.dialog && this.dialog.init)
			{
				this.dialogState = DialogState.empty;
			}
			else
			{
				this.dialogState = DialogState.loading;
			}

			return messagesNotEmpty;
		},
		...Vuex.mapState({
			application: state => state.application,
			messageCollection: state => state.messages.collection[state.application.dialog.chatId]
		}),
	},
	created()
	{
		this.timer = new Timer();

		this.initEventHandlers();
		this.subscribeToEvents();
	},
	beforeDestroy()
	{
		this.unsubscribeEvents();
		this.destroyHandlers();
	},
	methods:
	{
		initEventHandlers()
		{
			this.quoteHandler = new MobileQuoteHandler(this.$Bitrix);
			this.reactionHandler = new MobileReactionHandler(this.$Bitrix);
			this.readingHandler = new MobileReadingHandler(this.$Bitrix);
		},
		destroyHandlers()
		{
			this.quoteHandler.destroy();
			this.reactionHandler.destroy();
			this.readingHandler.destroy();
		},
		subscribeToEvents()
		{
			EventEmitter.subscribe(EventType.mobile.textarea.setText, this.onSetText);
			EventEmitter.subscribe(EventType.mobile.textarea.setFocus, this.onSetFocus);
			EventEmitter.subscribe(EventType.mobile.openUserList, this.onOpenUserList);
			EventEmitter.subscribe(EventType.dialog.clickOnUploadCancel, this.onClickOnUploadCancel);
			EventEmitter.subscribe(EventType.dialog.clickOnMessageRetry, this.onClickOnMessageRetry);
			EventEmitter.subscribe(EventType.dialog.clickOnDialog, this.onClickOnDialog);
			EventEmitter.subscribe(EventType.dialog.clickOnChatTeaser, this.onClickOnChatTeaser);
			EventEmitter.subscribe(EventType.dialog.clickOnKeyboardButton, this.onClickOnKeyboardButton);
			EventEmitter.subscribe(EventType.dialog.clickOnReadList, this.onClickOnReadList);
			EventEmitter.subscribe(EventType.dialog.clickOnMessageMenu, this.onClickOnMessageMenu);
			EventEmitter.subscribe(EventType.dialog.doubleClickOnMessage, this.onDoubleClickOnMessage);
			EventEmitter.subscribe(EventType.dialog.clickOnUserName, this.onClickOnUserName);
			EventEmitter.subscribe(EventType.dialog.clickOnMention, this.onClickOnMention);
			EventEmitter.subscribe(EventType.dialog.clickOnCommand, this.onClickOnCommand);
		},
		unsubscribeEvents()
		{
			EventEmitter.unsubscribe(EventType.mobile.textarea.setText, this.onSetText);
			EventEmitter.unsubscribe(EventType.mobile.textarea.setFocus, this.onSetFocus);
			EventEmitter.unsubscribe(EventType.mobile.openUserList, this.onOpenUserList);
			EventEmitter.unsubscribe(EventType.dialog.clickOnUploadCancel, this.onClickOnUploadCancel);
			EventEmitter.unsubscribe(EventType.dialog.clickOnMessageRetry, this.onClickOnMessageRetry);
			EventEmitter.unsubscribe(EventType.dialog.clickOnDialog, this.onClickOnDialog);
			EventEmitter.unsubscribe(EventType.dialog.clickOnChatTeaser, this.onClickOnChatTeaser);
			EventEmitter.unsubscribe(EventType.dialog.clickOnKeyboardButton, this.onClickOnKeyboardButton);
			EventEmitter.unsubscribe(EventType.dialog.clickOnReadList, this.onClickOnReadList);
			EventEmitter.unsubscribe(EventType.dialog.clickOnMessageMenu, this.onClickOnMessageMenu);
			EventEmitter.unsubscribe(EventType.dialog.doubleClickOnMessage, this.onDoubleClickOnMessage);
			EventEmitter.unsubscribe(EventType.dialog.clickOnUserName, this.onClickOnUserName);
			EventEmitter.unsubscribe(EventType.dialog.clickOnMention, this.onClickOnMention);
			EventEmitter.unsubscribe(EventType.dialog.clickOnCommand, this.onClickOnCommand);
		},
		getApplication()
		{
			return this.$Bitrix.Application.get();
		},
		logEvent(name, ...params)
		{
			Logger.info(name, ...params);
		},
		onDialogRequestHistory(event)
		{
			this.getApplication().getDialogHistory(event.lastId);
		},
		onDialogRequestUnread(event)
		{
			this.getApplication().getDialogUnread(event.lastId);
		},
		onClickOnUserName({data: event})
		{
			this.getApplication().replyToUser(event.user.id, event.user);
		},
		onClickOnUploadCancel({data: event})
		{
			this.getApplication().cancelUploadFile(event.file.id);
		},
		onClickOnCommand({data: event})
		{
			if (event.type === 'put')
			{
				this.getApplication().insertText({text: event.value+' '});
			}
			else if (event.type === 'send')
			{
				this.getApplication().addMessage(event.value);
			}
			else
			{
				Logger.warn('Unprocessed command', event);
			}
		},
		onClickOnMention({data: event})
		{
			if (event.type === 'USER')
			{
				this.getApplication().openDialog(event.value);
			}
			else if (event.type === 'CHAT')
			{
				this.getApplication().openDialog(event.value);
			}
			else if (event.type === 'CALL')
			{
				this.getApplication().openPhoneMenu(event.value);
			}
		},
		onClickOnMessageMenu({data: event})
		{
			Logger.warn('Message menu:', event);
			this.getApplication().openMessageMenu(event.message);
		},
		onClickOnMessageRetry({data: event})
		{
			Logger.warn('Message retry:', event);
			this.getApplication().retrySendMessage(event.message);
		},
		onDoubleClickOnMessage({data: event})
		{
			Logger.warn('Message double click:', event);
			EventEmitter.emit('ui:reaction:press', {id: 'message'+event.message.id})
		},
		onClickOnReadList({data: event})
		{
			this.getApplication().openReadedList(event.list);
		},
		onSetFocus()
		{
			this.getApplication().setTextFocus();
		},
		onSetText({data: event})
		{
			this.getApplication().setText(event.text);
		},
		onClickOnKeyboardButton({data: event})
		{
			if (event.action === 'ACTION')
			{
				let {dialogId, messageId, botId, action, value} = event.params;

				if (action === 'SEND')
				{
					this.getApplication().addMessage(value);
					setTimeout(() => EventEmitter.emit(EventType.dialog.scrollToBottom, {chatId: this.chatId, duration: 300, cancelIfScrollChange: false}), 300);
				}
				else if (action === 'PUT')
				{
					this.getApplication().insertText({text: value+' '});
				}
				else if (action === 'CALL')
				{
					this.getApplication().openPhoneMenu(value);
				}
				else if (action === 'COPY')
				{
					app.exec("copyToClipboard", {text: value});

					(new BXMobileApp.UI.NotificationBar({
						message: BX.message("MOBILE_MESSAGE_MENU_COPY_SUCCESS"),
						color: "#af000000",
						textColor: "#ffffff",
						groupId: "clipboard",
						maxLines: 1,
						align: "center",
						isGlobal: true,
						useCloseButton: true,
						autoHideTimeout: 1500,
						hideOnTap: true
					}, "copy")).show();

				}
				else if (action === 'DIALOG')
				{
					this.getApplication().openDialog(value);
				}

				return true;
			}

			if (event.action === 'COMMAND')
			{
				let {dialogId, messageId, botId, command, params} = event.params;

				this.$Bitrix.RestClient.get().callMethod(RestMethod.imMessageCommand, {
					'MESSAGE_ID': messageId,
					'DIALOG_ID': dialogId,
					'BOT_ID': botId,
					'COMMAND': command,
					'COMMAND_PARAMS': params,
				});

				return true;
			}

			return false;
		},
		onClickOnChatTeaser({data: event})
		{
			this.$Bitrix.Data.get('controller').application.joinParentChat(event.message.id, 'chat'+event.message.params.CHAT_ID).then((dialogId) => {
				this.getApplication().openDialog(dialogId);
			}).catch(() => {});
		},
		onClickOnDialog({data: event})
		{
			//this.getApplication().controller.hideSmiles();
		},
		onSmilesSelectSmile(event)
		{
			console.warn('Smile selected:', event);
			this.getApplication().insertText({text: event.text});
		},
		onSmilesSelectSet()
		{
			console.warn('Set selected');
			this.getApplication().setTextFocus();
		},
		onHideSmiles()
		{
			//this.getApplication().controller.hideSmiles();
			this.getApplication().setTextFocus();
		},
		onOpenUserList({data: event})
		{
			this.getApplication().openUserList(event);
		},
		getController()
		{
			return this.$Bitrix.Data.get('controller');
		},
		getApplicationController()
		{
			return this.getController().application;
		},
		getRestClient()
		{
			return this.$Bitrix.RestClient.get();
		},
		getCurrentUser()
		{
			return this.$store.getters['users/get'](this.application.common.userId, true);
		},
		executeRestAnswer(method, queryResult, extra)
		{
			this.getController().executeRestAnswer(method, queryResult, extra);
		},
		isUnreadMessagesLoaded()
		{
			if (!this.dialog)
			{
				return true;
			}

			if (this.dialog.lastMessageId <= 0)
			{
				return true;
			}

			if (!this.messageCollection || this.messageCollection.length <= 0)
			{
				return true;
			}

			let lastElementId = 0;
			for (let index = this.messageCollection.length-1; index >= 0; index--)
			{
				const lastElement = this.messageCollection[index];
				if (typeof lastElement.id === "number")
				{
					lastElementId = lastElement.id;
					break;
				}
			}

			return lastElementId >= this.dialog.lastMessageId;
		},
	},
	watch:
	{
		dialogState(state)
		{
			this.getApplication().changeDialogState(state);
		}
	},
	// language=Vue
	template: `
		<div :class="widgetClassName" :data-message-loaded="isMessageLoaded">
			<bx-im-component-dialog
				:userId="application.common.userId"
				:dialogId="application.dialog.dialogId"
				:enableReadMessages="application.dialog.enableReadMessages"
				:enableReactions="true"
				:enableDateActions="false"
				:enableCreateContent="false"
				:enableGestureQuote="application.options.quoteEnable"
				:enableGestureQuoteFromRight="application.options.quoteFromRight"
				:enableGestureMenu="true"
				:showMessageUserName="isDialog"
				:showMessageAvatar="isDialog"
				:showMessageMenu="false"
				:skipDataRequest="true"
			 />
			<template v-if="application.options.showSmiles">
				<MobileSmiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet" @hideSmiles="onHideSmiles" />
			</template>
		</div>
	`
}, {immutable: true});
