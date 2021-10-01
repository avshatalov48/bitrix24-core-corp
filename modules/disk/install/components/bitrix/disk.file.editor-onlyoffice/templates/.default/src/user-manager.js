import type {Context, User, UserManagerOptions} from "./types";
import {PULL} from "pull.client";
import {ajax as Ajax} from "main.core";
import {Users} from "disk.users";
import {BaseEvent, EventEmitter} from "main.core.events";

const ALLOWED_ATTEMPTS_TO_GET_USER_INFO = 3;
const SECONDS_TO_ACTUALIZE_ONLINE = 25;

export default class UserManager
{
	userBoxNode: HTMLElement = null;
	context: Context = null
	users: Users;
	badAttempts: Map<number>;
	alreadySaidHi: boolean = false;

	constructor(options: UserManagerOptions)
	{
		this.users = new BX.Disk.Users([]);
		this.badAttempts = new Map();
		this.context = options.context;
		this.userBoxNode = options.userBoxNode;
		this.alreadySaidHi = false;
		this.add(this.context.currentUser);

		this.bindEvents();
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('onPullStatus', (event: BaseEvent) => {
			if (event.getData()[0] === 'online')
			{
				this.handleWhenPullConnected();
			}
		});
	}

	handleWhenPullConnected(): void
	{
		if (!this.sentGreetings())
		{
			this.sendHiToUsers();
			setInterval(this.actualizeOnline.bind(this), 1000*SECONDS_TO_ACTUALIZE_ONLINE);
		}
	}

	actualizeOnline(): void
	{
		this.refineUsersByOnline();
		if (!this.sentGreetings())
		{
			this.sendHiToUsers();
		}
		else
		{
			this.sendPingToUsers();
		}
	}

	sentGreetings(): boolean
	{
		return this.alreadySaidHi;
	}

	sendHiToUsers(): void
	{
		if (!PULL.isConnected())
		{
			return;
		}

		PULL.sendMessageToChannels([this.context.object.publicChannel], 'disk', 'hiToDocument', {
			user: {
				id: this.context.currentUser.id,
				name: this.context.currentUser.name,
				avatar: this.#makeLinkAbsolute(this.context.currentUser.avatar),
			},
		});

		this.alreadySaidHi = true;
	}

	#makeLinkAbsolute(link: string): string
	{
		if (link.includes('http://') || link.includes('https://'))
		{
			return link;
		}

		return document.location.origin + link;
	}

	sendWelcomeToUser(): void
	{
		if (!PULL.isConnected())
		{
			return;
		}

		PULL.sendMessageToChannels([this.context.object.publicChannel], 'disk', 'welcomeToDocument', {
			user: {
				id: this.context.currentUser.id,
				name: this.context.currentUser.name,
				avatar: this.#makeLinkAbsolute(this.context.currentUser.avatar),
			},
		});
	}

	sendPingToUsers(): void
	{
		if (!PULL.isConnected())
		{
			return;
		}

		PULL.sendMessageToChannels([this.context.object.publicChannel], 'disk', 'pingDocument', {
			fromUserId: this.context.currentUser.id,
			infoToken: this.context.currentUser.infoToken,
		});
	}

	add(user: User): void
	{
		if (!this.users.hasUser(user.id))
		{
			this.users.addUser(user);

			console.log('Hi new user!', user.id);
		}

		this.updateOnline(user.id);
		this.renderBox();
	}

	updateOnline(userId: number): void
	{
		if (this.users.hasUser(userId))
		{
			this.users.getUser(userId).onlineAt = Date.now();
		}
	}

	getUserInfo(userId: number, infoToken: string): Promise
	{
		if (this.badAttempts.get(userId) >= ALLOWED_ATTEMPTS_TO_GET_USER_INFO)
		{
			return new Promise((resolve, reject) => {
				reject({
					status: 'blocked',
				})
			});
		}

		return new Promise((resolve, reject) => {
			Ajax.runComponentAction('bitrix:disk.file.editor-onlyoffice', 'getUserInfo', {
				mode: 'ajax',
				json: {
					documentSessionId: this.context.documentSession.id,
					documentSessionHash: this.context.documentSession.hash,
					userId: userId,
					infoToken: infoToken,
				}
			}).then((response) => {
				if (response.status === 'success')
				{
					this.badAttempts.delete(userId);
					resolve(response.data.user);
				}
			}, (response) => {
				const attempts = this.badAttempts.get(userId) || 0;
				this.badAttempts.set(userId, attempts + 1);
				console.log(this.badAttempts)

				reject(response);
			});
		});
	}

	has(userId: number): boolean
	{
		return this.users.hasUser(userId);
	}

	remove(userId: number): void
	{
		if (userId === this.context.currentUser.id)
		{
			return;
		}

		this.users.deleteUser(userId);
		this.renderBox();
	}

	refineUsersByOnline(): void
	{
		const secondsToOffline = 1000*(SECONDS_TO_ACTUALIZE_ONLINE+1)*2;
		const now = Date.now();

		this.users.forEach((user: User) => {
			if (now - user.onlineAt > secondsToOffline)
			{
				this.remove(user.id);
			}
		})
	}

	renderBox(): void
	{
		if (!this.userBoxNode.childElementCount)
		{
			this.userBoxNode.appendChild(this.users.getContainer());
		}
	}
};