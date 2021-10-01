import Item from './item';
import {ExternalLinkForTrackedObject} from 'disk.external-link';
export default class ItemExternalLink extends Item
{
	constructor(objectId, itemData)
	{
		super(objectId, itemData);

		const shouldBlockFeature = itemData['dataset']['shouldBlockFeature']
		const blocker = itemData['dataset']['blocker'];

		this.data['onclick'] = function() {
			this.emit('close');

			if (shouldBlockFeature && blocker)
			{
				eval(blocker);

				return;
			}

			ExternalLinkForTrackedObject.showPopup(this.trackedObjectId);
		}.bind(this);
	}

	static detect(itemData)
	{
		return itemData['id'] === 'externalLink';
	}
}