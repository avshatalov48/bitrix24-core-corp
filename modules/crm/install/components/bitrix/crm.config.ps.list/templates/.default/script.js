BX.namespace('BX.Crm.PaySystemList');

BX.Crm.PaySystemList =
{
	ajaxUrl : '',

	init : function (options)
	{	
		this.ajaxUrl = options.ajaxUrl
	},

	activate : function(paySystemId)
	{
		var currentStatus = BX('current_status_'+paySystemId);
		var postData = {
			paySystemId : paySystemId,
			sessid : BX.bitrix_sessid(),
			action : 'active',
			status : (currentStatus.value == 'Y') ? 'N' : 'Y'
		};

		BX.ajax({
			timeout: 30,
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: postData,

			onsuccess: function (result)
			{
				var className = '';
				var titleOnClassName = '';
				var titleOffClassName = '';

				var activeObj = BX('active_off_'+paySystemId);
				if (activeObj.className == 'crm-config-ps-list-not-activate-button')
				{
					className = 'crm-config-ps-list-activate-button crm-config-ps-off';
					titleOnClassName = 'crm-config-ps-list-activate-button-item-off';
					titleOffClassName = 'crm-config-ps-list-activate-button-item-on';
				}
				else
				{
					className = 'crm-config-ps-list-not-activate-button';
					titleOnClassName = 'crm-config-ps-list-activate-button-item-on';
					titleOffClassName = 'crm-config-ps-list-activate-button-item-off';
				}

				activeObj.className = className;
				BX('active_title_on_'+paySystemId).className = titleOnClassName;
				BX('active_title_off_'+paySystemId).className = titleOffClassName;

				if (result && result.hasOwnProperty('ERROR'))
					alert(result.ERROR);

				currentStatus = BX('current_status_'+paySystemId);
				if (currentStatus)
					currentStatus.value = (currentStatus.value == 'Y') ? 'N' : 'Y';
			},

			onfailure: function () {}
		});
	},
	
	delete : function (paySystemId)
	{
		if (confirm(BX.message('CRM_PS_DELETE_CONFIRM')))
		{

			var postData = {
				paySystemId: paySystemId,
				sessid: BX.bitrix_sessid(),
				action: 'delete'
			};

			BX.ajax({
				timeout: 30,
				method: 'POST',
				dataType: 'json',
				url: this.ajaxUrl,
				data: postData,

				onsuccess: function (result)
				{
					BX.toggleClass(BX('close-row-' + paySystemId), 'crm-webform-row-close');
					CloseWaitWindow();
				},

				onfailure: function ()
				{
				}
			});
		}
	}
};
