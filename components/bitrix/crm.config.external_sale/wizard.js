BX.ExtSaleWizard = function()
{
	this.url = "/bitrix/components/bitrix/crm.config.external_sale/wizard.php";
	this.id = undefined;
	this.step = 1;
	this.site = "";
	this.dlg = undefined;
}

BX.ExtSaleWizard.prototype.Start = function(site, step, id)
{
	if ((typeof step == undefined) || (step == 0))
		step = 1;
	if ((typeof id == undefined) || (id == 0))
		id = undefined;

	this.id = id;
	this.step = step;
	this.site = site;

	this.Show();
}

BX.ExtSaleWizard.prototype.RequestResult = function(result)
{
	BX.closeWait();
	if (result.length > 0)
	{
		this.dlg.SetContent(result);
	}
}

BX.ExtSaleWizard.prototype.Show = function()
{
	this.dlg = new BX.CDialog({
		'title': extSaleGetRemoteFormLocal1["TITLE"],
		'content_url': this.url + "?site_id=" + this.site + "&current_step=" + this.step + (this.id != undefined ? "&id=" + this.id : ""),
		'resizable': true,
		'draggable': true,
		'closeByEsc': false,
		'height': '400',
		'width': '800'
	});

	this.dlg.Show();
}

BX.ExtSaleWizard.prototype.Send = function(action)
{
	var dlgParent = this.dlg.GetContent();
	if (dlgParent)
	{
		var dlgFormsList = dlgParent.getElementsByTagName("form");
		if (dlgFormsList && dlgFormsList.length > 0)
		{
			var dlgForm = dlgFormsList[0];

			var sendParams = {}, ar, v;

			ar = this.GetFieldValuesByTagName(dlgForm.getElementsByTagName("input"));
			for (v in ar)
				sendParams[v] = ar[v];
			ar = this.GetFieldValuesByTagName(dlgForm.getElementsByTagName("select"));
			for (v in ar)
				sendParams[v] = ar[v];
			ar = this.GetFieldValuesByTagName(dlgForm.getElementsByTagName("textarea"));
			for (v in ar)
				sendParams[v] = ar[v];

			sendParams["wizard_action"] = action;

			var _this = this;

			BX.showWait();
			BX.ajax.post(this.url, sendParams, function(v){ _this.RequestResult(v); });

			return;
		}
	}

	parentWindow.Close();
	window.location.reload(true);
}

BX.ExtSaleWizard.prototype.FieldValuesByTagNameArray = function(arValue, value)
{
	if (typeof(arValue) != 'undefined')
	{
		if ((typeof(arValue) != 'object') || !(arValue instanceof Array))
			arValue = [arValue];

		arValue[arValue.length] = value;
	}
	else
	{
		arValue = [value];
	}
	return arValue;
}

BX.ExtSaleWizard.prototype.GetFieldValuesByTagName = function(inputsCollection)
{
	var fieldValues = {};

	if (!inputsCollection || inputsCollection.length <= 0)
		return fieldValues;

	for (var i in inputsCollection)
	{
		var elem = inputsCollection[i];

		if (elem.type == undefined)
			continue;

		if (elem.type.substr(0, "select".length) == "select")
		{
			if (elem.multiple)
			{
				var newName = elem.name.replace(/\[\]/g, "");
				for (var j = 0; j < elem.options.length; j++)
				{
					if (elem.options[j].selected)
						fieldValues[newName] = this.FieldValuesByTagNameArray(fieldValues[newName], elem.options[j].value);
				}
			}
			else
			{
				if (elem.selectedIndex >= 0)
					fieldValues[elem.name] = elem.options[elem.selectedIndex].value;
			}
		}
		else if (elem.type == "checkbox" || elem.type == "radio")
		{
			if (elem.checked)
			{
				if (elem.name.indexOf("[]") >= 0)
				{
					var newName = elem.name.replace(/\[\]/g, "");
					fieldValues[newName] = this.FieldValuesByTagNameArray(fieldValues[newName], elem.value);
				}
				else
				{
					fieldValues[elem.name] = elem.value;
				}
			}
		}
		else
		{
			if (elem.name.indexOf("[]") >= 0)
			{
				var newName = elem.name.replace(/\[\]/g, "");
				fieldValues[newName] = this.FieldValuesByTagNameArray(fieldValues[newName], elem.value);
			}
			else
			{
				fieldValues[elem.name] = elem.value;
			}
		}
	}

	return fieldValues;
}

var bxExtSaleWizard = new BX.ExtSaleWizard();
