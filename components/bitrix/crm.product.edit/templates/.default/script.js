function addNewTableRow(tableID, row_to_clone)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length;
	if(row_to_clone == null)
		row_to_clone = cnt - 1;
	var sHTML = tbl.rows[row_to_clone].cells[0].innerHTML;
	var oRow = tbl.insertRow(row_to_clone+1);
	var oCell = oRow.insertCell(0);

	var s, e, n, p;
	p = 0;
	while(true)
	{
		s = sHTML.indexOf('[n',p);
		if(s<0)break;
		e = sHTML.indexOf(']',s);
		if(e<0)break;
		n = parseInt(sHTML.substr(s+2,e-s));
		sHTML = sHTML.substr(0, s)+'[n'+(++n)+']'+sHTML.substr(e+1);
		p=s+1;
	}
	p = 0;
	while(true)
	{
		s = sHTML.indexOf('__n',p);
		if(s<0)break;
		e = sHTML.indexOf('_',s+2);
		if(e<0)break;
		n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'__n'+(++n)+'_'+sHTML.substr(e+1);
		p=e+1;
	}
	p = 0;
	while(true)
	{
		s = sHTML.indexOf('__N',p);
		if(s<0)break;
		e = sHTML.indexOf('__',s+2);
		if(e<0)break;
		n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'__N'+(++n)+'__'+sHTML.substr(e+2);
		p=e+2;
	}
	p = 0;
	while(true)
	{
		s = sHTML.indexOf('xxn',p);
		if(s<0)break;
		e = sHTML.indexOf('xx',s+2);
		if(e<0)break;
		n = parseInt(sHTML.substr(s+3,e-s));
		sHTML = sHTML.substr(0, s)+'xxn'+(++n)+'xx'+sHTML.substr(e+2);
		p=e+2;
	}
	p = 0;
	while(true)
	{
		s = sHTML.indexOf('%5Bn',p);
		if(s<0)break;
		e = sHTML.indexOf('%5D',s+3);
		if(e<0)break;
		n = parseInt(sHTML.substr(s+4,e-s));
		sHTML = sHTML.substr(0, s)+'%5Bn'+(++n)+'%5D'+sHTML.substr(e+3);
		p=e+3;
	}

	var htmlObject = {'html': sHTML};
	BX.onCustomEvent(window, 'onAddNewRowBeforeInner', [htmlObject]);
	sHTML = htmlObject.html;

	oCell.innerHTML = sHTML;

	var patt = new RegExp ("<"+"script"+">[^\000]*?<"+"\/"+"script"+">", "ig");
	var code = sHTML.match(patt);
	if(code)
	{
		for(var i = 0; i < code.length; i++)
		{
			if(code[i] != '')
			{
				s = code[i].substring(8, code[i].length-9);
				jsUtils.EvalGlobal(s);
			}
		}
	}

	if (BX && BX.adminPanel)
	{
		BX.adminPanel.modifyFormElements(oRow);
		BX.onCustomEvent('onAdminTabsChange');
	}

	setTimeout(function() {
		var r = BX.findChildren(oCell, {tag: /^(input|select|textarea)$/i});
		if (r && r.length > 0)
		{
			for (var i=0,l=r.length;i<l;i++)
			{
				if (r[i].form && r[i].form.BXAUTOSAVE)
					r[i].form.BXAUTOSAVE.RegisterInput(r[i]);
				else
					break;
			}
		}
	}, 10);
}
