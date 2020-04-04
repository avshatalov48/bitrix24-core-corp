var jsPB = {
	width:0,
	obContainer: false,
	obIndicator: false,
	obIndicator2: false,

	Init: function(cont_id)
	{
		this.obContainer = BX(cont_id);

		if (!this.obContainer)
			return false;

		this.obIndicator = BX('instal-progress-bar-inner-text');
		this.obIndicator2 = BX('instal-progress-bar-span');
		this.obIndicator3 = BX('instal-progress-bar-inner');

		this.obContainer.style.display = '';
		this.width = this.obContainer.clientWidth || this.obContainer.offsetWidth;
	},

	Update: function(percent)
	{
		this.obIndicator.innerHTML = this.obIndicator3.style.width = percent+'%';
		this.obIndicator2.innerHTML = percent+'%';
	},

	Remove: function(bRemoveParent)
	{
		if (bRemoveParent == null)
			bRemoveParent = false;

		this.obContainer.style.display = '';
	}
};

var crmImportLocations = {

	init: function(params)
	{
		for(var key in params) //url
			this[key] = params[key];
	},

	onImportButClick: function()
	{
		var formDiv = BX("form_container");
		formDiv.style.display = 'none';
		this.loadFile({});
	},

	getInputValue: function(inputObj)
	{
		if(!inputObj)
			return false;

		for(var i=0, l=inputObj.length; i<l; i++)
			if(inputObj[i].checked)
				return inputObj[i].value;

		return false;
	},

	getCSVFileName: function()
	{
		return this.getInputValue(document.forms.import_form.locations_csv);
	},

    getTmpPath: function()
    {
        return document.forms.import_form.TMP_PATH.value;
    },

    getSync: function()
	{
		return this.getInputValue(document.forms.import_form.sync);
	},

	showError: function(errorMsg)
	{
		var errDiv = BX("instal-load-error");
		errDiv.innerHTML = errorMsg;
		errDiv.style.display = '';
		BX.WindowManager.Get().adjustSizeEx();
	},

	showHeadMessage: function(message)
	{
		if(message === undefined)
			return;

		var msgDiv = BX("instal-load-title");
		msgDiv.innerHTML = message;
		msgDiv.style.display = '';
		BX.WindowManager.Get().adjustSizeEx();
	},

	showMessage: function(message, savePrevMsg)
	{
		var msgDiv = BX("instal-load-label"),
			oldMessages = msgDiv.innerHTML;

		msgDiv.innerHTML = (savePrevMsg ? oldMessages +"<br>" : '')+message;
		msgDiv.style.display = '';
		BX.WindowManager.Get().adjustSizeEx();
	},

	loadFileManager: function(params)
	{
		if(params.ERROR)
		{
			this.showError(params.ERROR);
			this.showHeadMessage(BX.message("CRM_LOC_IMP_JS_ERROR"));
			return false;
		}

		if(params.COMPLETE)
		{
			jsPB.Init('instal-load-block');
			this.importLocations({});
			return true;
		}

		if(params.MESSAGE)
			this.showMessage(params.MESSAGE);

		if(params.STEP)
			this.loadFile({'STEP': params.STEP});

		return true;
	},

    finalizeDialog: function()
    {
        BX.closeWait();
        BX("instal-progress-bar-outer").style.display = 'none';
        BX.WindowManager.Get().ClearButtons();
        BX.WindowManager.Get().SetButtons(_BTN2);
        BX.WindowManager.Get().adjustSizeEx();
    },

	importManager: function(params)
	{

		if(params.ERROR)
		{
			this.showError(params.ERROR);

			if(params.STEP)
				this.importLocations({'STEP': params.STEP});
			else
			{
				this.showHeadMessage(BX.message("CRM_LOC_IMP_JS_ERROR"));
                this.finalizeDialog();
				return false;
			}
		}

		if(params.COMPLETE)
		{

			jsPB.Update(100);
			this.showHeadMessage(BX.message("CRM_LOC_IMP_JS_IMPORT_SUCESS"));
            this.finalizeDialog();
			return true;
		}

		if(params.MESSAGE)
			this.showMessage(params.MESSAGE);

		if(parseInt(params.AMOUNT, 10) > 0 && parseInt(params.POS, 10) > 0)
		{
			var percent = Math.round((parseInt(params.POS, 10)/parseInt(params.AMOUNT, 10)) * 100);
			jsPB.Update(percent);
		}

		if(params.STEP != "")
			this.importLocations({'STEP': params.STEP ? params.STEP : 1});

		return true;
	},

	importLocations: function(params)
	{
		this.showHeadMessage(BX.message('CRM_LOC_IMP_JS_IMPORT_PROCESS'));

		data = {
			'STEP': params.STEP ?  params.STEP : 1,
			'CSVFILE': this.getCSVFileName(),
            'TMP_PATH': this.getTmpPath(),
			'LOADZIP': document.forms.import_form.load_zip.checked ? 'Y' : 'N',
			'SYNC': this.getSync(),
			'STEP_LENGTH': document.forms.import_form.step_length.value,
			'sessid': BX.bitrix_sessid()
		};


		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: this.url+'/import.php',
			onsuccess: BX.delegate(function(result) {
									this.importManager(result);
									}, this
						),
			onfailure: function() {BX.debug('onfailure: crmImportLocations.importLocations');}
		});

	},

	loadFile: function(params)
	{
		this.showHeadMessage(BX.message('CRM_LOC_IMP_JS_FILE_PROCESS'));

		data = {
			'STEP': params.STEP ?  params.STEP : 1,
			'CSVFILE': this.getCSVFileName(),
            'TMP_PATH': this.getTmpPath(),
			'LOADZIP': document.forms.import_form.load_zip.checked ? 'Y' : 'N',
			'sessid': BX.bitrix_sessid()
		};

		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: this.url+'/loader.php',
			onsuccess: BX.delegate(function(result) {
									this.loadFileManager(result);
									}, this
						),
			onfailure: function() {BX.debug('onfailure: crmImportLocations.loadFile');}
		});
	},

	checkZIP: function()
	{
		var obCSVFileRus = BX('loc_ussr');
		var obCSVFileNone = BX('none');
		var obZIPFile = BX('load_zip');
		var obZIPFileCont = BX('zip_container');
		var obOwnFile = BX('ffile');
		if (obCSVFileRus && obCSVFileNone && obZIPFile && obOwnFile)
		{
			if (obCSVFileRus.checked || obCSVFileNone.checked || obOwnFile.checked)
			{
				obZIPFile.disabled = false;
			}
			else
			{
				obZIPFile.disabled = true;
				obZIPFile.checked = false;
			}

			if(obCSVFileNone.checked)
				obZIPFile.checked = true;
			else
				obZIPFile.checked = false;

			if(obOwnFile.checked)
				BX.show(BX('fileupload'));
			else
				BX.hide(BX('fileupload'));
		}

	},

	checkStep: function()
	{
		var stepVal = document.forms.import_form.step_length.value,
			retVal = true;

		if(stepVal == '')
		{
			retVal = false;
		}
		else if(/[^0-9.,\s]/.test(stepVal) || BX.util.trim(stepVal) == '' || parseInt(stepVal) <=0 )
		{
			alert(BX.message('CRM_LOC_IMP_STEP_CHECK'));
			retVal = false;
		}

		BX('crm_loc_import').disabled = !retVal;

		return retVal;
	}
};

