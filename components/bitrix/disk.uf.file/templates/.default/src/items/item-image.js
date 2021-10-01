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
			const name = Text.encode(this.data['NAME']);
			const extension = Text.encode(this.data['EXTENSION']).toLowerCase();

			return Tag.render`
		<div class="disk-file-thumb disk-file-thumb-preview" onclick="${this.onClick.bind(this)}">
			<div style="background-image: url('${this.data['PREVIEW_URL']}'); background-size: cover;" class="disk-file-thumb-image"></div>
			<div data-bx-role="icon" class="ui-icon ui-icon-file-${extension} disk-file-thumb-icon"><i></i></div>
			<div data-bx-role="name" class="disk-file-thumb-text">${name}</div>
			<div class="disk-file-thumb-btn-box">
				<div class="disk-file-thumb-btn-close" onclick="${this.onClickDelete.bind(this)}"></div>
				<div class="disk-file-thumb-btn-more" data-bx-role="more" onclick="${this.onClickMore.bind(this)}"></div>
			</div>
		</div>`;
		});
	}

	getHTMLForHTMLEditor(tagId: String)
	{
		return `<img style="max-width: 90%;" data-bx-file-id="${this.data.ID}" id="${tagId}" src="${this.data.BIG_REVIEW_URL}" />`
	}

	static detect(itemData: ItemSavedType)
	{
		return !!itemData['PREVIEW_URL'];
	}
}
