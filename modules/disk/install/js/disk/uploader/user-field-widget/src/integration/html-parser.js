import { Text, Runtime } from 'main.core';
import { EventEmitter } from 'main.core.events';

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

	insertFile(item: TileWidgetItem): void
	{
		const bbDelimiter: string = item.isImage ? '\n' : ' ';
		const htmlDelimiter: string = item.isImage ? '<br>' : '&nbsp;';

		EventEmitter.emit(this.getHtmlEditor(), 'OnInsertContent', [
			bbDelimiter + this.createItemBBCode(item) + bbDelimiter,
			htmlDelimiter + this.createItemHtml(item) + htmlDelimiter,
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

	selectItem(item: TileWidgetItem): void
	{
		item.tileWidgetData.selected = true;
	}

	deselectItem(item: TileWidgetItem): void
	{
		item.tileWidgetData.selected = false;
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

		const items: TileWidgetItem[] = this.#form.getUserFieldControl().getItems();
		items.forEach((item: TileWidgetItem): void => {
			if (inserted.has(item.serverFileId))
			{
				hasInsertedItems = true;
				this.selectItem(item);
			}
			else
			{
				this.deselectItem(item);
			}
		});

		if (this.#form.getUserFieldControl().getPhotoTemplateMode() === 'auto')
		{
			this.#form.getUserFieldControl().setPhotoTemplate(hasInsertedItems ? 'gallery' : 'grid');
		}
	}

	createItemHtml(item: TileWidgetItem, id: string): string
	{
		const tagId = this.getHtmlEditor().SetBxTag(false, {
			tag: this.#parserId,
			serverFileId: item.serverFileId,
			hideContextMenu: true,
			fileId: item.serverFileId,
		});

		if (item.isImage)
		{
			const imageSrc = this.getHtmlEditor().bbCode ? item.previewUrl : item.serverPreviewUrl;
			const previewWidth = this.getHtmlEditor().bbCode ? item.previewWidth : item.serverPreviewWidth;
			const previewHeight = this.getHtmlEditor().bbCode ? item.previewHeight : item.serverPreviewHeight;

			const renderWidth = 600; // half size of imagePreviewWidth
			const renderHeight = 600; // half size of imagePreviewHeight
			const ratioWidth: number = renderWidth / previewWidth;
			const ratioHeight: number = renderHeight / previewHeight;
			const ratio: number = Math.min(ratioWidth, ratioHeight);

			const useOriginalSize = ratio > 1; // image is too small
			const width = useOriginalSize ? previewWidth : previewWidth * ratio;
			const height = useOriginalSize ? previewHeight : previewHeight * ratio;

			return `<img style="max-width: 90%;" width="${width}" height="${height}" data-bx-file-id="${Text.encode(item.serverFileId)}" id="${tagId}" src="${imageSrc}" title="${Text.encode(item.name)}" data-bx-paste-check="Y" />`;
		}
		else if (item.customData.fileType === 'player')
		{
			return `<img contenteditable="false" class="bxhtmled-player-surrogate" data-bx-file-id="${Text.encode(item.serverFileId)}" id="${tagId}" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-bx-paste-check="Y" />`;
		}

		return `<span contenteditable="false" data-bx-file-id="${Text.encode(item.serverFileId)}" id="${tagId}" style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;">${Text.encode(item.name)}</span>`;
	}

	createItemBBCode(item: TileWidgetItem): string
	{
		return this.#tag.replace('#id#', item.serverFileId);
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

				const items: TileWidgetItem[] = this.#form.getUserFieldControl().getItems();
				const item: TileWidgetItem = items.find((item: TileWidgetItem) => {
					return item.serverFileId === attachedId || item.customData.objectId === objectId;
				});

				if (item)
				{
					this.selectItem(item);

					return this.createItemHtml(item, id);
				}

				return str;
			}
		);
	}

	#unparse(bxTag): string
	{
		const { serverFileId } = bxTag;

		const items: TileWidgetItem[] = this.#form.getUserFieldControl().getItems();
		const item: TileWidgetItem = items.find((item: TileWidgetItem): boolean => {
			return item.serverFileId === serverFileId;
		});

		if (item)
		{
			return this.createItemBBCode(item);
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
