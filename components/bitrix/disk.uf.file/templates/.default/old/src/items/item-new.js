import {Tag, Text, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import 'ui.progressround';

export default class ItemNew
{
	id: string;
	object: Object;
	container: Element;
	data: Object;
	progress;

	constructor(fileId, fileObject)
	{
		this.id = fileId;
		this.object = fileObject;
		this.data = {
			NAME: fileObject.name,
		};
		EventEmitter.subscribe(this.object, 'onUploadProgress', this.onUploadProgress.bind(this));
		EventEmitter.subscribe(this.object, 'onUploadDone', this.onUploadDone.bind(this));
		EventEmitter.subscribe(this.object, 'onUploadError', this.onUploadError.bind(this));
		this.progress = new BX.UI.ProgressRound({
			width: 18,
			colorTrack: 'rgba(255,255,255,.3)',
			colorBar: '#fff',
			lineSize: 3,
			statusType: BX.UI.ProgressRound.Status.INCIRCLE,
			textBefore: '<i></i>',
			textAfter: this.object.size,
		});
	}

	getContainer()
	{
		if (!this.container)
		{
			let extension = this.object.name.split('.').pop().toLowerCase();
			extension = Text.encode(extension === this.object.name ? '' : extension);
			this.container = Tag.render`
		<div class="disk-file-thumb disk-file-thumb-file disk-file-thumb--${extension} disk-file-thumb--active">
			<div class="ui-icon ui-icon-file-${extension} disk-file-thumb-icon"><i></i></div>
			<div class="disk-file-thumb-text">${Text.encode(this.object.name)}</div>
			<div class="disk-file-thumb-loader">
				${this.progress.getContainer()}
				<div class="disk-file-thumb-loader-btn" onclick="${this.onClickDelete.bind(this)}"></div>
			</div>
		</div>
		`;
		}
		return this.container;
	}

	onClickDelete(event: MouseEvent)
	{
		event.preventDefault();
		event.stopPropagation();

		EventEmitter.emit(this, 'onDelete', [this]);

		setTimeout(() => { this.object.deleteFile(); }, 400);
		delete this.container;
	}

	onUploadProgress({compatData: [fileObject, progress]})
	{
		progress = Math.min(Math.max(this.progress.getValue(), progress), 98);
		this.progress.update(progress);
	}

	onUploadDone({compatData: [fileObject, {file}]})
	{
		EventEmitter.emit(this, 'onUploadDone', [file, fileObject.file]);
		delete this.object.hash;
		this.object.deleteFile();
	}

	onUploadError({compatData: [fileObject]})
	{
		EventEmitter.emit(this, 'onUploadError', [fileObject.file]);
		this.progress.setTextBefore(Loc.getMessage('WDUF_ITEM_ERROR'));
		this.container.classList.add('disk-file-upload-error')
		this.object.deleteFile();
	}
}
