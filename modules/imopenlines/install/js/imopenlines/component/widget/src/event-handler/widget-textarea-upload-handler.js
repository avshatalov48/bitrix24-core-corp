import {TextareaUploadHandler} from 'im.event-handler';
import {Logger} from 'im.lib.logger';
import {EventEmitter} from 'main.core.events';
import {WidgetEventType} from '../const';
import {Uploader} from 'im.lib.uploader';

export class WidgetTextareaUploadHandler extends TextareaUploadHandler
{
	storedFile: Object = null;
	widgetApplication: Object = null;

	constructor($Bitrix)
	{
		super($Bitrix);
		this.widgetApplication = $Bitrix.Application.get();

		this.onConsentAcceptedHandler = this.onConsentAccepted.bind(this);
		this.onConsentDeclinedHandler = this.onConsentDeclined.bind(this);
		EventEmitter.subscribe(WidgetEventType.consentAccepted, this.onConsentAcceptedHandler);
		EventEmitter.subscribe(WidgetEventType.consentDeclined, this.onConsentDeclinedHandler);
	}

	destroy(): void
	{
		super.destroy();
		EventEmitter.unsubscribe(WidgetEventType.consentAccepted, this.onConsentAcceptedHandler);
		EventEmitter.unsubscribe(WidgetEventType.consentDeclined, this.onConsentDeclinedHandler);
	}

	getUserId(): number
	{
		return this.controller.store.state.widget.user.id;
	}

	getUserHash(): string
	{
		return this.controller.store.state.widget.user.hash;
	}

	getHost(): string
	{
		return this.controller.store.state.widget.common.host;
	}

	addMessageWithFile(event)
	{
		const message = event.getData();
		if (!this.getDiskFolderId())
		{
			this.requestDiskFolderId(message.chatId).then(() => {
				this.addMessageWithFile(event);
			}).catch(error => {
				Logger.error('addMessageWithFile error', error);
				return false;
			});

			return false;
		}

		this.uploader.senderOptions.customHeaders['Livechat-Dialog-Id'] = this.getDialogId();
		this.uploader.senderOptions.customHeaders['Livechat-Auth-Id'] = this.getUserHash();

		this.uploader.addTask({
			taskId: message.file.id,
			fileData: message.file.source.file,
			fileName: message.file.source.file.name,
			generateUniqueName: true,
			diskFolderId: this.getDiskFolderId(),
			previewBlob: message.file.previewBlob,
			chunkSize: this.widgetApplication.getLocalize('isCloud') ? Uploader.CLOUD_MAX_CHUNK_SIZE : Uploader.BOX_MIN_CHUNK_SIZE,
		});
	}

	onTextareaFileSelected({data: event} = {})
	{
		let fileInputEvent = null;
		if (event && event.fileChangeEvent && event.fileChangeEvent.target.files.length > 0)
		{
			fileInputEvent = event.fileChangeEvent;
		}
		else
		{
			fileInputEvent = this.storedFile;
		}

		if (!fileInputEvent)
		{
			return false;
		}

		if (!this.controller.store.state.widget.dialog.userConsent && this.controller.store.state.widget.common.consentUrl)
		{
			this.storedFile = event.fileChangeEvent;
			EventEmitter.emit(WidgetEventType.showConsent);

			return false;
		}

		this.uploadFile(fileInputEvent);
	}

	uploadFile(event)
	{
		if (!event)
		{
			return false;
		}

		if (!this.getChatId())
		{
			EventEmitter.emit(WidgetEventType.requestData);
		}

		this.uploader.addFilesFromEvent(event);
	}

	onConsentAccepted()
	{
		if (!this.storedFile)
		{
			return;
		}

		this.onTextareaFileSelected();
		this.storedFile = '';
	}

	onConsentDeclined()
	{
		if (!this.storedFile)
		{
			return;
		}

		this.storedFile = '';
	}

	getUploaderSenderOptions()
	{
		return {
			host: this.getHost(),
			customHeaders: {
				'Livechat-Auth-Id': this.getUserHash()
			},
			actionUploadChunk: 'imopenlines.widget.disk.upload',
			actionCommitFile: 'imopenlines.widget.disk.commit',
			actionRollbackUpload: 'imopenlines.widget.disk.rollbackUpload',
		};
	}
}