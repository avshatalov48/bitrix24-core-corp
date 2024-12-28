import { Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Api } from 'sign.v2.api';
import { Uploader, UploaderFile } from 'ui.uploader.core';

export class BlankImporter extends EventEmitter
{
	#uploader: Uploader;
	#api: Api;

	constructor(target: HTMLElement)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.BlankImporter');
		this.#api = new Api();
		this.#uploader = new Uploader({
			browseElement: target,
			autoUpload: false,
			multiple: false,
			acceptedFileTypes: '.json',
		});

		this.#subscribeOnEvents();
	}

	#subscribeOnEvents(): void
	{
		this.#uploader.subscribe('File:onAdd', (e: BaseEvent) => {
			const uploaderFile: UploaderFile = e.getData().file;
			const reader: FileReader = new FileReader();
			reader.onload = (event: ProgressEvent): void => this.#importBlank(event.target.result);
			reader.readAsText(uploaderFile.getBinary());
		});
	}

	async #importBlank(serializedJson: string): Promise<void>
	{
		try
		{
			await this.#api.template.importBlank(serializedJson);

			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_BLANK_IMPORTER_SUCCESS'),
			});

			this.emit('onSuccessImport');
		}
		catch (e)
		{
			console.error(e);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_BLANK_IMPORTER_FAILURE'),
			});
		}
	}
}