import { ReactionHandler } from 'im.event-handler';
import { Utils } from 'im.lib.utils';
import { EventEmitter } from 'main.core.events';
import { EventType } from 'im.const';

export class MobileReactionHandler extends ReactionHandler
{
	constructor($Bitrix)
	{
		super($Bitrix);
		this.loc = $Bitrix.Loc.messages;
	}

	reactToMessage(messageId, reaction)
	{
		let action = reaction.action || ReactionHandler.actions.auto;
		if (action !== ReactionHandler.actions.auto)
		{
			action = action === ReactionHandler.actions.set
				? ReactionHandler.actions.plus
				: ReactionHandler.actions.minus;
		}

		const eventParameters = [
			'reactMessage',
			`reactMessage|${messageId}`,
			{ messageId, action },
			false,
			1000
		];

		BXMobileApp.Events.postToComponent('chatbackground::task::action', eventParameters, 'background');

		if (reaction.action === ReactionHandler.actions.set)
		{
			setTimeout(() => app.exec('callVibration'), 200);
		}
	}

	openMessageReactionList(id, reactions)
	{
		if (!Utils.dialog.isChatId(this.getDialogId()))
		{
			return;
		}

		let users = [];
		Object.keys(reactions).forEach(reaction => {
			users = [...users, ...reactions[reaction]];
		});

		EventEmitter.emit(EventType.mobile.openUserList, {
			users: users,
			title: this.loc['MOBILE_MESSAGE_LIST_LIKE']
		});
	}

	onSetMessageReaction({data})
	{
		this.reactToMessage(data.message.id, data.reaction);
	}

	getDialogId(): number | string
	{
		return this.store.state.application.dialog.dialogId;
	}
}