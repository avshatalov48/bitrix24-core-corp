import BaseCommandHandler from "./base-command-handler";
import {PullClient} from "pull.client";
import type {ExitDocumentMessage, HiDocumentMessage, PingDocumentMessage, WelcomeDocumentMessage} from "./types";

export default class ClientCommandHandler extends BaseCommandHandler
{
	getSubscriptionType(): string
	{
		return PullClient.SubscriptionType.Client;
	}

	getMap(): Object
	{
		return {
			exitDocument: this.handleExitDocument.bind(this),
			pingDocument: this.handlePingDocument.bind(this),
			hiToDocument: this.handleHiToDocument.bind(this),
			welcomeToDocument: this.handleWelcomeToDocument.bind(this),
		};
	}

	handleExitDocument(data: ExitDocumentMessage): void
	{
		console.log('exitDocument', data);

		const fromUserId = data.fromUserId;
		if (!this.isCurrentUser(fromUserId))
		{
			this.userManager.remove(fromUserId);
		}
	}

	handleWelcomeToDocument(data: WelcomeDocumentMessage): void
	{
		console.log('handleWelcomeToDocument', data);

		this.processNewbieInDocument(data);
	}

	handleHiToDocument(data: HiDocumentMessage): void
	{
		console.log('handleHiToDocument', data);

		const newbieAdded = this.processNewbieInDocument(data);
		if (newbieAdded)
		{
			//immediately send welcome to add actual online information for new user.
			this.userManager.sendWelcomeToUser();
		}
	}

	processNewbieInDocument(data: HiDocumentMessage|WelcomeDocumentMessage): boolean
	{
		const fromUserId = data.user.id;
		if (this.isCurrentUser(fromUserId))
		{
			return false;
		}

		if (this.userManager.has(fromUserId))
		{
			this.userManager.updateOnline(fromUserId);

			return false;
		}

		this.userManager.add(data.user);

		return true;
	}

	handlePingDocument(data: PingDocumentMessage): void
	{
		console.log('handlePingDocument', data);

		const fromUserId = data.fromUserId;
		if (this.isCurrentUser(fromUserId))
		{
			return;
		}

		if (this.userManager.has(fromUserId))
		{
			this.userManager.updateOnline(fromUserId);
		}
		else
		{
			if (this.userManager.sentGreetings())
			{
				this.userManager.getUserInfo(data.fromUserId, data.infoToken).then(userData => {
					this.userManager.add(userData);
				}, () => {});
			}
		}
	}
}