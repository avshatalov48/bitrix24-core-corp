import {Cache, Tag, Text} from 'main.core';
import Item from './item';
import type {ItemSavedType} from './item-type';

export default class ItemImage extends Item
{
	id: string;
	data: ItemSavedType;
	cache = new Cache.MemoryCache();

	setData(data)
	{
		this.data = data;
		this.data.BIG_REVIEW_URL = this.data.PREVIEW_URL.replace(/\&(width|height)\=\d+/gi, '');
	}

	getContainer(): Element
	{
		return this.cache.remember('container', () => {
			const nameWithoutExtension = Text.encode(this.getNameWithoutExtension());
			const extension = Text.encode(this.data['EXTENSION']).toLowerCase();

			return Tag.render`
		<div class="disk-file-thumb disk-file-thumb-preview">
			<div style="background-image: url('${encodeURI(this.data['PREVIEW_URL'])}'); background-size: cover;" class="disk-file-thumb-image"></div>
			${this.getIcon(extension)}
			${this.getNameBox(nameWithoutExtension, extension)}
			${this.getDeleteButton()}
			${this.getButtonBox()}
		</div>`;
		});
	}

	getHTMLForHTMLEditor(tagId: String)
	{
		return `<img style="max-width: 90%;" data-bx-file-id="${Text.encode(this.data.ID)}" id="${tagId}" src="${this.data.BIG_REVIEW_URL}" />`
	}

	static detect(itemData: ItemSavedType)
	{
		return !!itemData['PREVIEW_URL'];
	}
}
