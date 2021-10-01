import {Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import 'ui.progressround';
import ItemNew from "./item-new";

export default class ItemNewSelectedCloud extends ItemNew
{
	constructor(fileId, fileObject: ItemNewSelectedCloud)
	{
		super(fileId, fileObject);

		BX.Disk.ExternalLoader.startLoad({
			file: {
				id: this.id,
				name: this.object.name,
				service: this.object.service
			},
			onFinish: (newData) => {
				EventEmitter.emit(this, 'onUploadDone', [newData]);
			},
			onProgress: (progress) => {
				this.onUploadProgress({compatData: [{}, progress]});
			}
		});
	}

	onClickDelete(event: MouseEvent)
	{
		event.preventDefault();
		event.stopPropagation();

		EventEmitter.emit(this, 'onDelete', [this]);

		delete this.container;
	}

	onUploadDone({compatData: [fileObject, {file}]})
	{
	}

	onUploadError({compatData: [fileObject]})
	{
		EventEmitter.emit(this, 'onUploadError', [fileObject.file]);
		this.progress.getContainer().parentNode.removeChild(this.progress.getContainer());
		this.container.classList.add('disk-file-upload-error')
	}
}
