
function ExtSaleGetRemoteForm(externalSaleId, action, id)
{
	var url;

	if (action == "CREATE")
		url = "/bitrix/tools/crm_sale_proxy.php?" + externalSaleId + "/bitrix/admin/sale_order_new.php?param=1";
	else if (action == "EDIT")
		url = "/bitrix/tools/crm_sale_proxy.php?" + externalSaleId + "/bitrix/admin/sale_order_new.php?ID=" + id;
	else if (action == "VIEW")
		url = "/bitrix/tools/crm_sale_proxy.php?" + externalSaleId + "/bitrix/admin/sale_order_detail.php?ID=" + id;
	else if (action == "PRINT")
		url = "/bitrix/tools/crm_sale_proxy.php?" + externalSaleId + "/bitrix/admin/sale_order_print.php?ID=" + id;

	ExtSaleDialogShow(url, action == "PRINT" ? extSaleGetRemoteFormLocal["PRINT"] : extSaleGetRemoteFormLocal["SAVE"]);
	return false;
}

function ExtSaleRequestResult(dlgWindow, result)
{
	BX.closeWait();
	if (result.length > 0)
	{
		if (!isNaN(result) && parseInt(result) == result)
		{
			var obj = document.getElementById('ID_SYNC_ORDER_ID');
			if (obj)
				obj.value = result;
			obj = document.getElementById('ID_SYNC_ORDER_FORM_NAME');
			if (obj)
			{
				BX.showWait();
				ExtSaleDialogDisableButtons(true);
				document.forms[document.getElementById('ID_SYNC_ORDER_FORM_NAME').value].submit();
			}
			else
			{
				dlgWindow.Close();
			}
		}
		else
		{
			//dlgWindow.SetContent(result);
		}
	}
}

function ExtSaleGetFieldValuesByTagNameArray(arValue, value)
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

function ExtSaleGetFieldValuesByTagNameInternal(elem)
{
	var fieldValues1 = {};

	if (elem.type.substr(0, "select".length) == "select")
	{
		if (elem.multiple)
		{
			var newName = elem.name.replace(/\[\]/g, "");
			for (var j = 0; j < elem.options.length; j++)
			{
				if (elem.options[j].selected)
					fieldValues1[newName] = ExtSaleGetFieldValuesByTagNameArray(fieldValues1[newName], elem.options[j].value);
			}
		}
		else
		{
			if (elem.selectedIndex >= 0)
				fieldValues1[elem.name] = elem.options[elem.selectedIndex].value;
		}
	}
	else if (elem.type == "checkbox" || elem.type == "radio")
	{
		if (elem.checked)
		{
			if (elem.name.indexOf("[]") >= 0)
			{
				var newName = elem.name.replace(/\[\]/g, "");
				fieldValues1[newName] = ExtSaleGetFieldValuesByTagNameArray(fieldValues1[newName], elem.value);
			}
			else
			{
				fieldValues1[elem.name] = elem.value;
			}
		}
	}
	else
	{
		if (elem.name.indexOf("[]") >= 0)
		{
			var newName = elem.name.replace(/\[\]/g, "");
			fieldValues1[newName] = ExtSaleGetFieldValuesByTagNameArray(fieldValues1[newName], elem.value);
		}
		else
		{
			fieldValues1[elem.name] = elem.value;
		}
	}

	return fieldValues1;
}

function ExtSaleMergeOptions(obj1, obj2)
{
	if(!obj1)
	{
		obj1 = {};
	}

	if(!obj2)
	{
		obj2 = {};
	}

	var obj3 = {};
	for (var k1 in obj1)
	{
		if(!obj1.hasOwnProperty(k1))
		{
			continue;
		}

		if(BX.type.isArray(obj1[k1]))
		{
			obj3[k1] = BX.util.array_merge(obj3[k1], obj1[k1]);
		}
		else if(Object.prototype.toString.call(obj1[k1]) === '[object Object]')
		{
			obj3[k1] = ExtSaleMergeOptions(obj3[k1], obj1[k1]);
		}
		else
		{
			obj3[k1] = obj1[k1];
		}
	}

	for (var k2 in obj2)
	{
		if(!obj2.hasOwnProperty(k2))
		{
			continue;
		}

		if(BX.type.isArray(obj2[k2]))
		{
			obj3[k2] = BX.util.array_merge(obj3[k2], obj2[k2]);
		}
		else if(Object.prototype.toString.call(obj2[k2]) === '[object Object]')
		{
			obj3[k2] = ExtSaleMergeOptions(obj3[k2], obj2[k2]);
		}
		else
		{
			obj3[k2] = obj2[k2];
		}
	}
	return obj3;
}

function ExtSaleGetFieldValuesByTagName(inputsCollection)
{
	var fieldValues = {};

	if (!inputsCollection || inputsCollection.length <= 0)
		return fieldValues;

	for (var i = 0; i < inputsCollection.length; i++)
	{
		var elem = inputsCollection[i];

		if (elem.type == undefined)
		{
			if (elem.length > 0)
			{
				for (var j = 0; j < elem.length; j++)
				{
					var elem1 = elem[j];
					if (elem1 && (elem1.type != undefined))
						fieldValues = ExtSaleMergeOptions(fieldValues, ExtSaleGetFieldValuesByTagNameInternal(elem1));
				}
			}
		}
		else
		{
			fieldValues = ExtSaleMergeOptions(fieldValues, ExtSaleGetFieldValuesByTagNameInternal(elem));
		}
	}

	return fieldValues;
}

function ExtSaleDialogDisableButtons(val)
{
	var btn = document.getElementById("btn-save");
	if (btn)
		btn.disabled = val;
	btn = document.getElementById("btn-cancel");
	if (btn)
		btn.disabled = val;
}

function ExtSaleDialogShow(url, saveButtonName)
{
	var dlg = new BX.CAdminDialog({
		'title': extSaleGetRemoteFormLocal["ORDER"],
		'content_url': url,
		'resizable': true,
		'draggable': true,
		'height': '400',
		'width': '800'
	});

	var btns = [];

	btns.push({
		'title': saveButtonName.length > 0 ? saveButtonName : extSaleGetRemoteFormLocal["SAVE"],
		'id': 'btn-save',
		'name': 'btn-save',
		'action': function()
		{
			var parentWindow = this.parentWindow;
			var dlgParent = parentWindow.GetContent();
			if (dlgParent)
			{
				var dlgFormsList = dlgParent.getElementsByTagName("form");
				if (dlgFormsList && dlgFormsList.length > 0)
				{
					var dlgForm = dlgFormsList[0];
					if (dlgForm.action.length > 0)
					{
						ExtSaleDialogDisableButtons(true);

						var sendParams = {}, ar, v;

						ar = ExtSaleGetFieldValuesByTagName(dlgForm.getElementsByTagName("input"));
						for (v in ar)
							sendParams[v] = ar[v];
						ar = ExtSaleGetFieldValuesByTagName(dlgForm.getElementsByTagName("select"));
						for (v in ar)
							sendParams[v] = ar[v];
						ar = ExtSaleGetFieldValuesByTagName(dlgForm.getElementsByTagName("textarea"));
						for (v in ar)
							sendParams[v] = ar[v];

						sendParams["AlreadyUTF8Request"] = "Y";

						BX.showWait();
						if (dlgForm.method == "get")
							BX.ajax.get(dlgForm.action, sendParams, function(v){ExtSaleDialogDisableButtons(false); ExtSaleRequestResult(parentWindow, v);});
						else
							BX.ajax.post(dlgForm.action, sendParams, function(v){ExtSaleDialogDisableButtons(false); ExtSaleRequestResult(parentWindow, v);});

						return;
					}
				}
			}

			this.parentWindow.Close();
		}
	});

	btns.push({
		'title': extSaleGetRemoteFormLocal["CLOSE"],
		'id': 'btn-cancel',
		'name': 'btn-cancel',
		'action': function()
		{
			this.parentWindow.Close();
		}
	});

	dlg.SetButtons(btns);

	BX.addCustomEvent(
		dlg,
		'onWindowClose',
		function()
		{
			BX.ajax.get(url, {"dontsave" : "Y"}, function(v){});
		}
	);

	dlg.Show();
}
