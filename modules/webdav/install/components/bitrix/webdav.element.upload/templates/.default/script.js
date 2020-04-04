if (typeof oText != "object") {
	var oText = {}; }

function UploaderClass()
{
	var _this = this;

	this.Uploader = null;
	this.Thumbnail = null;
	this.iIndex = 0;
	
	this.oFields = {"Title" : "", "Tag" : "", "Description" : ""};
	this.oFiles = {};
	this.oSelectedFiles = [];
}
// Must be redefined
UploaderClass.prototype.InitDataFromForm = function(iIndex){}
UploaderClass.prototype.GetDataFromForm = function(){}
UploaderClass.prototype.ChangeFileCountPublic = function(iFileCount){}
UploaderClass.prototype.ShowDescription = function(){}
UploaderClass.prototype.HideDescription = function(){}
UploaderClass.prototype.BeforeUploadPublic = function(){}
UploaderClass.prototype.ShowUploadError = function(sText){}
UploaderClass.prototype.CheckFields = function(){return true;}
// Main functions
UploaderClass.prototype.Init = function(iIndex) {
	if (this.Uploader != null && this.Uploader)
		return true;
	iIndex = parseInt(iIndex);
	if (iIndex > 0 && this.iIndex <= 0)
	{
		this.iIndex = iIndex;
	}
//	debug_info('iIndex: ' + iIndex);
	this.InitDataFromForm(iIndex);
	this.Uploader = getImageUploader("ImageUploader" + iIndex);
	return (!this.Uploader ? false : true);
}
UploaderClass.prototype.ChangeFileCount = function() {
	if (!this.Init())
		return false;
	var guid = 0, sFileName = '', aFileName = [];
	this.FileCount = parseInt(this.Uploader.getUploadFileCount());
	
	for (var i = 1; i <= this.FileCount; i++)
	{
//		try	{
			guid = this.Uploader.getUploadFileGuid(i);
//		} catch(e){}
		if (typeof(this.oFiles[guid]) != "object" || !this.oFiles[guid] || this.oFiles[guid] == null)
		{
			sFileName = this.Uploader.getUploadFileName(i);
			sFileName = "" + (!sFileName || sFileName == 'undefined' ? 'noname' : sFileName);
			if (sFileName.search(/\\/g) > 0)
				sFileName = sFileName.replace(/\\/g, "/");
			
			if (sFileName.search(/\//g) > 0)
			{
				aFileName = sFileName.split("/");
				if (aFileName && aFileName.length > 0) {
					sFileName = aFileName[aFileName.length-1]; }
			}
			this.oFiles[guid] = {};
			for (var sFieldName in this.oFields) {
				this.oFiles[guid][sFieldName] = ""; }
			this.oFiles[guid]["Title"] = sFileName;
			this.oFiles[guid]["FileName"] = sFileName;
		}
	}
	this.ChangeFileCountPublic(this.FileCount);
}
UploaderClass.prototype.ChangeSelection = function() {
	if (!this.Init())
		return false;
	else if (!this.Thumbnail || this.Thumbnail == null)
		this.Thumbnail = getImageUploader("Thumbnail" + this.iIndex);
//	try{
	if (!this.Uploader || !this.Uploader.getUploadFileSelected || !this.Thumbnail)
		return false;
//	} catch(e){}

	this.SetDescription();
	this.HideDescription();
	this.oSelectedFiles = [];
	for (var ii = 1; ii <= this.FileCount; ii++)
	{
		try{
			if (this.Uploader.getUploadFileSelected(ii))
			{
				var tmp = this.Uploader.getUploadFileGuid(ii);
				this.oSelectedFiles.push(tmp);
			}
		}catch(e){}
	}
	if (this.oSelectedFiles.length > 0)
	{
		this.GetDescription();
		this.ShowDescription();
	}
}
UploaderClass.prototype.GetDescription = function()
{
	if (!this.Init())
		return false;
	else if (!this.Thumbnail || this.Thumbnail == null)
		this.Thumbnail = getImageUploader("Thumbnail" + this.iIndex);
	if (!this.Uploader || !this.Thumbnail || this.oSelectedFiles.length <= 0)
		return false;
	try
	{
		var oFieldsFirstFile = this.oFiles[this.oSelectedFiles[0]];
		var ii, bEmptyFields, sFieldName = '';
		for (sFieldName in this.oFields)
			this.oFields[sFieldName] = oFieldsFirstFile[sFieldName];
		for (ii = 0; ii < this.oSelectedFiles.length; ii++)
		{
			bEmptyFields = true;
			for (sFieldName in this.oFields)
			{
				if (this.oFields[sFieldName] == '')
					continue;
				if (oFieldsFirstFile[sFieldName] != this.oFiles[this.oSelectedFiles[ii]][sFieldName])
					this.oFields[sFieldName] = '';
				else
					bEmptyFields = false;
			}
			if (bEmptyFields)
			{
				break;
			}
		}
		this.Thumbnail.setGuid(this.oSelectedFiles[0]);
	}
	catch (e) {}
}
UploaderClass.prototype.SetDescription = function()
{
	if (!this.Init())
		return false;
	if (!this.Thumbnail || this.Thumbnail == null)
		this.Thumbnail = getImageUploader("Thumbnail" + this.iIndex);
	if (!this.Uploader || !this.Thumbnail)
		return false;
	var oData = this.GetDataFromForm(); // this.oFields
	for (var sFieldName in this.oFields)
	{
		this.oFields[sFieldName] = '';
	}
	for (var ii = 0; ii < this.oSelectedFiles.length; ii++)
	{
		for (var sFieldName in this.oFields)
		{
			this.oFields[sFieldName] = oData[sFieldName];
			if (sFieldName == "Title" && this.oFields[sFieldName] == '')
				continue;
			if (this.oFields[sFieldName] != '' || this.oSelectedFiles.length == 1)
				this.oFiles[this.oSelectedFiles[ii]][sFieldName] = this.oFields[sFieldName];
		}
	}

	if (!!this.Thumbnail && !!this.Thumbnail.setGuid)
		this.Thumbnail.setGuid("");
}
UploaderClass.prototype.BeforeUpload = function()
{
	if (!this.Uploader)
		return false;
	
	this.SetDescription();
	this.HideDescription();
	var iFileCount = this.Uploader.getUploadFileCount();
	
	for (var i = 1; i <= iFileCount; i++)
	{
		var guid = this.Uploader.getUploadFileGuid(i);
		for (var sFieldName in this.oFields)
		{
			if (sFieldName != "Description")
				this.Uploader.AddField(sFieldName + '_'+i, this.oFiles[guid][sFieldName]);
		}
		this.Uploader.setUploadFileDescription(i, this.oFiles[guid]["Description"]);
	}
	
	if (oParams[this.iIndex]['type'] != "ActiveX")
		this.Uploader.AddCookie(window.phpVars['COOKIES']);

	var oData = this.BeforeUploadPublic();
	for (var sFieldName in oData) {
		this.Uploader.AddField(sFieldName, oData[sFieldName]); }
	
	this.Uploader.AddField("save_upload", "Y");
	this.Uploader.AddField("AJAX_CALL", "Y");
	this.Uploader.AddField("CACHE_RESULT", "Y");
	if (oParams[this.iIndex]['type'] == "ActiveX")
		this.Uploader.AddField("CONVERT", "Y");
	
}
UploaderClass.prototype.AfterUpload = function(htmlPage)
{
	var result = {'fatal_errors' : {}, 'files' : {}}, bError = false, sError = '';
	try {
		eval("result="+htmlPage);
		if (result["fatal_errors"].length <= 0 || result["files"].length <= 0)
		{
			// check structure;
		}
	} catch(e) { result = {'fatal_errors' : {}, 'files' : {}}; }
	
	if (typeof result != "object" || result == null)
		result = {'fatal_errors' : {}, 'files' : {}};

	for (var ii in result["fatal_errors"])
	{
		bError = true;
		sError = (result["fatal_errors"][ii]['text'].length > 0 ? 
			result["fatal_errors"][ii]['text'] : result["fatal_errors"][ii]['id']) + '<br />';
	}
	if (!bError)
	{
		for (var key in result["files"])
		{
			if (result["files"][key] && result["files"][key]["status"] != "success")
			{
				bError = true;
				if (result["files"][key]["error"])
				{
					for (var ii = 0; ii < result["files"][key]["error"].length; ii++)
					{
						var File = result["files"][key]["error"][ii];
						sError += key + ': ' + (File['text'].length > 0 ? File['text'] : File['id']) + '<br />';
					}
				}
			}
		}
	}
	
	if (!bError)
		return;
	this.ShowUploadError(sError);
}
// File Uploader
FileUploaderClass = UploaderClass;
FileUploaderClass.prototype.InitDataFromForm = function(iIndex)
{
	this.index = parseInt(iIndex);
	this.form = document.getElementById('iu_upload_applet_form_' + iIndex);
	this.form_data = document.getElementById('iu_upload_form_' + iIndex);
	this.counter = document.getElementById('iu_count_to_upload_' + iIndex);
	this.button = document.getElementById('Send_' + iIndex);
	this.oFields = {"Title" : "", "Tag": "", "Description" : ""};
//	debug_info('InitDataFromForm0');
	this.HideDescription();
//	debug_info('InitDataFromForm1');
	this.ChangeFileCountPublic(0);
//	debug_info('InitDataFromForm2');
}
FileUploaderClass.prototype.GetDataFromForm = function()
{
	if (!this.form)
		return false;
	var oData = {"Title" : this.form["Title"].value, "Description" : this.form["Description"].value};
	if (this.form["Tag"]) {
		oData["Tag"] = this.form["Tag"].value; }
	return oData;
}
FileUploaderClass.prototype.ChangeFileCountPublic = function(iFileCount)
{
	_this_uploader = this;
	iFileCount = parseInt(iFileCount);
	if (iFileCount <= 0) 
	{
		if (this.counter)
			this.counter.innerHTML = window.oText["NoFiles"];
		if (this.button)
		{
			this.button.onclick = function(){return false;};
			this.button.className = "nonactive";
		}
	}
	else
	{
		if (this.counter)
			this.counter.innerHTML = iFileCount;
		if (this.button)
		{
			this.button.onclick = function(){if(_this_uploader.CheckFields()){getImageUploader('ImageUploader' + _this_uploader.index).Send()}};
			this.button.className = "";
		}
	}
	if (!this.form_data)
		return;
	for (var ii = 0; ii < this.form_data.elements.length; ii++) {
		this.form_data.elements[ii].disabled = (iFileCount <= 0 ? true : false); }
}
FileUploaderClass.prototype.ShowDescription = function()
{
	if (!this.form)
		return false;
	for (var ii in this.oFields) {
		if (this.form[ii]) {
			this.form[ii].disabled = false;
			this.form[ii].value = this.oFields[ii]; 
		} 
	}
}
FileUploaderClass.prototype.HideDescription = function()
{
	if (!this.form)
		return false;
	for (var ii in this.oFields) {
		if (this.form[ii]) {
			this.form[ii].disabled = true;
			this.form[ii].value = ''; 
		} 
	}
}
FileUploaderClass.prototype.BeforeUploadPublic = function()
{
	var oData = {};
	oData["FilesPerOnePackageCount"] = 1;
	for (var ii = 0; ii < this.form_data.elements.length; ii++)
	{
		if (this.form_data.elements[ii]["type"] == "submit" || 
			(this.form_data.elements[ii]["type"] == "checkbox" && this.form_data.elements[ii].checked != true))
			continue;
		else if (!this.form_data.elements[ii].name || this.form_data.elements[ii].name.length <= 0)
			continue;
		oData[this.form_data.elements[ii].name] = this.form_data.elements[ii].value;
	}
	return oData;
}
FileUploaderClass.prototype.CheckFields = function()
{
	this.ShowUploadError('');
	var iFileCount = this.Uploader.getUploadFileCount();
	var oFileTitles = {}; 
	var oDuplicateFileTitles = {}; 
	var oError = [];
// check file_name
	for (var ii = 1; ii <= iFileCount; ii++)
	{
		var guid = this.Uploader.getUploadFileGuid(ii);
		var File = this.oFiles[guid];
		var title = File["Title"].toLowerCase();
		var ext = File["FileName"].toLowerCase();
		if (title.lastIndexOf(".") <= 0 && ext.lastIndexOf(".") > 0)
		{
			title += ext.substr(ext.lastIndexOf("."));
		}
		if (!oFileTitles[title])
		{
			oFileTitles[title] = {"original_name" : File["Title"], "file_name" : File["FileName"]};
		}
		else
		{
			if (!oDuplicateFileTitles[title])
			{
				oDuplicateFileTitles[title] = [oFileTitles[title]];
			}
			oDuplicateFileTitles[title].push({"original_name" : File["Title"], "file_name" : File["FileName"]});
		}
	}
	for (title in oDuplicateFileTitles)
	{
		oTmp = {"title" : title, "files_name" : [], "file_name" : ""};
		for (var ii in oDuplicateFileTitles[title])
		{
			oTmp["files_name"].push(oDuplicateFileTitles[title][ii]["file_name"]);
			oTmp["file_name"] = oDuplicateFileTitles[title][ii]["original_name"];
		}
		if (oTmp["file_name"].lastIndexOf(".") <= 0 && title.lastIndexOf(".") > 0)
		{
			oTmp["file_name"] += title.substr(title.lastIndexOf("."));
		}
		var text = oText['Error_21'].replace('#TITLE#', oTmp["file_name"]).replace('#FILES#', oTmp["files_name"].join(", "));
		oError.push(text);
	}
	
	
	if (oError.length <= 0)
	{
		for (var ii = 1; ii <= iFileCount; ii++)
		{
			var guid = this.Uploader.getUploadFileGuid(ii);
			var File = this.oFiles[guid];
			var ext = File["FileName"].toLowerCase();
			if (File["Title"].toLowerCase().lastIndexOf(".") <= 0 && ext.lastIndexOf(".") > 0)
			{
				this.oFiles[guid]["Title"] += ext.substr(ext.lastIndexOf("."));
			}
		}
		return true;
	}
	this.ShowUploadError(oText['Error_2'] + '<br />' + oError.join(';<br />') + '.');
	return false;
}
FileUploaderClass.prototype.ShowUploadError = function(sText)
{
	document.getElementById("webdav_error_" + this.iIndex).innerHTML = '!!-' + sText;
	if (document.getElementById("webdav_error_" + this.iIndex))
		document.getElementById("webdav_error_" + this.iIndex).innerHTML = sText;
	else if (document.getElementById("webdav_error"))
		document.getElementById("webdav_error").innerHTML = sText;
}
ChangeModeUploader = function(view_mode_handler)
{
	if (!view_mode_handler || !view_mode_handler.form || parseInt(view_mode_handler.form.user_id.value) < 0)
		return false;
	var url = '/bitrix/components/bitrix/webdav.element.upload/user_settings.php?save=view_mode&sessid=' + view_mode_handler.form.sessid.value + '&view_mode=' + view_mode_handler.value;
	var TID = jsAjaxUtil.LoadData(url, new Function("jsUtils.Redirect([], '" + view_mode_handler.form.action + "')"));
}
SendTags = function(oObj, params)
{
	try
	{
		if (TcLoadTI)
		{
			if (typeof window.oObject[oObj.id] != 'object')
				window.oObject[oObj.id] = new JsTc(oObj, params);
			return;
		}
		setTimeout(SendTags(oObj, params), 10);
	}
	catch(e)
	{
		setTimeout(SendTags(oObj, params), 10);
	}
}


/* Upload form */
UploadLineClass = function()
{
	// Visual data
	this.form = false;
	this.container = false;
	// Main data
	this.oFiles = {}, this.oFields = {};
	this.iFileCount = 0, this.iFileIndex = 0;
	this.oInfo = {'stage' : 'ready', /* ready, upload, stop */
		'stage_upload' : 'ready', /* ready, wait, done*/
		'file_index' : 1};
	_this = this;
}
UploadLineClass.prototype.Init = function(oWhat, oFrom, oWhere, iIndex)
{
	if (!oFrom || !oWhere)
		return false;
	this.iIndex = parseInt(iIndex);
	this.form = oWhere;
	_this = this; 
	this.form.onsubmit = function(){_this.onSubmit(this); return false;}
	for (var ii = 0; ii < this.form.elements.length; ii++)
		this.form.elements[ii].disabled = true;
	if (document.getElementById('Send_' + this.iIndex))
	{
		document.getElementById('Send_' + this.iIndex).onclick = function(){_this.onSubmit(_this.form); return false;};
		document.getElementById('Send_' + this.iIndex).style.display = 'block';
	}
	
	this.container = oFrom;
	
	this.oFields = (oWhat && oWhat != null ? oWhat : {"SourceFile" : {"type" : "file", "title" : "File"}});
	this.iFileIndex = 0, this.iFileCount = 0;
	this.AddElement();
}
UploadLineClass.prototype.onChangeFile = function(oFile)
{
	var bEmpty = true; 
	for (var ii = 0; ii <= this.iFileIndex; ii++)
	{
		if (this.CheckData(ii))
		{
			bEmpty = false;
			break;
		}
	}
	for (var ii = 0; ii < this.form.elements.length; ii++)
		this.form.elements[ii].disabled = bEmpty;
	document.getElementById('Send_' + this.iIndex).className = (bEmpty ? "nonactive" : "");

	if (typeof(oFile) != "object" || oFile == null || oFile.value.length <= 0 || this.oInfo['stage'] == 'upload')
		return false;
	var form = document.forms['wd_form_' + this.iIndex + '_' + this.iFileIndex];
	if (form['SourceFile_1'].value.length > 0)
	{
		if (document.getElementById('wd_delete_' + this.iIndex + '_' + this.iFileIndex))
			document.getElementById('wd_delete_' + this.iIndex + '_' + this.iFileIndex).style.display = 'block';
		this.AddElement();
	}
}
UploadLineClass.prototype.onDeleteFile = function(oObj)
{
	if (typeof(oObj) != "object" || oObj == null || this.oInfo['stage'] == 'upload')
		return false;
	var file_index = parseInt(oObj.id.replace('wd_delete_' + this.iIndex + '_', ''));
	if (this.iFileIndex <= file_index)
		return true;
	this.DeleteElement(file_index);
	this.iFileCount = 0;
	for (var ii = 0; ii <= this.iFileIndex; ii++)
	{
		var title = document.getElementById('wd_title_' + this.iIndex + '_' + ii);
		if (title && title != null)
		{
			this.iFileCount++;
			title.innerHTML = this.iFileCount;
		}
	}
	this.onChangeFile(false);
	return true;
}
UploadLineClass.prototype.onSubmit = function(oForm)
{
	if (typeof(oForm) != "object" || oForm == null)
		return false;
	this.oInfo = {'stage' : 'ready', 'stage_upload' : 'ready', 'file_index' : 1};
	this.SendData();
	return false;
}
UploadLineClass.prototype.onFileUpload = function(iIndex, htmlPage)
{
	this.oInfo['stage'] = 'ready';
	this.oInfo['stage_upload'] = 'done';
	this.oInfo['file_index'] = iIndex;
	
	var result = {'fatal_errors' : {}, 'files' : {}}, bError = false, sError = '';
	
	try {
		eval("result=" + htmlPage);
		if (result["fatal_errors"].length <= 0 || result["files"].length <= 0)
		{
			// check structure;
		}
	} catch(e) { result = {'fatal_errors' : {'big_size' : {'text' : window.oText['ErrorNoData'], 'id' : 'no_data'}}, 'files' : {}}; }
	if (typeof result != "object" || result == null)
		result = {'fatal_errors' : {}, 'files' : {}};

	for (var ii in result["fatal_errors"])
	{
		bError = true;
		sError = (result["fatal_errors"][ii]['text'].length > 0 ? 
			result["fatal_errors"][ii]['text'] : result["fatal_errors"][ii]['id']) + '<br />';
	}
	var file_name = '';
	if (!bError)
	{
		for (var key in result["files"])
		{
			file_name = key;
			if (result["files"][key] && result["files"][key]["status"] != "success")
			{
				bError = true;
				if (result["files"][key]["error"])
				{
					for (var ii = 0; ii < result["files"][key]["error"].length; ii++)
					{
//						try {
							var File = result["files"][key]["error"][ii];
							sError += (File['text'].length > 0 ? File['text'] : File['id']) + '<br />';
//						} catch (e)	{ sError += 'Не известная ошибка.'; }
					}
				}
			}
		}
	}
	if (bError)
	{
		this.ShowFile(iIndex, 'done', '<span class="error required starrequired">' + sError + '</span>');
	}
	else
	{
		var ext = file_name.toLowerCase();
		ext = (ext.lastIndexOf(".") > 0 ? ext.substr((ext.lastIndexOf(".") + 1)) : '');
		var text = '<div class="element-name"><div class="element-icon ic' + ext + '"></div>' + file_name + '</div>';
		
		this.ShowFile(iIndex, 'done', text);
		
	}
}

UploadLineClass.prototype.CheckData = function(iIndex)
{
	var bEmpty = true;
	iIndex = parseInt(iIndex);
	if (document.forms['wd_form_' + this.iIndex + '_' + iIndex] && 
		document.forms['wd_form_' + this.iIndex + '_' + iIndex]['SourceFile_1'] && 
		document.forms['wd_form_' + this.iIndex + '_' + iIndex]['SourceFile_1'].value && 
		document.forms['wd_form_' + this.iIndex + '_' + iIndex]['SourceFile_1'].value.length > 0)
	{
		bEmpty = false; 
	}
	return (bEmpty ? false : true);
}
UploadLineClass.prototype.ShowFile = function(iIndex, status, text)
{
	form = document.forms['wd_form_' + this.iIndex + '_' + iIndex];
	var div = document.getElementById("wd_upload_file_" + this.iIndex + '_' + iIndex);
	var substrate = document.getElementById("substrate_" + this.iIndex + '_' + iIndex);
	
	if (!form || !div)
		return false;

	if (status == 'wait')
	{
		if (!substrate || substrate == null)
		{
			substrate = document.createElement("SPAN");
			substrate.id = 	"substrate_" + this.iIndex + '_' + iIndex;
			substrate.className = 'wd-substrate wd-substrate-wait';
			substrate.style.zIndex = 100;
			substrate.style.position = 	'absolute';
			substrate.style.display = 'none';
			substrate.style.backgroundColor = '#ededed';
			substrate.style.opacity = '0.3';
			if (substrate.style.MozOpacity)
				substrate.style.MozOpacity = '0.3';
			else if (substrate.style.KhtmlOpacity)
				substrate.style.KhtmlOpacity = '0.3';
			if (jsUtils.IsIE())
			{
		 		substrate.style.filter += "progid:DXImageTransform.Microsoft.Alpha(opacity=30)";
			}
			substrate.style.left = '2px';
			substrate.style.top = '2px';
			substrate.style.width = (parseInt(div.offsetWidth) - 4) + "px";
			substrate.style.height = (parseInt(div.offsetHeight) - 4) + "px";
			form.appendChild(substrate);
		}
		div.style.position = 'relative';
		substrate.style.display = 'block';
	}
	else if (status == 'done')
	{
		var pos = {'width' : form.offsetWidth, 'height' : form.offsetHeight};
		form.style.display = 'none';
		var div_reply = form.parentNode.appendChild(document.createElement('DIV'));
		div_reply.className =  'reply';
		div_reply.innerHTML = '<div class="inner">' + text + '</div>';
		div_reply.style.width = pos['width'] + "px";
		div_reply.style.height = pos['height'] + "px";
		form.parentNode.removeChild(form);
		this.onChangeFile(false);
	}
	else if (status == 'error')
	{
		if (!(!substrate || substrate == null))
			substrate.style.display = 'none';
	}
}
UploadLineClass.prototype.SendFile = function(iIndex)
{
	form = document.forms['wd_form_' + this.iIndex + '_' + iIndex];
	form.action = this.form.action;
	for (var ii = 0; ii < this.form.elements.length; ii++)
	{
		if (this.form.elements[ii]["type"] == "submit" || 
			(this.form.elements[ii]["type"] == "checkbox" && this.form.elements[ii].checked != true))
			continue;
		var input = document.createElement('INPUT');
		input.type = "hidden";
		input.name = this.form.elements[ii].name;
		input.value = this.form.elements[ii].value;
		if (input.name == "PackageGuid")
			input.value = Math.random();
		form.appendChild(input);
	}
	eval("jsAjaxUtil.SendForm(form, function(data){_this.onFileUpload(" + iIndex + ", data);})");
	form.submit();
}
UploadLineClass.prototype.SendData = function()
{
	if (this.oInfo['stage'] == 'ready')
	{
		for (var ii = this.oInfo['file_index']; ii <= this.iFileIndex; ii++)
		{
			if (!this.CheckData(ii))
			{
				this.ShowFile(ii, 'error', '');
				continue;
			}
			else
			{
				this.oInfo['stage'] = 'upload';
				this.oInfo['stage_upload'] = 'wait';
				this.oInfo['file_index'] = ii;
	
				this.ShowFile(ii, 'wait', '');
				this.SendFile(ii);
				break;
			}
		}
	}
	if (this.oInfo['stage'] == 'upload')
	{
		setTimeout(function(){_this.SendData()}, 1000);
		return;
	}
	else if (this.oInfo['stage'] == 'stop')
	{
//		debug_info('Загрузка остановлена пользователем. ');
	}
	else if (this.oInfo['stage'] == 'error')
	{
//		debug_info('Файлы загружены с ошибками. ');
	}
	else if (this.oInfo['stage'] == 'ready')
	{
//		debug_info('Файлы загружены успешно. ');
	}
	else
	{
//		debug_info('Не известный статус. ');
	}
}

UploadLineClass.prototype.AddElement = function()
{
	this.iFileIndex++;
	this.iFileCount++;
	var form_html = "", fields_html = "", prefix = this.iIndex + '_' + this.iFileIndex, field_name = "", text = "";
	for (var sFieldName in this.oFields)
	{
		field_name = sFieldName + '_1';
		text = '<input type="' + this.oFields[sFieldName]["type"] + '" name="' + field_name + '" />';
		if (this.oFields[sFieldName]["type"] == 'textarea')
			text = '<textarea name="' + field_name + '"></textarea>';
		else if (this.oFields[sFieldName]["type"] == 'file')
			text = '<input type="file" name="' + field_name + '" onchange="oParams[\'' + this.iIndex + '\'][\'object\'].onChangeFile(this)" />';
		else if (this.oFields[sFieldName]["use_search"] == "Y")
			text = '<input type="' + this.oFields[sFieldName]["type"] + '" name="' + field_name + '" id="' + field_name + '_' + prefix + '" onfocus="oParams[\'' + this.iIndex + '\'][\'object\'].SendTags(this)" />';
		fields_html += '<div><span>' + this.oFields[sFieldName]["title"] + ':</span>' + text + '</div>';
	}
	
	form_html +=  '<div class="wd-t"><div class="wd-r"><div class="wd-b"><div class="wd-l"><div class="wd-c">';
	
	form_html +=  '<div class="wd-title"><div class="wd-tr"><div class="wd-br"><div class="wd-bl"><div class="wd-tl">';
	form_html +=   '<div class="wd-del" id="wd_delete_' + prefix + '" onclick="oParams[\'' + this.iIndex + '\'][\'object\'].onDeleteFile(this)" style="display:none;"></div>';
	form_html +=   '<div class="wd-title-header" id="wd_title_' + prefix + '">' + this.iFileCount + '</div></div></div></div></div></div>';
	form_html +=   '<form id="wd_form_' + prefix + '" method="POST" enctype="multipart/form-data" class="wd-form">';
	form_html +=    fields_html;
	form_html +=   '</form>';
	form_html +=  '</div></div></div></div></div>';
	var oDiv = this.container.appendChild(document.createElement('DIV'));
	oDiv.id = 'wd_upload_file_' + prefix;
	oDiv.className = 'wd-upload-file'; 
	oDiv.innerHTML = form_html;
}
UploadLineClass.prototype.DeleteElement = function(iIndex)
{
	iIndex = parseInt(iIndex);
	if (iIndex <= 0)
		return false;
	oDiv = document.getElementById('wd_upload_file_' + this.iIndex + '_' + iIndex);
	if (oDiv)
	{
		oDiv.parentNode.removeChild(oDiv);
		this.iFileCount--;
		return true;
	}
	return false;
}

UploadLineClass.prototype.ShowUploadError = function(sText)
{
	if (document.getElementById("webdav_error_" + this.index))
		document.getElementById("webdav_error_" + this.index).innerHTML = sText;
	else if (document.getElementById("webdav_error"))
		document.getElementById("webdav_error").innerHTML = sText;
}
UploadLineClass.prototype.SendTags = function(oObj)
{
	if (typeof oObj != "object" || oObj == null)
		return false;
	if (window.TcLoadTI == true)
	{
		if (typeof window.oObject[oObj.id] != 'object')
			window.oObject[oObj.id] = new JsTc(oObj);
		return;
	}
	setTimeout(this.SendTags(oObj), 10);
}


window.WDUtilsIsLoaded = true;

BX(function() {
	function javaHideDaemon()
	{
		var popupShown = false;
		var overlays = BX.findChildren(document, {'className': 'bx-core-dialog-overlay'}, true);
		if ((!! overlays) && (overlays.length > 0))
		{
			for (i=0;i<overlays.length; i++)
			{
				popupShown = (BX.style(overlays[i], 'display') != 'none');
				if (popupShown)
					break;
			}

			var javaContainer = BX.findChild(document, {'className' : 'image-uploader-objects'}, true);
			if (!! javaContainer)
			{
				javaShown = (BX.style(javaContainer, 'visibility') != 'hidden');
				if (popupShown && javaShown)
				{
					BX.style(javaContainer, 'visibility', 'hidden');
				}
				else if((!popupShown) && (!javaShown))
				{
					BX.style(javaContainer, 'visibility', 'visible');
				}
			}
		}

		setTimeout(javaHideDaemon, 100);
	}

	setTimeout(javaHideDaemon, 100);
});
