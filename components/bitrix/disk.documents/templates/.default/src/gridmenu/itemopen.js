import {Type} from 'main.core';
import Item from './item';
import Backend from '../backend';

export default class ItemOpen extends Item
{
	constructor(objectId, itemData)
	{
		super(objectId, itemData);
		this.data['dataset'] = (this.data['dataset'] || {});
		this.data['dataset']['preventCloseContextMenu'] = true;
		this.data['onclick'] = function() {
			if (this.data['href'])
			{
				return this.open();
			}
			this.showLoad();
			Backend
				.getMenuOpenAction(this.objectId)
				.then(({data}) => {
					this.hideLoad();
					this.data['href'] = data;
					this.open();
				})
				.catch(({errors}) => {
					this.hideLoad();
					this.showError(errors);
				})
		}.bind(this);
	}

	open()
	{
		if (Type.isStringFilled(this.data['href']))
		{
			if (!this.data['target'])
			{
				BX.SidePanel.Instance.open(this.data['href']);
			}
			this.emit('close');
		}
		else
		{
			this.showError([{text: 'Empty href'}])
		}
	}

	static detect(itemData)
	{
		return itemData['id'] === 'open';
	}
}

