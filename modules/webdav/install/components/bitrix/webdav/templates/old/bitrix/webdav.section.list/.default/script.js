function EditDocWithProgID(file)
{
	if(!(document.attachEvent && !(navigator.userAgent.toLowerCase().indexOf('opera') != -1)))
	{
		return true;
	}
	
	try
	{
		var EditDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
		if (EditDocumentButton)
		{
			var url = location.protocol + "//" + location.host + file;
			if(EditDocumentButton.EditDocument2(window, url))
			{
				return false;
			}
		}
	}
	catch(e)
	{
		
	}
	return true;
}
window.wdChangeSelectPosition = function(oObj, bResult)
{
	if (typeof oObj != "object" || oObj == null)
		return;
	bResult = (bResult == true ? true : false);
	if (window.wdChangeSelectPosition.prototype.selector == 'undefined')
	{
		window.wdChangeSelectPosition.prototype.selector = false;
		var items = oObj.form.getElementsByTagName('input');
		if (items)
		{
			if (!items.length || (typeof(items.length) == 'undefined'))
			{
				items = [items];
			}
			var data = {'elements' : [], 'manage' : {'top' : false, 'bottom' : false}, 'count' : 0, 'checked' : 0};
			for (var ii = 0; ii < items.length; ii++)
			{
				if (items[ii].type != "checkbox")
					continue;
				else if (items[ii].name == 'ELEMENTS_ALL[TOP]')
					data['manage']['top'] = items[ii]; 
				else if (items[ii].name == 'ELEMENTS_ALL[BOTTOM]')
					data['manage']['bottom'] = items[ii]; 
				else if (items[ii].name.substr(0, 9) == 'ELEMENTS[')
				{
					data['elements'].push(items[ii]); 
					data['count']++;
					if (items[ii].checked && oObj.value != items[ii].value)
					{
						data['checked']++;
					}
					else if (!items[ii].checked && oObj.value == items[ii].value)
					{
						data['checked']++;
					}
				}
			}
			if (data['count'] > 0)
				window.wdChangeSelectPosition.prototype.selector = data;
		}
	}
	if (window.wdChangeSelectPosition.prototype.selector == false)
	{
		return (bResult ? 0 : false);
	}
	var data = window.wdChangeSelectPosition.prototype.selector
	if (bResult)
	{
		return data['checked'];
	}
	if (oObj.name == 'ELEMENTS_ALL[TOP]' || oObj.name == 'ELEMENTS_ALL[BOTTOM]')
	{
		for (var ii = 0; ii < data["elements"].length; ii++)
		{
			data["elements"][ii].checked = oObj.checked;
		}
		data['manage']['top'].checked = oObj.checked;
		data['manage']['bottom'].checked = oObj.checked;
		data['checked'] = ( oObj.checked ? data['count'] : 0);
	}	
	else if (oObj.checked)
	{
		data['checked']++;
	}
	else
	{
		data['checked']--;
	}
	data['manage']['top'].checked = (data['checked'] == data['count']);
	data['manage']['bottom'].checked = (data['checked'] == data['count']);
	window.wdChangeSelectPosition.prototype.selector = data;
	wdChangeAction(oObj);
}
window.wdChangeSelectPosition.prototype.selector = 'undefined';