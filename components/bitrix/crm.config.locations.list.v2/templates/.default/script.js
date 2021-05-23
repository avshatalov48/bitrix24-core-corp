BX.crmLocationListTools = function(){
	this.deleteGrid = function(title, message, btnTitle, path)
	{
		var d =
			new BX.CDialog(
				{
					title: title,
					head: '',
					content: message,
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

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

	this.viewSubtree = function(itemId)
	{
		jsUtils.Redirect([], '?PARENT_ID='+parseInt(itemId));
	}

	this.editItem = function(editUrl, listUrl)
	{
		jsUtils.Redirect([], editUrl+'?return_url='+encodeURIComponent(listUrl));
	}

	BX.addCustomEvent(document, 'bx-ui-crm-filter-set-active-item', function(){
		if(typeof BX.locationSelectors != 'undefined' && BX.locationSelectors['crm_filter_parent_id'] != 'undefined')
			BX.locationSelectors['crm_filter_parent_id'].cancelRequest();
	});
}