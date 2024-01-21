import { DesktopApi } from 'im.v2.lib.desktop-api';
import { Loc, Uri } from 'main.core';
import { OpenReadOnlyFile } from './open-read-only-file';

export class EditFile extends OpenReadOnlyFile
{
	#handleFileUploadFinished: Function;
	#fileUploadFinishedWasShown: boolean = false;

	constructor({ objectId, url, name })
	{
		super({ objectId, url, name });

		this.#handleFileUploadFinished = this.handleFileUploadFinished.bind(this);
	}

	getDownloadUrl(): string
	{
		return Uri.addParam(this.getUrl(), {
			editIn: 'l',
			action: 'start',
		});
	}

	getUploadUrl(): string
	{
		return Uri.addParam(this.getUrl(), {
			editIn: 'l',
			action: 'commit',
			primaryAction: 'commit',
		});
	}

	openFile(): void
	{
		// eslint-disable-next-line no-undef
		BXFileStorage.EditFile(
			this.getDownloadUrl(),
			this.getUploadUrl(),
			this.getName(),
		);
	}

	handleFileUploadFinished(): void
	{
		if (this.#fileUploadFinishedWasShown)
		{
			return;
		}

		this.unsubscribeToFinishUpload();

		const notificationOptions = {
			id: 'uploadFinished',
			title: this.getName(),
			text: Loc.getMessage('JS_B24DISK_FILE_UPLOAD_FINISHED'),
		};

		this.showNotification(notificationOptions);
	}

	subscribeToFinishUpload(): void
	{
		void DesktopApi.subscribe('BXFileStorageSyncStatusFinalFile', this.#handleFileUploadFinished);
	}

	unsubscribeToFinishUpload(): void
	{
		this.#fileUploadFinishedWasShown = true;
		void DesktopApi.unsubscribe('BXFileStorageSyncStatusFinalFile', this.#handleFileUploadFinished);
	}
}
