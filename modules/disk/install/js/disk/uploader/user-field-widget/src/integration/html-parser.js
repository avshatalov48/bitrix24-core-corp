import { Text, Runtime } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { UploaderFile } from 'ui.uploader.core';

import type { TileWidgetItem } from 'ui.uploader.tile-widget';
import type MainPostForm from './main-post-form';

export default class HtmlParser
{
	#form: MainPostForm = null;
	#parserId = 'diskfile0';
	#tag: string = '[DISK FILE ID=#id#]';
	#regexp: RegExp = /\[(?:DOCUMENT ID|DISK FILE ID)=(n?[0-9]+)\]/ig;
	syncHighlightsDebounced: Function = null;

	constructor(form: MainPostForm)
	{
		this.#form = form;

		this.syncHighlightsDebounced = Runtime.debounce(this.syncHighlights, 500, this);

		// BBCode Parser Registration ([DISK FILE ID=190])
		EventEmitter.emit(this.#form.getEventObject(), 'OnParserRegister', this.getParser());
	}

	getParser(): Object<string, any>
	{
		return {
			id: this.#parserId,
			init: this.#init.bind(this),
			parse: this.#parse.bind(this),
			unparse: this.#unparse.bind(this),
		};
	}

	/**
	 *
	 * @returns {Window.BXEditor}
	 */
	getHtmlEditor()
	{
		return this.#form.getHtmlEditor();
	}

	insertFile(file: UploaderFile): void
	{
		const bbDelimiter: string = file.isImage() ? '\n' : ' ';
		const htmlDelimiter: string = file.isImage() ? '<br>' : '&nbsp;';

		EventEmitter.emit(this.getHtmlEditor(), 'OnInsertContent', [
			bbDelimiter + this.createItemBBCode(file) + bbDelimiter,
			htmlDelimiter + this.createItemHtml(file) + htmlDelimiter,
		]);

		this.syncHighlights();
	}

	removeFile(item: TileWidgetItem): void
	{
		if (this.getHtmlEditor().GetViewMode() === 'wysiwyg')
		{
			const doc = this.getHtmlEditor().GetIframeDoc();
			Object.keys(this.getHtmlEditor().bxTags).forEach((tagId: string) => {
				const tag = this.getHtmlEditor().bxTags[tagId];
				if (tag.tag === this.#parserId && tag.serverFileId === item.serverFileId)
				{
					const node = doc.getElementById(tagId);
					if (node)
					{
						node.parentNode.removeChild(node);
					}
				}
			});

			this.getHtmlEditor().SaveContent();
		}
		else
		{
			const content = this.getHtmlEditor().GetContent().replace(
				this.#regexp,
				(str, foundId): string => {
					const { objectId, attachedId } = this.#getIds(foundId);
					const items: TileWidgetItem[] = this.#form.getUserFieldControl().getItems();
					const item: TileWidgetItem = items.find((item: TileWidgetItem) => {
						return item.serverFileId === attachedId || item.customData.objectId === objectId;
					});

					return item ? '' : str;
				},
			);

			this.getHtmlEditor().SetContent(content);
			this.getHtmlEditor().Focus();
		}

		this.syncHighlights();
	}

	selectItem(file: UploaderFile): void
	{
		file.setCustomData('tileSelected', true);
	}

	deselectItem(file: UploaderFile): void
	{
		file.setCustomData('tileSelected', false);
	}

	syncHighlights(): void
	{
		const doc = this.getHtmlEditor().GetIframeDoc();
		const inserted: Set<number | string> = new Set();
		Object.keys(this.getHtmlEditor().bxTags).forEach((tagId: string): void => {
			const tag = this.getHtmlEditor().bxTags[tagId];
			if (tag.tag === this.#parserId && doc.getElementById(tagId))
			{
				inserted.add(tag.serverFileId);
			}
		});

		let hasInsertedItems = false;

		const files: UploaderFile[] = this.#form.getUserFieldControl().getFiles();
		files.forEach((file: UploaderFile): void => {
			if (inserted.has(file.getServerFileId()))
			{
				hasInsertedItems = true;
				this.selectItem(file);
			}
			else
			{
				this.deselectItem(file);
			}
		});

		if (this.#form.getUserFieldControl().getPhotoTemplateMode() === 'auto')
		{
			this.#form.getUserFieldControl().setPhotoTemplate(hasInsertedItems ? 'gallery' : 'grid');
		}
	}

	createItemHtml(file: UploaderFile, id: string): string
	{
		const tagId = this.getHtmlEditor().SetBxTag(false, {
			tag: this.#parserId,
			serverFileId: file.getServerFileId(),
			hideContextMenu: true,
			fileId: file.getServerFileId(),
		});

		if (file.isImage())
		{
			const imageSrc = this.getHtmlEditor().bbCode ? file.getPreviewUrl() : file.getServerPreviewUrl();
			const previewWidth = this.getHtmlEditor().bbCode ? file.getPreviewWidth() : file.getServerPreviewWidth();
			const previewHeight = this.getHtmlEditor().bbCode ? file.getPreviewHeight() : file.getServerPreviewHeight();

			const renderWidth = 600; // half size of imagePreviewWidth
			const renderHeight = 600; // half size of imagePreviewHeight
			const ratioWidth: number = renderWidth / previewWidth;
			const ratioHeight: number = renderHeight / previewHeight;
			const ratio: number = Math.min(ratioWidth, ratioHeight);

			const useOriginalSize = ratio > 1; // image is too small
			const width = useOriginalSize ? previewWidth : previewWidth * ratio;
			const height = useOriginalSize ? previewHeight : previewHeight * ratio;

			return `<img style="max-width: 90%;" width="${width}" height="${height}" data-bx-file-id="${Text.encode(file.getServerFileId())}" id="${tagId}" src="${imageSrc}" title="${Text.encode(file.getName())}" data-bx-paste-check="Y" />`;
		}
		else if (file.getCustomData('fileType') === 'player')
		{
			return `<img contenteditable="false" class="bxhtmled-player-surrogate" data-bx-file-id="${Text.encode(file.getServerFileId())}" id="${tagId}" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-bx-paste-check="Y" />`;
		}

		return `<span contenteditable="false" data-bx-file-id="${Text.encode(file.getServerFileId())}" id="${tagId}" style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;">${Text.encode(file.getName())}</span>`;
	}

	createItemBBCode(file: UploaderFile): string
	{
		return this.#tag.replace('#id#', file.getServerFileId());
	}

	#init(htmlEditor): void
	{
		// stub
	}

	#parse(content): string
	{
		if (!this.#regexp.test(content))
		{
			return content;
		}

		this.syncHighlightsDebounced();

		return content.replace(
			this.#regexp,
			(str, id): string => {
				const { objectId, attachedId } = this.#getIds(id);

				const files: UploaderFile[] = this.#form.getUserFieldControl().getFiles();
				const insertedFile: UploaderFile = files.find((file: UploaderFile) => {
					return file.getServerFileId() === attachedId || file.getCustomData('objectId') === objectId;
				});

				if (insertedFile)
				{
					this.selectItem(insertedFile);

					return this.createItemHtml(insertedFile, id);
				}

				return str;
			},
		);
	}

	#unparse(bxTag): string
	{
		const { serverFileId } = bxTag;

		const files: UploaderFile[] = this.#form.getUserFieldControl().getFiles();
		const uploaderFile: UploaderFile = files.find((file: UploaderFile): boolean => {
			return file.getServerFileId() === serverFileId;
		});

		if (uploaderFile)
		{
			return this.createItemBBCode(uploaderFile);
		}

		return '';
	}

	#getIds(id: string): { objectId: ?number, attachedId: ?number}
	{
		let objectId: ?number = null;
		let attachedId: ?number = null;
		if (id[0] === 'n')
		{
			objectId = Text.toInteger(id.replace('n', ''));
		}
		else
		{
			attachedId = Text.toInteger(id);
		}

		return { objectId, attachedId };
	}
}
