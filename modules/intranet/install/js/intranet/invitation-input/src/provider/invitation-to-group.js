import { ajax } from 'main.core';
import { InvitationProvider } from './invitation-provider';

export class InvitationToGroup extends InvitationProvider
{
	#groupId: number;
	#users: Object;

	constructor(groupId: number, users: Object)
	{
		super();
		this.#groupId = groupId;
		this.#users = users;
	}

	invite(): Promise
	{
		return ajax.runAction('intranet.invite.inviteUsersToCollab', {
			data: {
				collabId: this.#groupId,
				users: this.#users,
			},
		});
	}
}
