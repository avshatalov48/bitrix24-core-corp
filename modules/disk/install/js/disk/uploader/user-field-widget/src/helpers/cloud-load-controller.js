import { UploaderFile, AbstractLoadController, Server } from 'ui.uploader.core';

export default class CloudLoadController extends AbstractLoadController
{
	constructor(server: Server, options = {})
	{
		super(server, options);
	}

	load(file: UploaderFile): void
	{
		this.emit('onProgress', { progress: 100 });
		this.emit('onLoad');
	}

	abort(): void
	{

	}
}