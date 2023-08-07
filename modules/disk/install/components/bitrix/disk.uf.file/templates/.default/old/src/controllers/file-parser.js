import {Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import FileController from './file-controller';
import Item from '../items/item';
import ItemImage from '../items/item-image';

let justCounter = 0;

export default class FileParser {
	id: String;
	htmlEditor;
	items: Map<Item>;
	tag: string = '[DISK FILE ID=#id#]';
	regexp: RegExp = /\[(?:DOCUMENT ID|DISK FILE ID)=([n0-9]+)\]/ig;

	constructor(fileController: FileController)
	{
		this.id = ['diskfile' + justCounter++].join('_');
		this.items = fileController.items;
	}

	getInterface()
	{
		return {
			id: this.id,
			init: (htmlEditor) => {
				this.htmlEditor = htmlEditor;
			},
			parse: this.parse.bind(this),
			unparse: this.unparse.bind(this),
		};
	}

	hasInterface()
	{
		return this.regexp !== null && this.tag !== null;
	}

	setParams({tag, regexp})
	{
		if (Type.isStringFilled(tag) && tag !== 'null')
		{
			this.tag = tag;
		}
		else
		{
			this.tag = null;
		}

		if (Type.isStringFilled(regexp) && regexp !== 'null')
		{
			this.regexp = new RegExp(regexp);
		}
		else if (regexp instanceof RegExp)
		{
			this.regexp = regexp;
		}
		else
		{
			this.regexp = null;
		}
	}

	insertFile(id: string)
	{
		const item = this.items.get(String(id));
		if (item)
		{
			const bbDelimiter = item instanceof ItemImage ? '\n' : ' ';
			const htmlDelimiter = item instanceof ItemImage ? '<br>' : '&nbsp;';
			EventEmitter.emit(this.htmlEditor, 'OnInsertContent', [
				bbDelimiter + this.getItemBBCode(id) + bbDelimiter,
				htmlDelimiter + this.getItemHTML(id) + htmlDelimiter
			]);
		}
	}

	getItemBBCode(id: String)
	{
		const item = this.items.get(String(id));
		if (item && item.isPluggedIn())
		{
			item.setInsertedInText();
		}

		return this.tag.replace('#id#', id);
	}

	getItemHTML(id: String)
	{
		const item = this.items.get(String(id));
		if (item)
		{
			return this.#getHTMLByItem(item, id);
		}

		return null;
	}

	#getHTMLByItem(item: Item, id: string)
	{
		if (item.isPluggedIn())
		{
			item.setInsertedInText();
		}

		return item.getHTMLForHTMLEditor(this.htmlEditor.SetBxTag(false, {tag: this.id, fileId: id, itemId: item.getId()}));
	}

	deleteFile(fileId)
	{
		if (!this.items.has(fileId))
		{
			return;
		}

		const fileIds = this.items.get(fileId).getAllIds();

		if (this.htmlEditor.GetViewMode() === 'wysiwyg')
		{
			const doc = this.htmlEditor.GetIframeDoc();

			for (let ii in this.htmlEditor.bxTags)
			{
				if (this.htmlEditor.bxTags.hasOwnProperty(ii)
					&& typeof this.htmlEditor.bxTags[ii] === 'object'
					&& this.htmlEditor.bxTags[ii]['tag'] === this.id
					&& fileIds.indexOf(String(this.htmlEditor.bxTags[ii]['itemId'])) >= 0
					&& doc.getElementById(ii)
				)
				{
					const node = doc.getElementById(ii);
					node.parentNode.removeChild(node);
				}
			}
			this.htmlEditor.SaveContent();
		}
		else
		{
			const content = this.htmlEditor.GetContent().replace(this.regexp,
				function(str, foundId) {
					return fileIds.indexOf(foundId) >= 0 ? '' : str;
				}
			);
			this.htmlEditor.SetContent(content);
			this.htmlEditor.Focus();
		}
	}

	parse(content)
	{
		if (!this.regexp.test(content))
		{
			return content;
		}
		content = content.replace(
			this.regexp,
			function(str, id)
			{
				const foundedItem = this.items.has(id) ? this.items.get(id) : [...this.items.values()].find((item: Item) => {
					return item.getAllIds().indexOf(id) >= 0;
				});
				if (foundedItem)
				{
					return this.#getHTMLByItem(foundedItem, id);
				}
				return str;
			}.bind(this)
		);
		return content;
	}

	unparse(bxTag, {node})
	{
		const id = bxTag.itemId;

		if (this.items.has(id))
		{
			return this.getItemBBCode(id);
		}
		return '';
	}
}