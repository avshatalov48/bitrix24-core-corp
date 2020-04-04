BX.CDialog.prototype.WDUploadResponse = function(evt, responseJSONStr)
{
	CloseWaitWindow();
	if (this.progress)
		BX.hide(this.progress);
	this.WDUploadInProgress = false;
	if (evt)
		responseJSONStr = this.iframeUpload.contentWindow.document.body.innerHTML;
	var errText = BX('wd_upload_error_message');
	var okText = BX('wd_upload_ok_message');
	BX.show(BX('wd_messages'));
	if (responseJSONStr.length > 0)
	{
		var response = eval('('+responseJSONStr+')');
		errText.innerHTML = '';
		okText.innerHTML = '';
		this.targetUrl = response.url;
		for (error in response.fatal_errors)
		{
			errText.innerHTML += response.fatal_errors[error].text + '<br />';
			BX("wd_upload_form").style.display = 'block';
			BX.show(this.btnSubmit);
		}
		for (file in response.files)
		{
			if (response.files[file].status == 'error')
			{
				errText.innerHTML += response.files[file].error[0].text + '<br />';
				BX.show(this.btnSubmit);
				BX("wd_upload_form").style.display = 'block';
			}
			else if (response.files[file].status == 'success')
			{
				okText.innerHTML = this.msg.UploadSuccess + '<br />';
				this.WDUploaded = true;
				BX.WindowManager.Get().WDUploaded = true; // communication with element.edit
				if (BX('wd_upload_form'))
					BX('wd_upload_form').innerHTML = '';
				BX.hide(this.btnSubmit);
				this.btnClose.focus();
				if (this.fileDropped)
					this.Close();
			}
		}
	}
	if (BX.browser.IsOpera()) // fix Opera12 splitted dialog
	{
		var dlg = BX.findParent(BX("wd_messages"), {'className': 'dialog-center'}, true);
		if (dlg)
		{
			BX.style(dlg, 'margin', '40px 0px 49px');
		}
	}
}

BX.CDialog.prototype.WDUploadSubmit = function(disabled)
{
	BX.hide(this.btnSubmit);
	BX("wd_upload_ok_message").innerHTML="<div class=\"bx-core-waitwindow\" style=\"margin:0 auto; background-color:transparent; border:none; position:relative;\">"+phpVars.messLoading+"</div>";
	BX.show(BX('wd_messages'));
	var form = BX("wd_upload_form");
	if (disabled)
	{
		var progress = BX.create('div', {'attrs': {'className': 'wd_upload_progress', 'id':'wd_upload_progress'}});
		var progressbar = BX.create('div', {'attrs': {'className': 'wd_upload_progress_bar'}});
		var progresspercent = BX.create('div', {'attrs': {'className': 'wd_upload_progress_percent'}});
		progress.appendChild(progressbar);
		progress.appendChild(progresspercent);
		form.parentNode.insertBefore(progress, BX('wd_messages'));
		this.progress = progress;
		this.progressbar = progressbar;
		this.progresspercent = progresspercent;
	}

	if (BX.browser.IsOpera())
		BX.remove(form);
	else
		BX.hide(form);

	if (!disabled)
	{
		this.WDUploadInProgress = true;
		form.submit();
	}
}

BX.CDialog.prototype.WDUpdateSubmitLabel = function(allowed)
{
	var selectedFileName = this.WDGetUploadFileName();
	if (selectedFileName.length < 1) return;
	var manualFileName = this.fileName.value;
	if (this.documentExists && manualFileName == selectedFileName)
		this.btnSubmit.value = this.msg.SendVersion;
	else
		this.btnSubmit.value = this.msg.SendDocument;
	if (allowed)
		this.btnSubmit.focus();
}

BX.CDialog.prototype.WDonCheckFileExists = function(result)
{
	var errText = BX('wd_upload_error_message');
	var okText = BX('wd_upload_ok_message');
	if (errText.innerHTML.length > 0) errText.innerHTML = '';
	if (okText.innerHTML.length > 0) okText.innerHTML = '';
	if (result.errormsg && result.errormsg.length>0) {
		errText.innerHTML = result.errormsg;
		BX.show(BX('wd_messages'));
	}
	if (result.okmsg && result.okmsg.length>0) {
		okText.innerHTML = result.okmsg;
		BX.show(BX('wd_messages'));
	}
	this.btnSubmit.disabled = ! result.permission;
	if (result.permission)
		BX.show(this.btnSubmit);
	else
		BX.hide(this.btnSubmit);

	this.documentExists = (result.permission == true && result.okmsg != "");
	if (!this.WDFileUpdate)
		this.WDUpdateSubmitLabel(result.permission);
	if (!this.documentExists && this.fileDropped)
		this.callSubmit();
}

BX.CDialog.prototype.WDGetUploadFileName = function()
{
	fileName = '';
	if (this.fileInput)
	{
		var fileName = this.fileInput.value;
		if (fileName.indexOf('\\') > -1) // deal with Chrome fakepath
			fileName = fileName.substr(fileName.lastIndexOf('\\')+1);
	}
	else
	{
		var fileNode = BX.findChild(this.DIV, { className: 'webform-field-upload-list'}, true);
		if (fileNode.file)
			fileName = fileNode.file.fileName || fileNode.file.name;
	}
	return fileName;
}

BX.CDialog.prototype.WDonFileNameChange = function()
{
	this.fileDropped = false;
	this.WDCheckFileExists();
}

BX.CDialog.prototype.WDCheckFileExists = function()
{
	if (this.WDGetUploadFileName().length < 1) return;
	var checkUrl = this.checkFileUrl;
	if (!this.updateDocument)
	{
		var fileName = BX.util.urlencode(this.fileName.value);
		var checkParams = 'AJAX_CALL=Y&SIMPLE_UPLOAD=Y&sessid='+this.sessid+'&SECTION_ID='+this.sectionID+'&CHECK_NAME='+fileName;
		BX.ajax.loadJSON(checkUrl + ((checkUrl.indexOf('?') >= 0) ? '&' : '?') + checkParams, BX.delegate(this.WDonCheckFileExists, this));
	} else {
		objSF = BX('SourceFile_1');
		var fileName = "";
		if(objSF != null)
		{
			fileName = objSF.value;
		}
		else if(this.fileName != null)
		{
			fileName = this.fileName.value;
		}
		var fileName = BX.util.urlencode(fileName);
		var checkParams = 'AJAX_CALL=Y&SIMPLE_UPLOAD=Y&sessid='+this.sessid+'&update_document='+this.elementID+'&CHECK_NAME='+fileName;
		BX.ajax.loadJSON(checkUrl + ((checkUrl.indexOf('?') >= 0) ? '&' : '?') + checkParams, BX.delegate(this.WDonCheckFileExists, this));
	}
}

BX.CDialog.prototype.WDToggleUpload = function()
{
	BX.removeClass(this.DIV, 'droptarget');
	var fileName = this.WDGetUploadFileName();
	if (!this.updateDocument)
		this.fileName.value = fileName;
	this.fileDropped = false;
	this.WDCheckFileExists();
	var _btn = this.btnSubmit;
}

BX.CDialog.prototype.WDUploadLeave = function(e)
{
	var e = e || window.event;
	var msg = '';
	if (this.WDUploadInProgress)
		msg = this.msg.UploadInterrupt;
	else if (((!this.WDUploaded) && this.fileInput && (this.fileInput.value.length > 0)) || (BX.WindowManager.Get().WDUpdate))
		msg = this.msg.UploadNotDone;
	if (msg != '')
	{
		if (e)
			e.returnValue = msg;
		return msg; // safari & chrome
	}
	return;
}

BX.CDialog.prototype.WDFormatSize = function(size)
{
	var arSuffix = ['B', 'KB', 'MB', 'GB'];
	var result = '';
	if (size < 1024)
		result = size + ' ' + arSuffix[0];
	else if (size < 1024*1024)
		result = Math.round(size/1024) + ' ' +arSuffix[1];
	else if (size < 1024*1024*1024)
		result = Math.round(size/1024/1024) + ' ' + arSuffix[2];
	else
		result = Math.round(size/1024/1024/1024) + ' ' + arSuffix[3];
	return result;
}

BX.CDialog.prototype.uploadClean = function(createInput)
{
	var fileNode = BX.findChild(this.DIV, { className: 'webform-field-upload-list'}, true);
	this.fileInput = false;
	this.fileDropped = false;
	BX.cleanNode(fileNode);
	if (createInput)
	{
		var fileInput = BX.create('INPUT', {'attrs': {'className':'SourceFile_1', 'id':'SourceFile_1', 'type':'file', 'name':'SourceFile_1'}, 'style':{'width':'90%'}});
		this.fileInput = fileInput;
		BX.bind(fileInput, 'change', BX.delegate(this.WDToggleUpload, this));
		fileNode.appendChild(fileInput);
	}
	this.WDUploadInProgress = false;
	BX.hide(this.btnSubmit);
}

BX.CDialog.prototype.updateListFiles = function(files)
{
	if (this && files)
	{
		var _this = this;
		if (files.length < 1)
			return;
		j = 0;
		var fileNode = BX.findChild(this.DIV, { className: 'webform-field-upload-list'}, true);

		this.uploadClean();
		var fileName = files[j].fileName || files[j].name;
		var fileHref = BX.create('A', { 'text': fileName, 'props': { 'className': 'upload-file-name', 'href': 'javascript:void(0);'}});
		var fileSize = BX.create('SPAN', { 'props':{ 'className': 'file-size'}, 'text': this.WDFormatSize(files[j].size)});
		var fileDelete = BX.create('A', { 'props': { 'className': 'delete-file', 'href': 'javascript:void(0);'}});
		BX.bind(fileDelete, 'click', BX.delegate(function() {this.uploadClean(true);}, _this));

		fileNode.appendChild(fileHref);
		fileNode.appendChild(fileSize);
		fileNode.appendChild(fileDelete);
		fileNode.href = fileHref;
		fileNode.file = files[j];

		BX.show(this.btnSubmit);
		this.btnSubmit.focus();
		this.WDUploadInProgress = true;
		if (this.dropAutoUpload)
			this.fileDropped = true;

		if (!this.updateDocument)
			this.fileName.value = fileName;
		this.WDCheckFileExists();
	}
}

BX.CDialog.prototype.GetInputData = function(parentNode)
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

BX.CDialog.prototype.callSubmit = function()
{
	if (this.fileInput)
	{
		this.WDUploadSubmit();
	}
	else
	{
		this.__form = BX.findChild(this.DIV, {attr:{id:'wd_upload_form'}}, true);
		var arConstParams = this.GetInputData(BX('wd_upload_form'));
		var listParent = BX('wdUploadOrder');
		this.fileNodes = BX.findChild(this.DIV, {className:'webform-field-upload-list'}, true, true);
		for (i in this.fileNodes)
		{
			if (this.fileNodes[i].file)
			{
				var fd = new BX.ajax.FormData();
				for (item in this.fileNodes[i].data)
					fd.append(item, this.fileNodes[i].data[item]);
				for (item in arConstParams)
					fd.append(item, arConstParams[item]);
				fd.append('SourceFile_1', this.fileNodes[i].file);
				this.WDUploadSubmit(true);
				fd.send(
					this.uploadFileUrl,
					BX.delegate(function(ajaxdata) {
						this.WDUploadResponse(null, ajaxdata);
					}, this),
					BX.delegate(this.WDonProgress, this)
				);
			}
		}
	}
}

BX.CDialog.prototype.WDonProgress = function(percent)
{
	if (isNaN(percent))
		return;
	var percentS = Math.ceil(percent*100);
	BX.style(this.progressbar, 'width', percentS+'%');
	this.progresspercent.innerHTML = percentS+'%';
}


BX.CDialog.prototype.uploadDialogClose = function()
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
			BX.unbind(window, 'beforeunload', BX.proxy(this.WDUploadLeave, this));
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
	BX.unbind(window, 'beforeunload', BX.proxy(this.WDUploadLeave, this));
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

BX.CDialog.prototype.WDUploadInit = function(params)
{
	this.WDUploaded = false;
	this.WDUploadInProgress = false;
	this.WDFileUpdate = params.fileUpdate;
	this.documentExists = false;
	this.fileDropped = false;

	this.msg = params.msg;

	this.dropAutoUpload = params.dropAutoUpload;
	this.checkFileUrl = params.checkFileUrl;
	this.uploadFileUrl = params.uploadFileUrl;
	this.updateDocument = params.updateDocument;
	this.targetUrl = params.targetUrl;
	this.sessid = params.sessid;
	this.sectionID = params.sectionID;
	this.elementID = params.elementID;

	this.progress = null;
	this.progressbar = null;

	this.SetButtons("<input type=\"button\" id=\"wd_upload_submit\" value=\""+params.msg.Submit+"\">");
	this.SetButtons("<input type=\"button\" id=\"wd_upload_close\" value=\""+params.msg.Close+"\">");

	this.btnSubmit = BX("wd_upload_submit");
	this.btnClose = BX('wd_upload_close');
	this.iframeUpload = BX('upload_iframe');
	this.fileInput = BX('SourceFile_1');
	this.fileName = BX('Title_1');

	this.btnSubmit.disabled = true;

	BX.bind(this.btnSubmit, 'click', BX.delegate(this.callSubmit, this));
	BX.bind(this.btnClose, 'click', BX.delegate(this.Close, this));
	BX.bind(this.iframeUpload, 'load', BX.delegate(this.WDUploadResponse, this));
	BX.bind(this.fileInput, 'change', BX.delegate(this.WDToggleUpload, this));
	BX.bind(window, 'beforeunload', BX.proxy(this.WDUploadLeave, this));

	if (!this.WDFileUpdate)
	{
		BX.bind(this.fileName, 'change', BX.delegate(this.WDonFileNameChange, this));
		//BX.bind(this.fileName, 'keyup', BX.delegate(this.WDUpdateSubmitLabel, this));
	}

	BX.addCustomEvent(this, 'onBeforeWindowClose', BX.delegate(this.uploadDialogClose, this));

	BX.loadScript((!!BX.DD ? [] : ['/bitrix/js/main/core/core_dd.js']), BX.delegate(this.WDUploadFileDrop, this));
	//BX.findChild(this.DIV, {'class':'bx-core-dialog-content'}, true).style.height = 'auto';
	BX.findChild(this.DIV, {'class':'bx-core-adm-dialog-content'}, true).style.height = 'auto';
}

BX.CDialog.prototype.WDUploadFileEnter = function()
{
	BX.addClass(this.DIV, 'droptarget');
}

BX.CDialog.prototype.WDUploadFileLeave = function()
{
	BX.removeClass(this.DIV, 'droptarget');
}

BX.CDialog.prototype.WDUploadFileDrop = function()
{
	var dropbox = new BX.DD.dropFiles(this.DIV);
	if (dropbox && dropbox.supported() && BX.ajax.FormData.isSupported())
	{
		//var dropNote = BX.findChild(this.DIV, {'className':'drop-note'}, true);
		//if (dropNote)
			//BX.show(dropNote);
		BX.addCustomEvent(dropbox, 'dropFiles', BX.delegate(this.updateListFiles, this));
		//BX.addCustomEvent(dropbox, 'dragEnter', BX.delegate(this.WDUploadFileEnter, this));
		//BX.addCustomEvent(dropbox, 'dragLeave', BX.delegate(this.WDUploadFileLeave, this));
	}
}
