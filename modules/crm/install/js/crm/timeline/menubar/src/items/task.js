import Item from '../item';

export default class Task extends Item
{
	showSlider(): void
	{
		BX.CrmActivityEditor.getDefault().addTask(
			{
				'ownerType': BX.CrmEntityType.resolveName(this.getEntityTypeId()),
				'ownerID': this.getEntityId(),
			}
		);
	}

	supportsLayout(): Boolean
	{
		return false;
	}
}
