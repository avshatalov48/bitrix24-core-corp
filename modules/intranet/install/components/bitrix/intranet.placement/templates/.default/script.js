;(function ()
{
	BX.namespace('BX.Intranet.Placement');
	if (!BX.Intranet.Placement)
	{
		return;
	}

	function Placement()
	{
	}

	Placement.prototype =
		{
			init: function (params)
			{
				this.containerId = params.containerId;
				this.placementCode = params.placementCode;
				this.maxLoadDelay = params.maxLoadDelay;
				this.serviceUrl = params.serviceUrl;
				this.items = params.items;
				this.signedParameters = params.signedParameters;
				this.loadPlacement();
			},

			loadPlacement: function()
			{
				for (let i = 0; i < this.items.length; i++)
				{
					BX.ajax(
						{
							url: this.serviceUrl,
							method: 'POST',
							dataType: 'html',
							data:
								{
									'PARAMS': {
										'params' : {
											'ID': this.items[i].APP_ID,
											'PLACEMENT': this.items[i].PLACEMENT,
											'PLACEMENT_ID': this.items[i].PLACEMENT_ID,
											'PLACEMENT_OPTIONS': this.items[i].PLACEMENT_OPTIONS
										}
									}
								},
							onsuccess: function(response)
								{
									this.appRequestOnSuccess(response, this.items[i])
								}.bind(this),
						}
					);
				}
			},

			appRequestOnSuccess: function(response, item)
			{
				BX.append(
					BX.create(
						'div',
						{
							props: {
								id: item.ID,
							},
							html: response
						}
					),
					BX(this.containerId)
				);
				this.checkLoadTiming(item, Date.now());
			},

			checkLoadTiming: function(item, time)
			{
				BX.findChild(
					BX(item.ID),
					{
						'tag': 'iframe',
					},
					true
				).onload = function()
				{
					if (this.maxLoadDelay < (Date.now() - time))
					{
						this.sendAjax(
							'setLongLoad',
						{
								item: item,
							}
						);
					}
				}.bind(this);
			},

			sendAjax: function (action, data)
			{
				BX.ajax.runComponentAction(
					'bitrix:intranet.placement',
					action,
					{
						mode: 'class',
						signedParameters: this.signedParameters,
						data: data
					}
				).then(
					function (response)
					{
					}
				);
			}
		};

	BX.Intranet.Placement = Placement;

})(window);