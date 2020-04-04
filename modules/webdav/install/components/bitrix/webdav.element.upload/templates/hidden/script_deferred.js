BX.WDFileHiddenUpload = function()
{
	this.id = this.getID();
	this.enabled = true;
	this.agent = null;
}

BX.WDFileHiddenUpload.prototype.getID = function() {
    return '' + new Date().getTime();
}

BX.WDFileHiddenUpload.prototype.UploadResponse = function(evt, responseJSONStr)
{
	//CloseWaitWindow();
	this.WDUploadInProgress = false;

	if (evt)
		responseJSONStr = this.iframeUpload.contentWindow.document.body.innerHTML;

	var arMessages = [];
	if (responseJSONStr.length > 0)
	{
		var response = eval('('+responseJSONStr+')');
		this.targetUrl = response.url;
		for (error in response.fatal_errors)
		{
			arMessages.push(response.fatal_errors[error].text);
		}
		for (file in response.files)
		{
			if (response.files[file].status == 'error')
			{
				arMessages.push(response.files[file].error[0].text);
			}
			else if (response.files[file].status == 'success')
			{
				this.WDUploaded = true;
			}
		}
		var result = {};
		result.success = (arMessages.length <= 0);
		result.storage = 'webdav';
		if (result.success){
			result.element_id = response.element_id;
			result.files = response.files;
		}
		else
			result.messages = arMessages;
		BX.unbind(window, 'beforeunload', BX.proxy(this.UploadLeave, this));
		BX.onCustomEvent(this, 'uploadFinish', [result, response]);
	}
}

BX.WDFileHiddenUpload.prototype.onCheckFileExists = function(result)
{
	var errText = BX('wd_upload_error_message'+this.ucid);
	var okText = BX('wd_upload_ok_message'+this.ucid);
	if (errText.innerHTML.length > 0) errText.innerHTML = '';
	if (okText.innerHTML.length > 0) okText.innerHTML = '';
	if (result.errormsg && result.errormsg.length>0) {
		errText.innerHTML = result.errormsg;
		BX.show(BX('wd_messages'+this.ucid));
	}

	if (result.okmsg && result.okmsg.length>0) {
		okText.innerHTML = result.okmsg;
		BX.show(BX('wd_messages'+this.ucid));
	}
	this.documentExists = (result.permission == true && result.okmsg != "");
	if (!this.documentExists && this.fileDropped)
		this.CallSubmit();
}

BX.WDFileHiddenUpload.prototype.GetUploadFileName = function(not_customized)
{
	custom = !not_customized;
	fileName = '';
	if (custom && (this.fileTitle.value.length > 0)) {
		fileName = this.fileTitle.value;
	} else if (this.fileInput && (this.fileInput.value.length > 0)) {
		var fileName = this.fileInput.value;
		if (fileName.indexOf('\\') > -1) // deal with Chrome fakepath
			fileName = fileName.substr(fileName.lastIndexOf('\\')+1);
	} else {
		var fileNode = BX.findChild(this.DIV, { className: 'webform-field-upload-list'}, true);
		if (fileNode.file)
			fileName = fileNode.file.fileName || fileNode.file.name;
	}
	return fileName;
}

BX.WDFileHiddenUpload.prototype.CheckFileExists = function()
{
	var fileName = '';
	fileName = this.GetUploadFileName(); //BX('SourceFile_1'+this.ucid).value;
	if (fileName.length < 1)
		return;

	var checkParams = 'AJAX_CALL=Y&SIMPLE_UPLOAD=Y&sessid='+this.sessid+'&CHECK_NAME='+fileName;
	var checkUrl = this.checkFileUrl + ((this.checkFileUrl.indexOf('?') >= 0) ? '&' : '?') + checkParams;

	BX.ajax.loadJSON(checkUrl, BX.delegate(this.onCheckFileExists, this));
}

BX.WDFileHiddenUpload.prototype.UploadLeave = function(e)
{
	var e = e || window.event;
	var msg = '';
	if (this.WDUploadInProgress)
		msg = this.msg.UploadInterrupt;
	else if (((!this.WDUploaded) && this.fileInput && (this.fileInput.value.length > 0)))
		msg = this.msg.UploadNotDone;
	if (msg != '')
	{
		if (e)
			e.returnValue = msg;
		return msg; // safari & chrome
	}
	return;
}

BX.WDFileHiddenUpload.prototype.UpdateListFiles = function(files)
{
	if (this && files)
	{
		var _this = this;
		if (files.length < 1)
			return;
		j = 0;
		var fileNode = BX.findChild(this.DIV, { className: 'webform-field-upload-list'}, true);
		fileNode.file = files[j];

		this.WDUploadInProgress = true;
		if (this.dropAutoUpload)
			this.fileDropped = true;

		this.CallSubmit();
		//this.CheckFileExists();
	}
}

BX.WDFileHiddenUpload.prototype.GetInputData = function(parentNode)
{
	var elements = [];
	var data = {};
	elements = elements.concat(
		BX.findChildren(parentNode, {'tag': 'input'}, true),
		BX.findChildren(parentNode, {'tag': 'textarea'}, true),
		BX.findChildren(parentNode, {'tag': 'select'}, true));

	for(var i=0; i<elements.length; i++)
	{
		var el = elements[i];
		if (!el || el.disabled || el.name.length < 1)
			continue;
		switch(el.type.toLowerCase())
		{
			case 'text':
			case 'textarea':
			case 'password':
			case 'hidden':
			case 'select-one':
				data[el.name] = el.value;
				break;
			case 'radio':
				if(el.checked)
					data[el.name] = el.value;
				break;
			case 'checkbox':
				data[el.name] = (el.checked ? 'Y':'N');
				break;
			case 'select-multiple':
				var l = el.options.length;
				if (l > 0) data[el.name] = new Array();
				for (j=0; j<l; j++)
					if (el.options[j].selected)
						data[el.name].push(el.options[j].value);
				break;
			default:
				break;
		}
	}
	return data;
}

BX.WDFileHiddenUpload.prototype.SetFileInput = function(fileInput)
{
	BX.remove(this.fileInput);
	this.__form.appendChild(fileInput);
	this.fileInput = fileInput;
}

BX.WDFileHiddenUpload.prototype.CallSubmit = function()
{
	BX.onCustomEvent(this, 'uploadStart', [this]);

	BX.bind(window, 'beforeunload', BX.proxy(this.UploadLeave, this));
	BX.addCustomEvent(this, 'onBeforeWindowClose', BX.delegate(this.UploadDialogClose, this));

	if (this.dropbox) {
		this.onProgress(0.15);
		if (this.fileInput && (this.fileInput.files.length > 0)) {
			var fileNode = BX.findChild(this.DIV, { className: 'webform-field-upload-list'}, true);
			fileNode.file = this.fileInput.files[0];
		}

		this.__form = BX.findChild(this.DIV, {attr:{id:'wd_upload_form'+this.ucid}}, true);
		var arConstParams = this.GetInputData(this.__form);
		var listParent = BX('wdUploadOrder'+this.ucid);
		this.fileNodes = BX.findChild(this.DIV, {className:'webform-field-upload-list'}, true, true);

		for (i in this.fileNodes) {
			if (this.fileNodes[i].file) {

				var fd = new BX.ajax.FormData();

				for (item in this.fileNodes[i].data)
				{
					fd.append(item, this.fileNodes[i].data[item]);
				}

				if (!! Object && !! Object.keys) // for IE 10 ....
				{
					var keys = Object.keys(arConstParams);
					for (var k in keys)
					{
						var key = keys[k]
						var cons = arConstParams[key]
						fd.append(key, cons);
					}
				}
				else
				{
					for (item in arConstParams)
					{
						fd.append(item, arConstParams[item]);
					}
				}

				fd.append('SourceFile_1', this.fileNodes[i].file);
				fd.send(
					this.uploadFileUrl,
					BX.delegate(function(ajaxdata) {
						this.UploadResponse(null, ajaxdata);
					}, this),
					BX.delegate(this.onProgress, this)
				);
			}
		}
	} else {
		this.onProgress(0.15);
		BX("wd_upload_ok_message"+this.ucid).innerHTML="<div class=\"bx-core-waitwindow\" style=\"margin:0 auto; background-color:transparent; border:none; position:relative;\">"+phpVars.messLoading+"</div>";
		BX.show(BX('wd_messages'+this.ucid));
		BX.hide(this.__form);
		this.WDUploadInProgress = true;
		var fid = this.__form.id;
		this.__form.submit();
	}
}

BX.WDFileHiddenUpload.prototype.onProgress = function(percent)
{
	BX.onCustomEvent(this, 'progress', [percent]);
}

BX.WDFileHiddenUpload.prototype.UploadDialogClose = function()
{
	var msg = '';
	if (this.WDUploadInProgress)
		msg = this.msg.UploadInterruptConfirm;
	else if ((!this.WDUploaded) && this.fileInput && (this.fileInput.value.length > 0))
		msg = this.msg.UploadNotDoneAsk;
	this.denyClose = false;
	if (msg != '')
	{
		if (confirm(msg))
		{
			BX.unbind(window, 'beforeunload', BX.proxy(this.UploadLeave, this));
			if (this.WDUploadInProgress)
			{
				BX.showWait();
				jsUtils.Redirect([], this.targetUrl);
			}
		}
		else
		{
			this.denyClose = true;
		}
	}
	BX.unbind(window, 'beforeunload', BX.proxy(this.UploadLeave, this));
	if (this.WDUploaded)
	{
		BX.showWait();
		var tmphref = window.location.href;

		if (window.location.href.indexOf('#') > 0)
			tmphref = tmphref.substr(0, tmphref.indexOf('#'));
		if (this.targetUrl.substr(0, this.targetUrl.indexOf('#')) == decodeURIComponent(tmphref)) {
			window.location.href = this.targetUrl;
			window.location.reload(true);
		} else {
			window.location.href = this.targetUrl;
		}
	}
	if (! this.denyClose)
		this.DIV.parentNode.removeChild(this.DIV);
}

BX.WDFileHiddenUpload.prototype.ToggleUpload = function()
{
	BX.removeClass(this.DIV, 'droptarget');
	this.fileDropped = true;
	//this.CheckFileExists();
	this.CallSubmit();
}

BX.WDFileHiddenUpload.prototype.UploadInit = function(params)
{
	this.WDUploaded = false;
	this.WDUploadInProgress = false;
	this.documentExists = false;
	this.fileDropped = false;

	this.msg = params.msg;

	this.dropAutoUpload = params.dropAutoUpload;
	this.checkFileUrl = params.checkFileUrl;
	this.uploadFileUrl = params.uploadFileUrl;
	this.targetUrl = params.targetUrl;
	this.sessid = params.sessid;
	this.ucid = params.ucid;
	this.parentID = params.parentID;

	this.__form = BX('wd_upload_form'+this.ucid);
	this.iframeUpload = BX('upload_iframe'+this.ucid);
	this.fileInput = BX('SourceFile_1'+this.ucid);
	this.fileTitle = BX('Title_1'+this.ucid);
	this.DIV = this.__form.parentNode;

	BX.bind(this.iframeUpload, 'load', BX.delegate(this.UploadResponse, this));
	BX.bind(this.fileInput, 'change', BX.proxy(this.ToggleUpload, this));
	if (!!BX.DD) {
		this.UploadFileDrop();
		BX.onCustomEvent(top, 'WDFileHiddenUploadInit', [this]);
		//this.agent.BindUploadEvents(this); //problems with multiple file upload
	}
}

BX.WDFileHiddenUpload.prototype.UploadFileEnter = function()
{
	BX.addClass(this.DIV, 'droptarget');
}

BX.WDFileHiddenUpload.prototype.UploadFileLeave = function()
{
	BX.removeClass(this.DIV, 'droptarget');
}

BX.WDFileHiddenUpload.prototype.UploadFileDrop = function()
{
	var dropbox = new BX.DD.dropFiles(this.DIV);
	if (dropbox && dropbox.supported() && BX.ajax.FormData.isSupported())
	{
		this.dropbox = dropbox;
	}
}
