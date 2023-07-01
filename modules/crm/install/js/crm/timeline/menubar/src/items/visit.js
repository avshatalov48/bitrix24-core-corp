import Item from '../item';

export default class Visit extends Item
{
	showSlider(): void
	{
		const visitParameters = this.getSettings() ?? {};
		visitParameters['OWNER_TYPE'] = BX.CrmEntityType.resolveName(this.getEntityTypeId());
		visitParameters['OWNER_ID'] = this.getEntityId();

		BX.CrmActivityVisit.create(visitParameters).showEdit();
	}

	supportsLayout(): Boolean
	{
		return false;
	}
}
