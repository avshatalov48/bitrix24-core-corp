import Item from './item';

export default class ItemHistory extends Item
{
	constructor(trackedObjectId, itemData)
	{
		super(trackedObjectId, itemData);

		this.object = {
			id: itemData.dataset.objectId,
			fileHistoryUrl: itemData.dataset.fileHistoryUrl,
			name: itemData.dataset.objectName,
			blockedByFeature: itemData.dataset.blockedByFeature,
		};

		this.data.onclick = this.handleClick.bind(this);
	}

	handleClick()
	{
		this.emit('close');

		if (this.object.blockedByFeature)
		{
			top.BX.UI.InfoHelper.show('limit_office_version_storage');

			return;
		}

		const fileHistoryUrl = this.object.fileHistoryUrl;
		BX.SidePanel.Instance.open(fileHistoryUrl, {
			cacheable: false,
			allowChangeHistory: false,
		});
	}

	static detect(itemData): boolean
	{
		return itemData.id === 'history';
	}
}
