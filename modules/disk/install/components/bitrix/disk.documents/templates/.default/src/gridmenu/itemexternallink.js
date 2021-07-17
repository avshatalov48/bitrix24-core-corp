import {Type} from 'main.core';
import Item from './item';
import {ExternalLinkForTrackedObject} from 'disk.external-link';
export default class ItemExternalLink extends Item
{
	constructor(objectId, itemData)
	{
		super(objectId, itemData);

		this.data['onclick'] = function() {
			this.emit('close');

			ExternalLinkForTrackedObject.showPopup(this.trackedObjectId);

		}.bind(this);
	}

	static detect(itemData)
	{
		return itemData['id'] === 'externalLink';
	}
}

