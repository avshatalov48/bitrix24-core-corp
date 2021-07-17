import {PullClient} from "pull.client";
import type {CommandOptions} from "./types";
import UserManager from "./user-manager";

export default class BaseCommandHandler
{
	options: CommandOptions = null;
	userManager: UserManager = null;

	constructor(commandOptions: CommandOptions)
	{
		this.options = commandOptions;
		this.userManager = commandOptions.userManager;
		this.context = commandOptions.context;
	}

	getModuleId(): string
	{
		return 'disk';
	}

	getSubscriptionType(): string
	{
		return PullClient.SubscriptionType.Server;
	}

	filterCurrentObject(handler: Function): any
	{
		return (data) => {
			if (this.context.object.id !== data.object.id)
			{
				return;
			}

			return handler(data);
		};
	}

	isCurrentUser(userId: number): boolean
	{
		return this.context.currentUser.id === userId;
	}
}