import Editor from "../editor";

/** @memberof BX.Crm.Timeline.Editors */
export default class Rest extends Editor
{
	constructor()
	{
		super();
		this._interfaceInitialized = false;
	}

	action(action)
	{
		if(!this._interfaceInitialized)
		{
			this._interfaceInitialized = true;
			this.initializeInterface();
		}

		if(action === 'activity_rest_applist')
		{
			BX.rest.Marketplace.open({
				PLACEMENT: this.getSetting("placement", '')
			});

			top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', BX.proxy(this.fireUpdateEvent, this));
		}
		else
		{
			const appId = action.replace('activity_rest_', '');
			const appData = appId.split('_');

			BX.rest.AppLayout.openApplication(
				appData[0],
				{
					ID: this._ownerId
				},
				{
					PLACEMENT: this.getSetting("placement", ''),
					PLACEMENT_ID: appData[1]
				}
			);
		}
	}

	initializeInterface()
	{
		if(!!top.BX.rest && !!top.BX.rest.AppLayout)
		{
			const entityTypeId = this._manager._ownerTypeId, entityId = this._manager._ownerId;

			const PlacementInterface = top.BX.rest.AppLayout.initializePlacement(this.getSetting("placement", ''));

			PlacementInterface.prototype.reloadData = function(params, cb)
			{
				BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
				cb();
			};
		}
	}

	fireUpdateEvent()
	{
		const entityTypeId = this._manager._ownerTypeId, entityId = this._manager._ownerId;
		setTimeout(function(){
			console.log('fireUpdate', entityId, entityTypeId);
			BX.Crm.EntityEvent.fire(BX.Crm.EntityEvent.names.invalidate, entityTypeId, entityId, '');
		}, 3000);
	}

	static create(id, settings)
	{
		const self = new Rest();
		self.initialize(id, settings);
		Rest.items[self.getId()] = self;
		return self;
	}

	static items = {};
}
