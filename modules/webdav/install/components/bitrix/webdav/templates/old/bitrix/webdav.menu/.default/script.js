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
	catch(e){}
	return true;
}
function WDAddElement(oAnchor)
{
	if (!oAnchor || typeof(oAnchor) != "object")
		return true;

    var sTemplate = location.protocol + "//" + location.host + '/bitrix/components/bitrix/webdav.menu/template.doc';
    var sSaveLocation = oAnchor.href;
	try
	{
		var AddDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
		if (!AddDocumentButton.CreateNewDocument2(window, sTemplate, sSaveLocation))
		{
			alert(oText['error_create_1']);
		}
		
		AddDocumentButton.PromptedOnLastOpen();
		SetWindowRefreshFocus();
		return;
	}
	catch (e)
	{
	}
	
	try
	{
		AddDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.1");
		window.onfocus = null;
		
		if (!AddDocumentButton.CreateNewDocument(sTemplate, sSaveLocation))
		{
			alert(oText['error_create_1']);
		}
		
		SetWindowRefreshFocus();
		return;
	}
	catch (e)
	{
		alert(oText['error_create_2']);
	}
}
function SetWindowRefreshFocus()
{
	window.onfocus = new Function("window.location.href=window.location;");
}

function WDAddSection(oAnchor)
{
	if (!oAnchor || typeof(oAnchor) != "object")
		return true;

	CPHttpRequest1 = new JCPHttpRequest();
	CPHttpRequest1._SetHandler = function(TID, httpRequest)
	{
		var _this = this;

		function __handlerReadyStateChange()
		{
			if(httpRequest.readyState == 4)
			{
				_this._OnDataReady(TID, httpRequest.responseText);
				_this._Close(TID, httpRequest);
			}
		}

		httpRequest.onreadystatechange = __handlerReadyStateChange;
	}

	TID = CPHttpRequest1.InitThread();
	CPHttpRequest1.SetAction(TID, function(data){
		var div = document.createElement("DIV");
		div.id = "wd_section_edit";
		div.style.visible = 'hidden';
		div.className = "wd-popup";
		div.style.position = 'absolute';
		div.innerHTML = data;
		
		var scripts = div.getElementsByTagName('script');
	    for (var i = 0; i < scripts.length; i++)
	    {
	        var thisScript = scripts[i];
	        var text;
	        var sSrc = thisScript.src.replace(/http\:\/\/[^\/]+\//gi, '');
	        if (thisScript.src && sSrc != 'bitrix/js/main/utils.js' && sSrc != 'bitrix/js/main/admin_tools.js' &&
	        	sSrc != '/bitrix/js/main/utils.js' && sSrc != '/bitrix/js/main/admin_tools.js') 
	        {
	            var newScript = document.createElement("script");
	            newScript.type = 'text/javascript';
	            newScript.src = thisScript.src;
	            document.body.appendChild(newScript);
	        }
	        else if (thisScript.text || thisScript.innerHTML) 
	        {
	        	text = (thisScript.text ? thisScript.text : thisScript.innerHTML);
				text = (""+text).replace(/^\s*<!\-\-/, '').replace(/\-\->\s*$/, '');
	            eval(text);
	        }
	    }
	    
    	data = data.replace(/\<script([^\>])*\>([^\<]*)\<\/script\>/gi, '');
    	div.innerHTML = data;
	    document.body.appendChild(div);
		WDMenu.PopupShow(div);
		CloseWaitWindow();
	});
	
	ShowWaitWindow();
	CPHttpRequest1.Send(TID, oAnchor.href, {"use_light_view" : "y", "AJAX_CALL" : "Y"});
	return false;
}

function WDOnSubmitForm(form)
{
	if (typeof form != "object")
		return false;
	oData = {"AJAX_CALL" : "Y"};
	for (var ii in form.elements)
	{
		if (form.elements[ii] && form.elements[ii].name)
		{
			oData[form.elements[ii].name] = form.elements[ii].value;
		}
	}
	
	TID = CPHttpRequest.InitThread();
	CPHttpRequest.SetAction(TID, 
		function(data)
		{
			result = {};
			try
			{
				eval("result = " + data + ";");
				if (typeof(result) != "object" || result == null)
				{
					if (document.getElementById('wd_section_edit'))
						document.getElementById('wd_section_edit').innerHTML = data;
				}
				else
				{
					if (result['url'] && result['url'].length > 0)
						jsUtils.Redirect({}, result['url']);
					WDMenu.PopupHide('wd_section_edit');
				}
			}
			catch(e)
			{
				if (document.getElementById('wd_section_edit'))
					document.getElementById('wd_section_edit').innerHTML = data;
			}
			CloseWaitWindow();
		});
	
	ShowWaitWindow();
	CPHttpRequest.Post(TID, form.action, oData);
	return false;
}

function WDOnCancelForm()
{
	WDMenu.PopupHide('wd_section_edit');
	CloseWaitWindow();
}