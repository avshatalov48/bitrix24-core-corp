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