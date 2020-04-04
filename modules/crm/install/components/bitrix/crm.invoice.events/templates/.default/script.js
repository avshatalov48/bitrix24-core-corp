function crm_event_desc(iid)
{
	BX('event_desc_short_'+iid).style.display = 'none';
	BX('event_desc_full_'+iid).style.display = 'block';
}

function crm_event_delete_grid(title, message, btnTitle, path)
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
}