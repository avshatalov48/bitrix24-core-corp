import Item from '../item';

export default class Meeting extends Item
{
	showSlider(): void
	{
		const planner = new BX.Crm.Activity.Planner();
		planner.showEdit(
			{
				'TYPE_ID': BX.CrmActivityType.meeting,
				'OWNER_TYPE_ID': this.getEntityTypeId(),
				'OWNER_ID': this.getEntityId(),
			}
		);
	}

	supportsLayout(): Boolean
	{
		return false;
	}
}
