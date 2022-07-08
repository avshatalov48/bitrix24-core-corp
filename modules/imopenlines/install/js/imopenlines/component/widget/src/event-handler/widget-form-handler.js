import { EventEmitter } from "main.core.events";
import { WidgetEventType, FormType, RestMethod, SubscriptionType, VoteType } from "../const";

export class WidgetFormHandler
{
	store: Object = null;
	application: Object = null;
	restClient: Object = null;

	constructor($Bitrix)
	{
		this.store = $Bitrix.Data.get('controller').store;
		this.restClient = $Bitrix.RestClient.get();
		this.application = $Bitrix.Application.get();

		this.showFormHandler = this.onShowForm.bind(this);
		this.hideFormHandler = this.onHideForm.bind(this);
		this.sendVoteHandler = this.onSendVote.bind(this);
		EventEmitter.subscribe(WidgetEventType.showForm, this.showFormHandler);
		EventEmitter.subscribe(WidgetEventType.hideForm, this.hideFormHandler);
		EventEmitter.subscribe(WidgetEventType.sendDialogVote, this.sendVoteHandler);
	}

	onShowForm({data: event})
	{
		clearTimeout(this.showFormTimeout);
		if (event.type === FormType.like)
		{
			if (event.delayed)
			{
				this.showFormTimeout = setTimeout(() => {
					this.showLikeForm();
				}, 5000);
			}
			else
			{
				this.showLikeForm();
			}
		}
		else if (event.type === FormType.smile)
		{
			this.showSmiles();
		}
	}

	onHideForm()
	{
		this.hideForm();
	}

	onSendVote({data: {vote}})
	{
		console.warn('VOTE', vote);
		this.sendVote(vote);
	}

	showLikeForm()
	{
		if (this.application.offline)
		{
			return false;
		}

		clearTimeout(this.showFormTimeout);
		if (!this.getWidgetModel().common.vote.enable)
		{
			return false;
		}

		if (
			this.getWidgetModel().dialog.sessionClose
			&& this.getWidgetModel().dialog.userVote !== VoteType.none
		)
		{
			return false;
		}

		this.store.commit('widget/common', {showForm: FormType.like});
	}

	showSmiles()
	{
		this.store.commit('widget/common', {showForm: FormType.smile});
	}

	sendVote(vote)
	{
		const {sessionId} = this.getWidgetModel().dialog;
		if (!sessionId)
		{
			return false;
		}

		this.restClient.callMethod(RestMethod.widgetVoteSend, {
			'SESSION_ID': sessionId,
			'ACTION': vote
		}).catch(() => {
			this.store.commit('widget/dialog', {userVote: VoteType.none});
		});

		this.application.sendEvent({
			type: SubscriptionType.userVote,
			data: { vote }
		});
	}

	hideForm()
	{
		clearTimeout(this.showFormTimeout);

		if (this.getWidgetModel().common.showForm !== FormType.none)
		{
			this.store.commit('widget/common', {showForm: FormType.none});
		}
	}

	getWidgetModel()
	{
		return this.store.state.widget;
	}

	destroy()
	{
		EventEmitter.unsubscribe(WidgetEventType.showForm, this.showFormHandler);
		EventEmitter.unsubscribe(WidgetEventType.hideForm, this.hideFormHandler);
		EventEmitter.unsubscribe(WidgetEventType.sendDialogVote, this.sendVoteHandler);
	}
}