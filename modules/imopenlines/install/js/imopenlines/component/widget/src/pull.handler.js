/**
 * Bitrix OpenLines widget
 * Widget pull commands (Pull Command Handler)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {SubscriptionType, VoteType} from "./const";

class WidgetImPullCommandHandler
{
	static create(params = {})
	{
		return new this(params);
	}

	getModuleId()
	{
		return 'im';
	}

	constructor(params)
	{
		this.controller = params.controller;
		this.store = params.store;
		this.widget = params.widget;
	}

	handleMessageChat(params, extra, command)
	{
		if (params.message.senderId != this.controller.application.getUserId())
		{
			this.widget.sendEvent({
				type: SubscriptionType.operatorMessage,
				data: params
			});

			if (!this.store.state.widget.common.showed && !this.widget.onceShowed)
			{
				this.widget.onceShowed = true;
				this.widget.open();
			}
		}
	}
}

class WidgetImopenlinesPullCommandHandler
{
	static create(params = {})
	{
		return new this(params);
	}

	constructor(params = {})
	{
		this.controller = params.controller;
		this.store = params.store;
		this.widget = params.widget;
	}

	getModuleId()
	{
		return 'imopenlines';
	}

	handleSessionStart(params, extra, command)
	{
		this.store.commit('widget/dialog', {
			sessionId: params.sessionId,
			sessionClose: false,
			sessionStatus: 0,
			userVote: VoteType.none,
		});

		this.store.dispatch('widget/setVoteDateFinish', '');

		this.widget.sendEvent({
			type: SubscriptionType.sessionStart,
			data: {
				sessionId: params.sessionId
			}
		});
	}

	handleSessionOperatorChange(params, extra, command)
	{
		this.store.commit('widget/dialog', {
			operator: params.operator,
			operatorChatId: params.operatorChatId
		});

		this.widget.sendEvent({
			type: SubscriptionType.sessionOperatorChange,
			data: {
				operator: params.operator
			}
		});
	}

	handleSessionStatus(params, extra, command)
	{
		this.store.commit('widget/dialog', {
			sessionId: params.sessionId,
			sessionStatus: params.sessionStatus,
			sessionClose: params.sessionClose,
		});

		this.widget.sendEvent({
			type: SubscriptionType.sessionStatus,
			data: {
				sessionId: params.sessionId,
				sessionStatus: params.sessionStatus
			}
		});

		if (params.sessionClose)
		{
			this.widget.sendEvent({
				type: SubscriptionType.sessionFinish,
				data: {
					sessionId: params.sessionId,
					sessionStatus: params.sessionStatus
				}
			});

			if (!params.spam)
			{
				this.store.commit('widget/dialog', {
					operator: {
						name: '',
						firstName: '',
						lastName: '',
						workPosition: '',
						avatar: '',
						online: false,
					}
				});
			}
		}
	}

	handleSessionDateCloseVote(params, extra, command)
	{
		this.store.dispatch('widget/setVoteDateFinish', params.dateCloseVote);
	}
}

export {
	WidgetImPullCommandHandler,
	WidgetImopenlinesPullCommandHandler,
};