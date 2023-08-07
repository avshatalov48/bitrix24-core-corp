import { UploaderFile, AbstractUploadController, UploaderError, Server } from 'ui.uploader.core';

export default class CloudUploadController extends AbstractUploadController
{
	#fileId: string = null;
	#serviceId: string = null;

	constructor(server: Server, options = {})
	{
		super(server, options);

		this.#fileId = options.fileId;
		this.#serviceId = options.serviceId;
	}

	upload(file: UploaderFile): void
	{
		BX.Disk.ExternalLoader.startLoad({
			file: {
				id: this.#fileId,
				service: this.#serviceId,
			},
			onFinish: (newData): void => {
				this.emit('onUpload', { fileInfo: newData.fileInfo});
			},
			onProgress: (progress): void => {
				this.emit('onProgress', { progress: progress });
			},
			onError: (errors): void => {
				this.emit('onError', { error: UploaderError.createFromAjaxErrors(errors) });
			},
		});
	}

	abort(): void
	{

	}
}