import Item from './item';
import {LegacyPopup, SharingControlType} from "disk.sharing-legacy-popup";

export default class ItemSharing extends Item
{
	constructor(trackedObjectId, itemData)
	{
		super(trackedObjectId, itemData);

		const object = {
			id: itemData['dataset']['objectId'],
			name: itemData['dataset']['objectName'],
		}

		this.data['onclick'] = () => {
			this.emit('close');

			switch (this.data['dataset']['type'])
			{
				case SharingControlType.WITH_CHANGE_RIGHTS:
					(new LegacyPopup()).showSharingDetailWithChangeRights({
						object: object
					});
					break;
				case SharingControlType.WITH_SHARING:
					(new LegacyPopup()).showSharingDetailWithChangeRights({
						object: object
					});
					break;
				case SharingControlType.WITHOUT_EDIT:
					(new LegacyPopup()).showSharingDetailWithoutEdit({
						object: object
					});
					break;
			}
		}
	}

	static detect(itemData)
	{
		return itemData['id'] === 'sharing';
	}
}

