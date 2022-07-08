/**
 * Bitrix OpenLines widget
 * Widget model (Vuex Builder model)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {VuexBuilderModel} from 'ui.vue.vuex';

import {Utils} from 'im.lib.utils';
import {Cookie} from "im.lib.cookie";
import {FormType, LocationStyle, LocationType, VoteType} from "./const";

export class WidgetModel extends VuexBuilderModel
{
	/**
	 * @inheritDoc
	 */
	getName()
	{
		return 'widget';
	}

	getState()
	{
		return {
			common:
			{
				configId: 0,
				configName: '',
				host: this.getVariable('common.host', location.protocol+'//'+location.host),
				pageMode: this.getVariable('common.pageMode', false),
				copyright: this.getVariable('common.copyright', true),
				copyrightUrl: this.getVariable('common.copyrightUrl', 'https://bitrix24.com'),
				location: this.getVariable('common.location', LocationType.bottomRight),
				styles: {
					backgroundColor: this.getVariable('styles.backgroundColor', '#17a3ea'),
					iconColor: this.getVariable('styles.iconColor', '#ffffff')
				},
				vote: {
					enable: false,
					beforeFinish: true,
					messageText: this.getVariable('vote.messageText', ''),
					messageLike: this.getVariable('vote.messageLike', ''),
					messageDislike: this.getVariable('vote.messageDislike', '')
				},
				textMessages: {
					bxLivechatOnlineLine1: this.getVariable('textMessages.bxLivechatOnlineLine1', ''),
					bxLivechatOnlineLine2: this.getVariable('textMessages.bxLivechatOnlineLine2', ''),
					bxLivechatOffline: this.getVariable('textMessages.bxLivechatOffline', ''),
					bxLivechatTitle: '',
				},
				online: false,
				operators: [],
				connectors: [],
				showForm: FormType.none,
				showed: false,
				reopen: false,
				dragged: false,
				textareaHeight: 0,
				widgetHeight: 0,
				widgetWidth: 0,
				showConsent: false,
				consentUrl: '',
				dialogStart: false,
				watchTyping: false,
				showSessionId: false,
				isCreateSessionMode: false,
				crmFormsSettings: {
					useWelcomeForm: false,
					welcomeFormId: 0,
					welcomeFormSec: '',
					welcomeFormDelay: false,
					welcomeFormFilled: false,
					successText: '',
					errorText: ''
				}
			},
			dialog:
			{
				sessionId: 0,
				sessionClose: true,
				sessionStatus: 0,
				userVote: VoteType.none,
				closeVote: false,
				userConsent: false,
				operatorChatId: 0,
				operator: {
					id: 0,
					name: '',
					firstName: '',
					lastName: '',
					workPosition: '',
					avatar: '',
					online: false,
				}
			},
			user:
			{
				id: -1,
				hash: '',
				name: '',
				firstName: '',
				lastName: '',
				avatar: '',
				email: '',
				phone: '',
				www: '',
				gender: 'M',
				position: '',
			},
		}
	}

	getStateSaveException()
	{
		return {
			common: {
				host: null,
				pageMode: null,
				copyright: null,
				copyrightUrl: null,
				styles: null,
				dragged: null,
				showed: null,
				showConsent: null,
				showForm: null,
			},
		}
	}

	getMutations()
	{
		return {

			common: (state, payload) =>
			{
				if (typeof payload.configId === 'number')
				{
					state.common.configId = payload.configId;
				}
				if (typeof payload.configName === 'string')
				{
					state.common.configName = payload.configName;
				}
				if (typeof payload.online === 'boolean')
				{
					state.common.online = payload.online;
				}
				if (Utils.types.isPlainObject(payload.vote))
				{
					if (typeof payload.vote.enable === 'boolean')
					{
						state.common.vote.enable = payload.vote.enable;
					}
					if (typeof payload.vote.beforeFinish === 'boolean')
					{
						state.common.vote.beforeFinish = payload.vote.beforeFinish;
					}
					if (typeof payload.vote.messageText === 'string')
					{
						state.common.vote.messageText = payload.vote.messageText;
					}
					if (typeof payload.vote.messageLike === 'string')
					{
						state.common.vote.messageLike = payload.vote.messageLike;
					}
					if (typeof payload.vote.messageDislike === 'string')
					{
						state.common.vote.messageDislike = payload.vote.messageDislike;
					}
				}
				if (Utils.types.isPlainObject(payload.textMessages))
				{
					if (typeof payload.textMessages.bxLivechatOnlineLine1 === 'string' && payload.textMessages.bxLivechatOnlineLine1 !== '')
					{
						state.common.textMessages.bxLivechatOnlineLine1 = payload.textMessages.bxLivechatOnlineLine1;
					}
					if (typeof payload.textMessages.bxLivechatOnlineLine2 === 'string' && payload.textMessages.bxLivechatOnlineLine2 !== '')
					{
						state.common.textMessages.bxLivechatOnlineLine2 = payload.textMessages.bxLivechatOnlineLine2;
					}
					if (typeof payload.textMessages.bxLivechatOffline === 'string' && payload.textMessages.bxLivechatOffline !== '')
					{
						state.common.textMessages.bxLivechatOffline = payload.textMessages.bxLivechatOffline;
					}
					if (typeof payload.textMessages.bxLivechatTitle === 'string' && payload.textMessages.bxLivechatTitle !== '')
					{
						state.common.textMessages.bxLivechatTitle = payload.textMessages.bxLivechatTitle;
					}
				}
				if (typeof payload.dragged === 'boolean')
				{
					state.common.dragged = payload.dragged;
				}
				if (typeof payload.textareaHeight === 'number')
				{
					state.common.textareaHeight = payload.textareaHeight;
				}
				if (typeof payload.widgetHeight === 'number')
				{
					state.common.widgetHeight = payload.widgetHeight;
				}
				if (typeof payload.widgetWidth === 'number')
				{
					state.common.widgetWidth = payload.widgetWidth;
				}
				if (typeof payload.showConsent === 'boolean')
				{
					state.common.showConsent = payload.showConsent;
				}
				if (typeof payload.consentUrl === 'string')
				{
					state.common.consentUrl = payload.consentUrl;
				}
				if (typeof payload.showed === 'boolean')
				{
					state.common.showed = payload.showed;
					payload.reopen = Utils.device.isMobile()? false: payload.showed;
				}
				if (typeof payload.reopen === 'boolean')
				{
					state.common.reopen = payload.reopen;
				}
				if (typeof payload.copyright === 'boolean')
				{
					state.common.copyright = payload.copyright;
				}
				if (typeof payload.dialogStart === 'boolean')
				{
					state.common.dialogStart = payload.dialogStart;
				}
				if (typeof payload.watchTyping === 'boolean')
				{
					state.common.watchTyping = payload.watchTyping;
				}
				if (typeof payload.showSessionId === 'boolean')
				{
					state.common.showSessionId = payload.showSessionId;
				}
				if (payload.operators instanceof Array)
				{
					state.common.operators = payload.operators;
				}
				if (payload.connectors instanceof Array)
				{
					state.common.connectors = payload.connectors;
				}
				if (typeof payload.showForm === 'string' && typeof FormType[payload.showForm] !== 'undefined')
				{
					if (payload.showForm === FormType.like && !!state.dialog.closeVote)
					{
						payload.showForm = FormType.none;
					}
					state.common.showForm = payload.showForm;
				}
				if (typeof payload.location === 'number' && typeof LocationStyle[payload.location] !== 'undefined')
				{
					if (state.common.location !== payload.location)
					{
						state.common.widgetHeight = 0;
						state.common.widgetWidth = 0;
						state.common.location = payload.location;
					}
				}

				if (Utils.types.isPlainObject(payload.crmFormsSettings))
				{
					if (typeof payload.crmFormsSettings.useWelcomeForm === 'string')
					{
						state.common.crmFormsSettings.useWelcomeForm = payload.crmFormsSettings.useWelcomeForm === 'Y';
					}
					if (typeof payload.crmFormsSettings.welcomeFormId === 'string')
					{
						state.common.crmFormsSettings.welcomeFormId = payload.crmFormsSettings.welcomeFormId;
					}
					if (typeof payload.crmFormsSettings.welcomeFormSec === 'string')
					{
						state.common.crmFormsSettings.welcomeFormSec = payload.crmFormsSettings.welcomeFormSec;
					}
					if (typeof payload.crmFormsSettings.welcomeFormDelay === 'string')
					{
						state.common.crmFormsSettings.welcomeFormDelay = payload.crmFormsSettings.welcomeFormDelay === 'Y';
					}
					if (typeof payload.crmFormsSettings.successText === 'string' && payload.crmFormsSettings.successText !== '')
					{
						state.common.crmFormsSettings.successText = payload.crmFormsSettings.successText;
					}
					if (typeof payload.crmFormsSettings.errorText === 'string' && payload.crmFormsSettings.errorText !== '')
					{
						state.common.crmFormsSettings.errorText = payload.crmFormsSettings.errorText;
					}
				}

				if (typeof payload.isCreateSessionMode === 'boolean')
				{
					state.common.isCreateSessionMode = payload.isCreateSessionMode;
				}

				if (this.isSaveNeeded({common: payload}))
				{
					this.saveState(state);
				}
			},
			dialog: (state, payload) =>
			{
				if (typeof payload.sessionId === 'number')
				{
					state.dialog.sessionId = payload.sessionId;
				}
				if (typeof payload.sessionClose === 'boolean')
				{
					state.dialog.sessionClose = payload.sessionClose;
				}
				if (typeof payload.sessionStatus === 'number')
				{
					state.dialog.sessionStatus = payload.sessionStatus;
				}
				if (typeof payload.userConsent === 'boolean')
				{
					state.dialog.userConsent = payload.userConsent;
				}
				if (typeof payload.userVote === 'string' && typeof payload.userVote !== 'undefined')
				{
					state.dialog.userVote = payload.userVote;
				}
				if (typeof payload.closeVote === 'boolean')
				{
					state.dialog.closeVote = payload.closeVote;

					if (
						!!payload.closeVote
						&& state.common.showForm === FormType.like
					)
					{
						state.common.showForm = FormType.none;
					}
				}
				if (typeof payload.operatorChatId === 'number')
				{
					state.dialog.operatorChatId = payload.operatorChatId;
				}
				if (Utils.types.isPlainObject(payload.operator))
				{
					if (typeof payload.operator.id === 'number')
					{
						state.dialog.operator.id = payload.operator.id;
					}
					if (typeof payload.operator.name === 'string' || typeof payload.operator.name === 'number')
					{
						state.dialog.operator.name = payload.operator.name.toString();
					}
					if (typeof payload.operator.lastName === 'string' || typeof payload.operator.lastName === 'number')
					{
						state.dialog.operator.lastName = payload.operator.lastName.toString();
					}
					if (typeof payload.operator.firstName === 'string' || typeof payload.operator.firstName === 'number')
					{
						state.dialog.operator.firstName = payload.operator.firstName.toString();
					}
					if (typeof payload.operator.workPosition === 'string' || typeof payload.operator.workPosition === 'number')
					{
						state.dialog.operator.workPosition = payload.operator.workPosition.toString();
					}
					if (typeof payload.operator.avatar === 'string')
					{
						if (!payload.operator.avatar || payload.operator.avatar.startsWith('http'))
						{
							state.dialog.operator.avatar = payload.operator.avatar;
						}
						else
						{
							state.dialog.operator.avatar = state.common.host+payload.operator.avatar;
						}
					}
					if (typeof payload.operator.online === 'boolean')
					{
						state.dialog.operator.online = payload.operator.online;
					}
				}
				if (this.isSaveNeeded({dialog: payload}))
				{
					this.saveState(state);
				}
			},
			user: (state, payload) =>
			{
				if (typeof payload.id === 'number')
				{
					state.user.id = payload.id;
				}
				if (typeof payload.hash === 'string' && payload.hash !== state.user.hash)
				{
					state.user.hash = payload.hash;
					Cookie.set(null, 'LIVECHAT_HASH', payload.hash, {expires: 365*86400, path: '/'});
				}
				if (typeof payload.name === 'string' || typeof payload.name === 'number')
				{
					state.user.name = payload.name.toString();
				}
				if (typeof payload.firstName === 'string' || typeof payload.firstName === 'number')
				{
					state.user.firstName = payload.firstName.toString();
				}
				if (typeof payload.lastName === 'string' || typeof payload.lastName === 'number')
				{
					state.user.lastName = payload.lastName.toString();
				}
				if (typeof payload.avatar === 'string')
				{
					state.user.avatar = payload.avatar;
				}
				if (typeof payload.email === 'string')
				{
					state.user.email = payload.email;
				}
				if (typeof payload.phone === 'string' || typeof payload.phone === 'number')
				{
					state.user.phone = payload.phone.toString();
				}
				if (typeof payload.www === 'string')
				{
					state.user.www = payload.www;
				}
				if (typeof payload.gender === 'string')
				{
					state.user.gender = payload.gender;
				}
				if (typeof payload.position === 'string')
				{
					state.user.position = payload.position;
				}

				if (this.isSaveNeeded({user: payload}))
				{
					this.saveState(state);
				}
			},
		}
	}

	getActions()
	{
		return {
			show: ({ commit }) =>
			{
				commit('common', {showed: true});
			},
			setVoteDateFinish: ({ commit, dispatch, state }, payload) =>
			{
				if (!payload)
				{
					clearTimeout(this.setVoteDateTimeout);
					commit('dialog', {closeVote: false});
					return true;
				}

				const totalDelay = new Date(payload).getTime() - new Date().getTime();
				const dayTimestamp = 10000;
				clearTimeout(this.setVoteDateTimeout);

				if (payload)
				{
					if (totalDelay && !state.dialog.closeVote)
					{
						commit('dialog', {closeVote: false});
					}

					var delay = totalDelay;

					if(totalDelay > dayTimestamp)
					{
						delay = dayTimestamp;
					}

					this.setVoteDateTimeout = setTimeout(function requestCloseVote() {
						delay = new Date(payload).getTime() - new Date().getTime();
						if(delay > 0)
						{
							if(delay > dayTimestamp)
							{
								delay = dayTimestamp;
							}
							setTimeout(requestCloseVote, delay);
						}
						else
						{
							commit('dialog', {closeVote: true});
						}
					}, delay);
				}
			}
		}
	}
}