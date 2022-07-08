/**
 * Bitrix OpenLines widget
 * Widget Rest answers (Rest Answer Handler)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BaseRestHandler} from "im.provider.rest";
import {LocalStorage} from "im.lib.localstorage";
import {SubscriptionType} from "./const";

class WidgetRestAnswerHandler extends BaseRestHandler
{
	constructor(props = {})
	{
		super(props);

		this.widget = props.widget;
	}

	handleImopenlinesWidgetConfigGetSuccess(data)
	{
		this.store.commit('widget/common', {
			configId: data.configId,
			configName: data.configName,
			vote: data.vote,
			textMessages: data.textMessages,
			operators: data.operators || [],
			online: data.online,
			consentUrl: data.consentUrl,
			connectors: data.connectors || [],
			watchTyping: data.watchTyping,
			showSessionId: data.showSessionId,
			crmFormsSettings: data.crmFormsSettings
		});

		this.store.commit('application/set', {disk: data.disk});

		this.widget.addLocalize(data.serverVariables);
		LocalStorage.set(this.widget.getSiteId(), 0, 'serverVariables', data.serverVariables || {});
	}

	handleImopenlinesWidgetUserRegisterSuccess(data)
	{
		this.widget.restClient.setAuthId(data.hash);

		let previousData = [];
		if (typeof this.store.state.messages.collection[this.controller.application.getChatId()] !== 'undefined')
		{
			previousData = this.store.state.messages.collection[this.controller.application.getChatId()];
		}
		this.store.commit('messages/initCollection', {chatId: data.chatId, messages: previousData});

		this.store.commit('dialogues/initCollection', {dialogId: data.dialogId, fields: {
			entityType: 'LIVECHAT',
			type: 'livechat'
		}});

		this.store.commit('application/set', {dialog: {
			chatId: data.chatId,
			dialogId: 'chat'+data.chatId
		}});
	}

	handleImopenlinesWidgetChatCreateSuccess(data)
	{
		this.widget.restClient.setAuthId(data.hash);

		this.store.commit('messages/initCollection', {chatId: data.chatId, messages: []});
		this.store.commit('dialogues/initCollection', {
			dialogId: data.dialogId, fields: {
				entityType: 'LIVECHAT',
				type: 'livechat'
			}
		});

		this.store.commit('application/set', {
			dialog: {
				chatId: data.chatId,
				dialogId: 'chat' + data.chatId
			}
		});
	}

	handleImopenlinesWidgetUserGetSuccess(data)
	{
		this.store.commit('widget/user', {
			id: data.id,
			hash: data.hash,
			name: data.name,
			firstName: data.firstName,
			lastName: data.lastName,
			phone: data.phone,
			avatar: data.avatar,
			email: data.email,
			www: data.www,
			gender: data.gender,
			position: data.position,
		});
		this.store.dispatch('users/set', [{
			id: data.id,
			name: data.name,
			firstName: data.firstName,
			lastName: data.lastName,
			avatar: data.avatar,
			gender: data.gender,
			workPosition: data.position,
		}]);
		this.store.commit('application/set', {common: {
			userId: data.id
		}});
	}

	handleImopenlinesWidgetDialogGetSuccess(data)
	{
		this.store.commit('messages/initCollection', {chatId: data.chatId});

		this.store.commit('widget/dialog', data);

		this.store.commit('application/set', {dialog: {
			chatId: data.chatId,
			dialogId: 'chat'+data.chatId,
			diskFolderId: data.diskFolderId,
		}});

		this.store.dispatch('widget/setVoteDateFinish', data.dateCloseVote);
	}

	handleImDialogMessagesGetInitSuccess(data)
	{
		this.handleImDialogMessagesGetSuccess(data);
	}

	handleImDialogMessagesGetSuccess(data)
	{
		if (data.messages && data.messages.length > 0 && !this.widget.isDialogStart())
		{
			this.store.commit('widget/common', {
				dialogStart: true
			});
			this.store.commit('widget/dialog', {
				userConsent: true
			});
		}
	}

	handleImMessageAddSuccess(messageId, message)
	{
		this.widget.sendEvent({
			type: SubscriptionType.userMessage,
			data: {
				id: messageId,
				text: message.text
			}
		});
	}

	handleImDiskFileCommitSuccess(result, message)
	{
		this.widget.sendEvent({
			type: SubscriptionType.userFile,
			data: {}
		});
	}
}

export {WidgetRestAnswerHandler};