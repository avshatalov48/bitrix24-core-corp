import Item from '../item';
export default class Call extends Item
{
	showSlider(): void
	{
		const planner = new BX.Crm.Activity.Planner();
		planner.showEdit(
			{
				'TYPE_ID': BX.CrmActivityType.call,
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
