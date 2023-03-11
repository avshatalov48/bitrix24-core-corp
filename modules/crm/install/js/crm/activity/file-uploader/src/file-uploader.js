import { Dom, Tag, Type } from 'main.core';
import { TileWidget } from 'ui.uploader.tile-widget';

import type { FileUploaderOptions } from './file-uploader-options';

import 'ui.design-tokens';
import './file-uploader.css'

const MAX_UPLOAD_FILE_SIZE = 1024 * 1024 * 50; // 50M;

export class FileUploader
{
	#container: ?HTMLElement = null;
	#widget: ?TileWidget = null;

	constructor(params: FileUploaderOptions)
	{
		this.#assertValidParams(params);

		this.#widget = new TileWidget({
			controller: 'crm.fileUploader.todoActivityUploaderController',
			controllerOptions: {
				entityId: params.ownerId,
				entityTypeId: params.ownerTypeId,
				activityId: params.activityId
			},
			files: Type.isArrayFilled(params.files) ? params.files : [],
			events: Type.isPlainObject(params.events) ? params.events : {},
			multiple: true,
			autoUpload: true,
			maxFileSize: MAX_UPLOAD_FILE_SIZE
		});

		if (Type.isDomNode(params.baseContainer))
		{
			this.#container = Tag.render`<div class="crm-activity__todo-editor-file-uploader-wrapper"></div>`;

			const baseContainer = params.baseContainer;

			Dom.insertAfter(this.#container, baseContainer);

			this.#widget.renderTo(this.#container);
		}
	}

	getWidget(): TileWidget
	{
		return this.#widget;
	}

	getContainer(): HTMLElement
	{
		return this.#container;
	}

	renderTo(container: HTMLElement): void
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('FileUploader container must be a DOM Node');
		}

		this.#container = container;
		this.#widget.renderTo(container);
	}

	getFiles(): Array
	{
		return this.#widget.getUploader().getFiles();
	}

	getServerFileIds(): Array
	{
		const files = this.#widget.getUploader().getFiles();
		if (files.length === 0)
		{
			return [];
		}

		const completedFiles = files.filter(file => file.isComplete());
		if (completedFiles.length === 0)
		{
			return [];
		}

		return completedFiles.map(file => file.getServerId());
	}

	#assertValidParams(params: FileUploaderOptions): void
	{
		if (!Type.isPlainObject(params))
		{
			throw new Error('BX.Crm.Activity.FileUploader: The "params" argument must be object.');
		}

		if (!Type.isNumber(params.ownerId))
		{
			throw new Error('BX.Crm.Activity.FileUploader: The "ownerId" argument must be set.');
		}

		if (!Type.isNumber(params.ownerTypeId))
		{
			throw new Error('BX.Crm.Activity.FileUploader: The "ownerTypeId" argument must be set.');
		}
	}
}
