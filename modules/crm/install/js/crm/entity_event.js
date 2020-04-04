if(typeof BX.Crm.EntityEvent === "undefined")
{
	BX.Crm.EntityEvent =
		{
			names:
				{
					create: "onCrmEntityCreate",
					update: "onCrmEntityUpdate",
					delete: "onCrmEntityDelete",
					invalidate: "onCrmEntityInvalidate"
				},
			fireCreate: function(entityTypeId, entityId, context, additionalParams)
			{
				this.fire(BX.Crm.EntityEvent.names.create, entityTypeId, entityId, context, additionalParams);
			},
			fireUpdate: function(entityTypeId, entityId, context, additionalParams)
			{
				this.fire(BX.Crm.EntityEvent.names.update, entityTypeId, entityId, context, additionalParams);
			},
			fireDelete: function(entityTypeId, entityId, context, additionalParams)
			{
				this.fire(BX.Crm.EntityEvent.names.delete, entityTypeId, entityId, context, additionalParams);
			},
			fire: function(eventName, entityTypeId, entityId, context, additionalParams)
			{
				var params =
					{
						entityTypeId: entityTypeId,
						entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
						entityId: entityId,
						context: context
					};

				if(BX.type.isPlainObject(additionalParams))
				{
					params = BX.mergeEx(params, additionalParams);
				}

				BX.localStorage.set(eventName, params, 10);
			}
		};
}