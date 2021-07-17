import {Type} from 'main.core';
import Item from './item';
import 'clipboard';

export default class ItemShareSection extends Item
{
	constructor(objectId, itemData)
	{
		super(objectId, itemData);
		this.data['dataset'] = (this.data['dataset'] || {});
		this.data['dataset']['preventCloseContextMenu'] = true;
	}

	static detect(itemData)
	{
		return itemData['id'] === 'share-section';
	}
}

