import Item from '../item';

/** @memberof BX.Crm.Timeline.MenuBar */

export default class Market extends Item
{
	showSlider(): void
	{
		BX.rest.Marketplace.open({
			PLACEMENT: this.getSetting('placement', '')
		});

		top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', this.#fireUpdateEvent.bind(this));
	}

	supportsLayout(): Boolean
	{
		return false;
	}

	#fireUpdateEvent()
	{
		const entityTypeId = this.getEntityTypeId();
		const entityId = this.getEntityId();

		setTimeout(function(){
			console.log('fireUpdate', entityId, entityTypeId);
			BX.Crm.EntityEvent.fire(BX.Crm.EntityEvent.names.invalidate, entityTypeId, entityId, '');
		}, 3000);
	}
}
