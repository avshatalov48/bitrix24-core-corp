import { ajax } from 'main.core';
import { InvitationProvider } from './invitation-provider';

export class InvitationToPortal extends InvitationProvider
{
	#users: Object;

	constructor(users: Object)
	{
		super();
		this.#users = users;
	}

	invite(): Promise
	{
		return ajax.runAction('intranet.v2.Invitation.inviteUsers', {
			data: {
				invitations: this.#users,
			},
		});
	}
}
