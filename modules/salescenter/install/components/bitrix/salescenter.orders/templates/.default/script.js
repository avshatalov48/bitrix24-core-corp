;(function()
{
	'use strict';
	BX.namespace('BX.Salescenter');

	BX.Salescenter.Context = {
		sms: 'sms',
		chat: 'chat',
	};

	BX.Salescenter.Orders = {
		gridId: 'CRM_ORDER_LIST_V12',
		sessionId: 0,
		context: '',
	};

	BX.Salescenter.Orders.init = function(params)
	{
		if (params.sessionId > 0)
		{
			BX.Salescenter.Orders.sessionId = params.sessionId;
		}
		if (params.gridId)
		{
			BX.Salescenter.Orders.gridId = params.gridId;
		}
		if (params.context)
		{
			BX.Salescenter.Orders.context = params.context;
		}
		this.isPaymentsLimitReached = (params.isPaymentsLimitReached === true);

		BX.Salescenter.Orders.initEvents();
	};

	BX.Salescenter.Orders.getGrid = function()
	{
		var grid = BX.Main.gridManager.getById(BX.Salescenter.Orders.gridId);
		if(grid)
		{
			return grid.instance;
		}

		return false;
	};

	BX.Salescenter.Orders.sendGridOrders = function()
	{
		if(BX.Salescenter.Orders.isPaymentsLimitReached)
		{
			BX.Salescenter.Orders.showFeaturePopup();
		}
		if(BX.Salescenter.Orders.context === BX.Salescenter.Context.chat && BX.Salescenter.Orders.sessionId <= 0)
		{
			return;
		}
		var grid = BX.Salescenter.Orders.getGrid();
		if(!grid)
		{
			return;
		}
		var orderIds = grid.getRows().getSelectedIds();
		if(!orderIds || orderIds.length === 0)
		{
			return;
		}
		BX.ajax.runAction('salescenter.order.sendOrders', {
			analyticsLabel: 'salescenterSendOrders',
			data: {
				orderIds: orderIds,
				options: {
					context: BX.Salescenter.Orders.context,
					sessionId: BX.Salescenter.Orders.sessionId,
				}
			}
		}).then(function(response)
		{
			var orders = response.data.orders;
			if(orders && orders.length > 0)
			{
				if(BX.SidePanel)
				{
					var slider = BX.SidePanel.Instance.getSliderByWindow(window);
					if(slider)
					{
						var previousSlider = BX.SidePanel.Instance.getPreviousSlider(slider);
						if(previousSlider)
						{
							if (BX.Salescenter.Orders.context === BX.Salescenter.Context.sms && response.data.orderTitle)
							{
								previousSlider.getData().set('action', 'sendPayment');
								previousSlider.getData().set('order', {title: response.data.orderTitle});
							}
							previousSlider.close();
						}
						slider.destroy();
					}
				}
				for(var i in orders)
				{
					if(orders.hasOwnProperty(i))
					{
						BX.UI.Notification.Center.notify({
							content: BX.message('SALESCENTER_ORDER_SENT_NOTIFICATION').replace('#ORDER_ID#', orders[i]),
						});
					}
				}
			}
		});
	};

	BX.Salescenter.Orders.highlightOrder = function(orderId)
	{
		if(orderId > 0)
		{
			var grid = BX.Salescenter.Orders.getGrid();
			if(grid)
			{
				var newRow = grid.getRows().getById(orderId);
				if(newRow)
				{
					newRow.select();
				}
			}
		}
	};

	BX.Salescenter.Orders.initEvents = function()
	{
		top.BX.addCustomEvent(window, 'salescenter-order-create', function(data)
		{
			var grid = BX.Salescenter.Orders.getGrid();
			if(grid)
			{
				grid.reloadTable('GET', {}, function()
				{
					BX.Salescenter.Orders.highlightOrder(data.orderId);
				});
			}
		});
	};

	BX.Salescenter.Orders.showFeaturePopup = function()
	{
		B24.licenseInfoPopup.show('salescenterPaymentsLimit', BX.message('SALESCENTER_ORDERS_LIMITS_TITLE'), BX.message('SALESCENTER_ORDERS_LIMITS_MESSAGE'), true);
	};

})(window);