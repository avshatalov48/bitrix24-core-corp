;(function()
{
	'use strict';
	BX.namespace('BX.Crm.Lead');

	if (BX.Crm.Lead['Menu'])
	{
		return false;
	}
	BX.Crm.Lead.Menu = {
		onClickDelete: function(title, message, btnTitle, path)
		{
			var d;
			d = new BX.CDialog({
				title: title,
				head: '',
				content: message,
				resizable: false,
				draggable: true,
				height: 70,
				width: 300
			});

			var _BTN = [
				{
					title: btnTitle,
					id: 'crmOk',
					'action': function ()
					{
						window.location.href = path;
						BX.WindowManager.Get().Close();
					}
				},
				BX.CDialog.btnCancel
			];
			d.ClearButtons();
			d.SetButtons(_BTN);
			d.Show();
		},
		onClickConfigStatuses: function(url)
		{
			BX.SidePanel.Instance.open(url, {
				cacheable: false,
				events: {
					onClose: function()
					{
						var localBX = this.getFrameWindow().BX;
						if (localBX
							&& localBX.Crm
							&& localBX.Crm.SalesTunnels
							&& localBX.Crm.SalesTunnels.Manager
							&& localBX.Crm.SalesTunnels.Manager.getLastInstance
							&& localBX.Crm.SalesTunnels.Manager.getLastInstance().isChanged)
						{
							this.reload();
							if (window.top.BX.Main && window.top.BX.Main.filterManager)
							{
								var data = window.top.BX.Main.filterManager.data;
								Object.values(data).forEach(function(filter) { filter._onFindButtonClick(); });
							}
						}
					}
				}
			});
		}
	};
})();
