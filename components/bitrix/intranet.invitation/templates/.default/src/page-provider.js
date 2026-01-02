import { PageFactory } from './page-factory';

export class PageProvider
{
	provide(): Map
	{
		const pageFactory = new PageFactory();

		const pages = new Map();
		if (this.canCurrentUserInvite)
		{
			if (this.useLocalEmailProgram)
			{
				pages.set('invite-email', pageFactory.createLocalEmailPage.call(this));
			}

			pages.set('invite', pageFactory.createEmailPage.call(this));
			pages.set('mass-invite', pageFactory.createMassPage.call(this));
			pages.set('invite-with-group-dp', pageFactory.createGroupPage.call(this));
			pages.set('add', pageFactory.createRegisterPage.call(this));
			pages.set('self', pageFactory.createLinkPage.call(this));
		}

		if (this.isExtranetInstalled)
		{
			pages.set('extranet', pageFactory.createExtranetPage.call(this));
		}

		if (this.isCloud && this.canCurrentUserInvite)
		{
			pages.set('integrator', pageFactory.createIntegratorPage.call(this));
		}

		pages.set('success', pageFactory.createSeccessPage.call(this));

		return pages;
	}
}
