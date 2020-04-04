function WDDrop(oAnchor)
{
	var sSaveLocation = oAnchor; 
	if (oAnchor && typeof(oAnchor) == "object")
		sSaveLocation = oAnchor.href;
	WDConfirm(oText['delete_title'], oText['message01'], function() {jsUtils.Redirect({}, sSaveLocation)} );
}
function WDAddElement(oAnchor)
{
	var sSaveLocation = oAnchor; 
	if (oAnchor && typeof(oAnchor) == "object")
		sSaveLocation = oAnchor.href;

    var sTemplate = location.protocol + "//" + location.host + '/bitrix/components/bitrix/webdav.menu/template.doc';
    
	try	{
		var AddDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
		if (!AddDocumentButton.CreateNewDocument2(window, sTemplate, sSaveLocation))
		{
			alert(oText['error_create_1']);
		}
		
		AddDocumentButton.PromptedOnLastOpen();
		SetWindowRefreshFocus();
		return;
	} catch (e) { }
	
	try {
		AddDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.1");
		window.onfocus = null;
		
		if (!AddDocumentButton.CreateNewDocument(sTemplate, sSaveLocation))
		{
			alert(oText['error_create_1']);
		}
		
		SetWindowRefreshFocus();
		return;
	} catch (e) { alert(oText['error_create_2']); }
}
function SetWindowRefreshFocus()
{
	window.onfocus = new Function("window.location.href=window.location;");
}
