import Item from '../item';

export default class Email extends Item
{
	showSlider(): void
	{
		const ownerInfo = BX.CrmTimelineManager.getDefault().getOwnerInfo();

		BX.CrmActivityEditor.getDefault().addEmail(
		{
			'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
			'ownerID': this.getEntityId(),
			'ownerUrl': ownerInfo['SHOW_URL'],
			'ownerTitle': ownerInfo['TITLE'],
			'subject': '',
		}
	);
	}

	supportsLayout(): Boolean
	{
		return false;
	}
}
