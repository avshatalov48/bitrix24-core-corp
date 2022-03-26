;(function()
{
	'use strict';
	BX.namespace('BX.Salescenter');

	BX.Salescenter.Context = {
		sms: 'sms',
		chat: 'chat',
	};

	BX.Salescenter.Payments = {
		gridId: 'CRM_ORDER_PAYMENT_LIST_V12',
		sessionId: 0,
		context: '',
	};

	BX.Salescenter.Payments.init = function(params)
	{
		if (params.sessionId > 0)
		{
			BX.Salescenter.Payments.sessionId = params.sessionId;
		}
		if (params.gridId)
		{
			BX.Salescenter.Payments.gridId = params.gridId;
		}
		if (params.context)
		{
			BX.Salescenter.Payments.context = params.context;
		}
		this.isPaymentsLimitReached = (params.isPaymentsLimitReached === true);
	};

	BX.Salescenter.Payments.getGrid = function()
	{
		var grid = BX.Main.gridManager.getById(BX.Salescenter.Payments.gridId);
		if (grid)
		{
			return grid.instance;
		}

		return false;
	};

	BX.Salescenter.Payments.sendGridPayments = function()
	{
		if (BX.Salescenter.Payments.isPaymentsLimitReached)
		{
			BX.Salescenter.Payments.showFeaturePopup();
		}

		if (BX.Salescenter.Payments.context === BX.Salescenter.Context.chat && BX.Salescenter.Payments.sessionId <= 0)
		{
			return;
		}

		var grid = BX.Salescenter.Payments.getGrid();
		if (!grid)
		{
			return;
		}

		var paymentIds = grid.getRows().getSelectedIds();
		if (!paymentIds || paymentIds.length === 0)
		{
			return;
		}

		BX.ajax.runAction('salescenter.order.sendPayments', {
			analyticsLabel: 'salescenterSendPayments',
			data: {
				paymentIds: paymentIds,
				options: {
					context: BX.Salescenter.Payments.context,
					sessionId: BX.Salescenter.Payments.sessionId,
				}
			}
		}).then(function(response)
		{
			var payments = response.data.payments;
			if (payments && payments.length > 0)
			{
				if (BX.SidePanel)
				{
					var slider = BX.SidePanel.Instance.getSliderByWindow(window);
					if (slider)
					{
						var previousSlider = BX.SidePanel.Instance.getPreviousSlider(slider);
						if (previousSlider)
						{
							if (BX.Salescenter.Payments.context === BX.Salescenter.Context.sms && response.data.paymentTitle)
							{
								previousSlider.getData().set('action', 'sendPayment');
								previousSlider.getData().set('order', {title: response.data.paymentTitle});
							}
							previousSlider.close();
						}
						slider.destroy();
					}
				}
				for (var i in payments)
				{
					if (payments.hasOwnProperty(i))
					{
						BX.UI.Notification.Center.notify({
							content: BX.message('SPL_TEMPLETE_SALESCENTER_PAYMENT_SENT_NOTIFICATION').replace('#PAYMENT_ID#', payments[i]),
						});
					}
				}
			}
		});
	};

	BX.Salescenter.Payments.highlightOrder = function(orderId)
	{
		if (orderId > 0)
		{
			var grid = BX.Salescenter.Payments.getGrid();
			if (grid)
			{
				var newRow = grid.getRows().getById(orderId);
				if (newRow)
				{
					newRow.select();
				}
			}
		}
	};

	BX.Salescenter.Payments.showFeaturePopup = function()
	{
		B24.licenseInfoPopup.show('salescenterPaymentsLimit', BX.message('SPL_ORDERS_LIMITS_TITLE'), BX.message('SPL_ORDERS_LIMITS_MESSAGE'), true);
	};

})(window);