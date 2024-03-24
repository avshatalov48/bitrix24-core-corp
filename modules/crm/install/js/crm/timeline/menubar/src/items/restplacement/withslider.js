import Base from './base.js';

export default class WithSlider extends Base
{
	#interfaceInitialized: Boolean = false;

	showSlider(): void
	{
		if (!this.#interfaceInitialized)
		{
			this.#interfaceInitialized = true;
			this.#initializeInterface();
		}

		const appId = this.getSetting('appId', '');

		BX.rest.AppLayout.openApplication(
			appId,
			{
				ID: this.getEntityId(),
			},
			{
				PLACEMENT: this.getSetting('placement', ''),
				PLACEMENT_ID: this.getSetting('placementId', ''),
			},
		);
	}

	supportsLayout(): Boolean
	{
		return false;
	}

	#initializeInterface()
	{
		if (top.BX.rest?.AppLayout)
		{
			const PlacementInterface = top.BX.rest.AppLayout.initializePlacement(this.getSetting('placement', ''));

			if (!PlacementInterface.prototype.reloadData)
			{
				const entityTypeId = this.getEntityTypeId();
				const entityId = this.getEntityId();

				PlacementInterface.prototype.reloadData = function(params, cb)
				{
					BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
					cb();
				};
			}
		}
	}
}
