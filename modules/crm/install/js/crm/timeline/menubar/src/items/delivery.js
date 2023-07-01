import Item from '../item';

export default class Delivery extends Item
{
	showSlider(): void
	{
		BX.CrmActivityEditor.getDefault().addDelivery(
		{
			'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
			'ownerID': this.getEntityId(),
			"orderList": BX.CrmTimelineManager.getDefault().getOwnerInfo()['ORDER_LIST']
		}
	);
	}
	supportsLayout(): Boolean
	{
		return false;
	}
}
