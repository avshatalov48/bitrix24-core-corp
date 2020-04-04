
function invoice_delete(title, message, btnTitle, path)
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