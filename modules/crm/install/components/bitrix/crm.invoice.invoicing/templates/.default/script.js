BX.Invoicing = {
	init : function (options)
	{
		this.ajaxUrl = options.ajaxUrl;
	},

	getMovementList: function (requestId)
	{
		var params = {};

		params.sessid = BX.bitrix_sessid();

		var paySystem = BX('PAY_SYSTEM');
		if (paySystem)
			params.PAY_SYSTEM_ID = paySystem.value;

		var invoicingType = BX('INVOICING_TYPE');
		if (invoicingType)
			params.MODE = invoicingType.value;

		if (typeof requestId === 'undefined')
		{
			BX.showWait();

			var dateStart = BX('DATE_START');
			var dateEnd = BX('DATE_END');

			if (dateStart)
				params.DATE_START = dateStart.value;
			if (dateEnd)
				params.DATE_END = dateEnd.value;
		}
		else
		{
			params.requestId = requestId;
		}

		BX.ajax(
		{
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: params,
			onsuccess: BX.proxy(function(result)
			{
				if (result.hasOwnProperty('ERRORS'))
				{
					BX.closeWait();
					alert(result.ERRORS);
				}
				else
				{
					if (result.hasOwnProperty('TIME'))
					{
						var time = parseInt(result.TIME) * 1000;
						setTimeout(
							BX.proxy(this.getMovementList(result.REQUEST_ID), this),
							time
						);
					}
					else if (result.hasOwnProperty('GRID'))
					{
						BX.closeWait();
						var container = BX('bx-crm-edit-form-wrapper');
						if (container)
						{
							var data = BX.processHTML(result.GRID);
							container.innerHTML = data['HTML'];
							for (var i in data['SCRIPT'])
							{
								if (data['SCRIPT'].hasOwnProperty(i))
									BX.evalGlobal(data['SCRIPT'][i]['JS']);
							}
						}
					}
					else
					{
						alert(BX.message('CRM_RESULT_NO_FOUND'));
						BX.closeWait();
					}
				}
			}, this),
			onfailure: function()
			{
				BX.closeWait();
				console.log('BX.Invoicing.getMovementList');
			}
		}, this);
	}
};
