import { DesktopApi } from 'im.v2.lib.desktop-api';
import { Loc } from 'main.core';
import { Notifier, NotificationOptions } from 'ui.notification-manager';

export class OpenReadOnlyFile
{
	#objectId: ?number;
	#url: string;
	#name: string;
	#handleAppLaunched: Function;
	#appLaunchedNotifyWasShown: boolean = false;

	constructor({ objectId, url, name })
	{
		this.#objectId = objectId;
		this.#url = url;
		this.#name = name;

		this.#handleAppLaunched = this.handleAppLaunched.bind(this);
	}

	getObjectId(): ?number
	{
		return this.#objectId;
	}

	getUrl(): string
	{
		return this.#url;
	}

	getName(): string
	{
		return this.#name;
	}

	subscribeToFileOpen(): void
	{
		void DesktopApi.subscribe('BXFileStorageLaunchApp', this.#handleAppLaunched);
	}

	unsubscribeToFileOpen(): void
	{
		this.#appLaunchedNotifyWasShown = true;
		void DesktopApi.unsubscribe('BXFileStorageLaunchApp', this.#handleAppLaunched);
	}

	showNotification(options: NotificationOptions): void
	{
		Notifier.notify(options);
	}

	handleAppLaunched(name: string): void
	{
		if (this.#appLaunchedNotifyWasShown)
		{
			return;
		}
		this.unsubscribeToFileOpen();

		const notificationOptions = {
			id: 'launchApp',
			title: name,
			text: Loc.getMessage('JS_B24DISK_LAUNCH_APP_DESCR'),
		};

		this.showNotification(notificationOptions);
	}

	showOpenNotification(): void
	{
		const notificationOptions = {
			id: 'openFile',
			title: this.getName(),
			text: Loc.getMessage('JS_B24DISK_FILE_DOWNLOAD_STARTED_DESCR'),
		};
		this.showNotification(notificationOptions);
	}

	exists(): Promise
	{
		return new Promise((resolve, reject) => {
			// eslint-disable-next-line no-undef
			const filePath = BXFileStorage.FindPathByPartOfId(`|f${this.getObjectId()}`);
			// eslint-disable-next-line no-undef
			BXFileStorage.FileExist(filePath, (exist: boolean) => {
				if (exist && filePath)
				{
					resolve(filePath);
				}
				else
				{
					reject();
				}
			});
		});
	}

	getDownloadUrl(): string
	{
		return this.getUrl();
	}

	run(): void
	{
		this
			.exists()
			.then((filePath) => {
				// eslint-disable-next-line no-undef
				BXFileStorage.ObjectOpen(filePath, () => {});
			})
			.catch(() => {
				if (!this.getUrl())
				{
					return;
				}

				this.showOpenNotification();
				this.subscribeToFileOpen();

				this.openFile();
			});
	}

	openFile(): void
	{
		// eslint-disable-next-line no-undef
		BXFileStorage.ViewFile(
			this.getDownloadUrl(),
			this.getName(),
		);
	}
}
