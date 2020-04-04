
function CrmPermRoleShowRow(el)
{
	el.className = (el.className == 'crmPermRoleTreePlus' ? 'crmPermRoleTreeMinus' : 'crmPermRoleTreePlus');
	var parentTr = BX.findParent(el, {tag:'tr'});

	while(parentTr.nextElementSibling.nodeName == 'TR')
	{
		if (parentTr.nextElementSibling.className == 'crmPermRoleFields')
		{
			parentTr.nextElementSibling.style.display = (parentTr.nextElementSibling.style.display == 'none' ? '' : 'none');
			parentTr = parentTr.nextElementSibling;
		}
		else
			break;
	}
}

function CrmPermRoleShowBox(id, parent_id)
{
	BX(id).style.display = 'none';
	BX(id + '_Select').style.display = '';
	BX(id + '_SelectBox').focus();
	BX.bind(BX(id + '_SelectBox'), 'change', function(){
		BX(id).className = 'divPermsBoxText';
		if (BX(id+'_SelectBox').options[BX(id+'_SelectBox').selectedIndex].value != '-')
			BX(id).innerHTML = BX(id+'_SelectBox').options[BX(id+'_SelectBox').selectedIndex].text;
		else 
		{
			BX(id).innerHTML = BX(parent_id+'_SelectBox').options[BX(parent_id+'_SelectBox').selectedIndex].text;
			BX(id).className += ' divPermsBoxTextGray';
		}
		BX(id).style.display = 'inline-block';
		BX(id+'_Select').style.display = 'none';
	});	
	BX.bind(BX(id+'_SelectBox'), 'blur', function(){
		BX(id).style.display = 'inline-block';
		BX(id+'_Select').style.display = 'none';
	});	
}

function CrmRoleDelete(title, message, btnTitle, path)
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