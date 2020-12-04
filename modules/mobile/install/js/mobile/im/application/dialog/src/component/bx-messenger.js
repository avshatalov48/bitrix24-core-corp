/**
 * Bitrix im dialog mobile
 * Dialog vue component
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {Logger} from "im.lib.logger";
import {EventType, RestMethod} from "im.const";
import {Utils} from "im.lib.utils";
import "im.view.dialog";
import "im.view.quotepanel";

/**
 * @notice Do not mutate or clone this component! It is under development.
 */
Vue.component('bx-mobile-im-component-dialog',
{
	data: function()
	{
		return {
			dialogState: 'loading'
		};
	},
	computed:
	{
		EventType: () => EventType,
		localize()
		{
			return Object.assign({},
				Vue.getFilteredPhrases('MOBILE_CHAT_', this.$root.$bitrixMessages),
				Vue.getFilteredPhrases('IM_UTILS_', this.$root.$bitrixMessages),
			);
		},
		widgetClassName(state)
		{
			let className = ['bx-mobilechat-wrapper'];

			if (this.showMessageDialog)
			{
				className.push('bx-mobilechat-chat-start');
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

			if (!this.showMessageDialog || !this.dialog.quoteId)
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
				title: editId? this.localize.MOBILE_CHAT_EDIT_TITLE : (message.params.NAME ? message.params.NAME : (user ? user.name: '')),
				color: user? user.color: '',
				description: Utils.text.purify(message.text, message.params, files, this.localize)
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
		showMessageDialog()
		{
			let result = this.messageCollection && this.messageCollection.length > 0;
			let timeout = ChatPerformance.getDialogShowTimeout();
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
		...Vuex.mapState({
			application: state => state.application,
			dialog: state => state.dialogues.collection[state.application.dialog.dialogId],
			messageCollection: state => state.messages.collection[state.application.dialog.chatId]
		})
	},
	methods:
	{
		logEvent(name, ...params)
		{
			Logger.info(name, ...params);
		},
		onDialogRequestHistory(event)
		{
			this.$root.$bitrixApplication.getDialogHistory(event.lastId);
		},
		onDialogRequestUnread(event)
		{
			this.$root.$bitrixApplication.getDialogUnread(event.lastId);
		},
		onDialogMessageClickByUserName(event)
		{
			this.$root.$bitrixApplication.replyToUser(event.user.id, event.user);
		},
		onDialogMessageClickByUploadCancel(event)
		{
			this.$root.$bitrixApplication.cancelUploadFile(event.file.id);
		},
		onDialogMessageClickByCommand(event)
		{
			if (event.type === 'put')
			{
				this.$root.$bitrixApplication.insertText({text: event.value+' '});
			}
			else if (event.type === 'send')
			{
				this.$root.$bitrixApplication.addMessage(event.value);
			}
			else
			{
				Logger.warn('Unprocessed command', event);
			}
		},
		onDialogMessageClickByMention(event)
		{
			if (event.type === 'USER')
			{
				this.$root.$bitrixApplication.openProfile(event.value);
			}
			else if (event.type === 'CHAT')
			{
				this.$root.$bitrixApplication.openDialog(event.value);
			}
			else if (event.type === 'CALL')
			{
				this.$root.$bitrixApplication.openPhoneMenu(event.value);
			}
		},
		onDialogMessageMenuClick(event)
		{
			Logger.warn('Message menu:', event);
			this.$root.$bitrixApplication.openMessageMenu(event.message);
		},
		onDialogMessageRetryClick(event)
		{
			Logger.warn('Message retry:', event);
			this.$root.$bitrixApplication.retrySendMessage(event.message);
		},
		onDialogReadMessage(event)
		{
			this.$root.$bitrixApplication.readMessage(event.id);
		},
		onDialogReadedListClick(event)
		{
			this.$root.$bitrixApplication.openReadedList(event.list);
		},
		onDialogQuoteMessage(event)
		{
			this.$root.$bitrixApplication.quoteMessage(event.message.id);
		},
		onDialogMessageReactionSet(event)
		{
			this.$root.$bitrixApplication.reactMessage(event.message.id, event.reaction);
		},
		onDialogMessageReactionListOpen(event)
		{
			this.$root.$bitrixApplication.openMessageReactionList(event.message.id, event.values);
		},
		onDialogMessageClickByKeyboardButton(data)
		{
			if (data.action === 'ACTION')
			{
				let {dialogId, messageId, botId, action, value} = data.params;

				if (action === 'SEND')
				{
					this.$root.$bitrixApplication.addMessage(value);
					setTimeout(() => this.$root.$bitrixController.application.emit(EventType.dialog.scrollToBottom, {duration: 300, cancelIfScrollChange: false}), 300);
				}
				else if (action === 'PUT')
				{
					this.$root.$bitrixApplication.insertText({text: value+' '});
				}
				else if (action === 'CALL')
				{
					this.$root.$bitrixApplication.openPhoneMenu(value);
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
					this.$root.$bitrixApplication.openDialog(value);
				}

				return true;
			}

			if (data.action === 'COMMAND')
			{
				let {dialogId, messageId, botId, command, params} = data.params;

				this.$root.$bitrixController.restClient.callMethod(RestMethod.imMessageCommand, {
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
		onDialogMessageClickByChatTeaser(event)
		{
			this.$root.$bitrixApplication.execMessageOpenChatTeaser(event);
		},
		onDialogClick(event)
		{
			//this.$root.$bitrixApplication.controller.hideSmiles();
		},
		onQuotePanelClose()
		{
			this.$root.$bitrixApplication.quoteMessageClear();
		},
		onSmilesSelectSmile(event)
		{
			console.warn('Smile selected:', event);
			this.$root.$bitrixApplication.insertText({text: event.text});
		},
		onSmilesSelectSet()
		{
			console.warn('Set selected');
			this.$root.$bitrixApplication.setTextFocus();
		},
		onHideSmiles()
		{
			//this.$root.$bitrixApplication.controller.hideSmiles();
			this.$root.$bitrixApplication.setTextFocus();
		}

	},
	template: `
		<div :class="widgetClassName">
			<div :class="['bx-mobilechat-box', {'bx-mobilechat-box-dark-background': isDarkBackground}]">
				<template v-if="application.error.active">
					<bx-im-view-body-error/>
				</template>			
				<template v-else>
					<div :class="['bx-mobilechat-body', {'bx-mobilechat-body-with-message': dialogState == 'show'}]" key="with-message">
						<template v-if="dialogState == 'loading'">
							<bx-im-view-body-loading/>
						</template>
						<template v-else-if="dialogState == 'empty'">
							<bx-im-view-body-empty/>
						</template>
						<template v-else>
							<div class="bx-mobilechat-dialog">
								<bx-im-view-dialog
									:userId="application.common.userId" 
									:dialogId="application.dialog.dialogId"
									:chatId="application.dialog.chatId"
									:messageLimit="application.dialog.messageLimit"
									:messageExtraCount="application.dialog.messageExtraCount"
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
									:listenEventScrollToBottom="EventType.dialog.scrollToBottom"
									:listenEventRequestHistory="EventType.dialog.requestHistoryResult"
									:listenEventRequestUnread="EventType.dialog.requestUnreadResult"
									:listenEventSendReadMessages="EventType.dialog.sendReadMessages"
									@readMessage="onDialogReadMessage"
									@quoteMessage="onDialogQuoteMessage"
									@requestHistory="onDialogRequestHistory"
									@requestUnread="onDialogRequestUnread"
									@clickByCommand="onDialogMessageClickByCommand"
									@clickByMention="onDialogMessageClickByMention"
									@clickByUserName="onDialogMessageClickByUserName"
									@clickByMessageMenu="onDialogMessageMenuClick"
									@clickByMessageRetry="onDialogMessageRetryClick"
									@clickByUploadCancel="onDialogMessageClickByUploadCancel"
									@clickByReadedList="onDialogReadedListClick"
									@setMessageReaction="onDialogMessageReactionSet"
									@openMessageReactionList="onDialogMessageReactionListOpen"
									@clickByKeyboardButton="onDialogMessageClickByKeyboardButton"
									@clickByChatTeaser="onDialogMessageClickByChatTeaser"
									@click="onDialogClick"
								 />
							</div>
							<template v-if="application.options.showSmiles">
<!--								<bx-livechat-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet"/>	-->
								<bx-messenger-smiles @selectSmile="onSmilesSelectSmile" @selectSet="onSmilesSelectSet" @hideSmiles="onHideSmiles" />	
							</template>
							<bx-im-view-quote-panel :id="quotePanelData.id" :title="quotePanelData.title" :description="quotePanelData.description" :color="quotePanelData.color" @close="onQuotePanelClose"/>
						</template>
					</div>
				</template>
			</div>
		</div>
	`
});
