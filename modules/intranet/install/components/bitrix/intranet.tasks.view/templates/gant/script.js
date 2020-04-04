var jsBXAC = {
	cur_date: null,
	arData: null,
	
	_months: [],
	_note: '',
	
	url: '',
	detail_url: '',
	
	obMainLayout: null,
	obLayout: null,
	obTable: null,
	
	bSectionFlag: false,
	
	arTmpEntries: [],
	obFiltersForm: null,
	
	serverTimezoneOffset: 0,
	
	Init: function (url, layout, detail_url, cur_date, obFiltersForm, bSectionFlag, serverTimezoneOffset)
	{
		jsBXAC.url = url;
		jsBXAC.detail_url = detail_url;
		jsBXAC._months = jsBXCalendarMonths,

		jsBXAC.serverTimezoneOffset = serverTimezoneOffset + (new Date()).getTimezoneOffset() * 60;
		jsBXAC.cur_date = new Date((cur_date + jsBXAC.serverTimezoneOffset) * 1000);
		
		jsBXAC.bSectionFlag = bSectionFlag;

		jsBXAC.obLayout = document.createElement('DIV');
		
		jsBXAC.obMainLayout = document.getElementById(layout);
		jsBXAC.obMainLayout.className = 'bx-calendar-layout';
		
		jsBXAC._note = jsBXAC.obMainLayout.innerHTML;
		
		jsBXAC.obFiltersForm = obFiltersForm;

		if (null != obFiltersForm)
		{
			obFiltersForm.onsubmit = function () {return false;}
			
			for (var i=0; i < obFiltersForm.elements.length; i++)
			{
				obFiltersForm.elements[i].onchange = jsBXAC._refresh;
			}
		}
		
		jsBXAC._refresh();
	},
	
	__loader: function(str)
	{
		jsBXAC.arData = eval(str);

		for (var i = 0; i < jsBXAC.arTmpEntries.length; i++)
		{
			document.body.removeChild(jsBXAC.arTmpEntries[i]);
		}

		jsBXAC.arTmpEntries = [];
		
		jsBXAC.obMainLayout.innerHTML = jsBXAC._note;
		jsBXAC.obLayout = null;
		jsBXAC.obLayout = document.createElement('DIV');
		
		setTimeout('jsBXAC.Generate(); jsBXAC.Finish(); jsAjaxUtil.CloseLocalWaitWindow(666, jsBXAC.obMainLayout);', 50);
	},
	
	Finish: function()
	{
		while (jsBXAC.obMainLayout.firstChild)
			jsBXAC.obMainLayout.removeChild(jsBXAC.obMainLayout.firstChild)

		jsBXAC.obMainLayout.appendChild(jsBXAC.obLayout);
		
		setTimeout('jsBXAC.generateEntries()', 20);
	},
	
	generateEntries: function()
	{
		var date_start = jsBXAC.cur_date;
		var date_finish = new Date(date_start);
		date_finish.setMonth(date_finish.getMonth() + 1);
		date_finish.setDate(date_finish.getDate() - 1);

		var padding = 2;

		for (var i = 0; i < jsBXAC.arData.length; i++)
		{
			var obUserRow = document.getElementById('bx_calendar_user_' + jsBXAC.arData[i]['ID']);
			
			if (obUserRow && jsBXAC.arData[i]['DATA'])
			{
				var obRowPos = jsUtils.GetRealPos(obUserRow);
				
				for (var j = 0; j < jsBXAC.arData[i]['DATA'].length; j++)
				{
					
					var ts_start = new Date((jsBXAC.serverTimezoneOffset + parseInt(jsBXAC.arData[i]['DATA'][j]['DATE_ACTIVE_FROM'])) * 1000);
					var ts_finish = new Date((jsBXAC.serverTimezoneOffset + parseInt(jsBXAC.arData[i]['DATA'][j]['DATE_ACTIVE_TO'])) * 1000);
					
					if (date_start.valueOf() > ts_finish.valueOf() || date_finish.valueOf() < ts_start.valueOf())
						continue;
					
					var obDiv = document.body.appendChild(document.createElement('DIV'));
					
					obDiv.bx_color_variant = jsBXAC.arData[i]['DATA'][j]['TYPE'] ? jsBXAC.arData[i]['DATA'][j]['TYPE'] : 'default';
					
					obDiv.className = 'bx-calendar-entry bx-calendar-color-' + obDiv.bx_color_variant;
					obDiv.style.top = (obRowPos.top + padding) + 'px';
					
					var obStartCell = obUserRow.cells[date_start.valueOf() < ts_start.valueOf() ? ts_start.getDate() : date_start.getDate()];
					var obFinishCell = obUserRow.cells[date_finish.valueOf() < ts_finish.valueOf() ? date_finish.getDate() : ts_finish.getDate()];
					
					obPos = jsUtils.GetRealPos(obStartCell);
					var start_pos = obPos.left + padding;

					if (ts_start.getHours() > 14) 
						start_pos += parseInt((obPos.right - obPos.left)/2) - 1;

					if (obStartCell != obFinishCell)
						obPos = jsUtils.GetRealPos(obFinishCell);

					var width = obPos.right - start_pos - (jsUtils.IsIE() ? padding  * 2 : padding);
					
					if (ts_finish.getHours() > 0 && ts_finish.getHours() <= 14) 
						width -= parseInt((obPos.right - obPos.left)/2) + 1;

						
					obDiv.style.left = start_pos + 'px';
					obDiv.style.width = width + 'px';
					
					obDiv.innerHTML = obDiv.title = jsBXAC.arData[i]['DATA'][j]['NAME'];
					obDiv.__bx_user_id = jsBXAC.arData[i]['ID'];
					obDiv.onmouseover = jsBXAC._hightlightRowDiv;
					obDiv.onmouseout = jsBXAC._unhightlightRowDiv;
					
					jsBXAC.arTmpEntries[jsBXAC.arTmpEntries.length] = obDiv;
					
				}
			}
		}
	},
	
	_hightlightRow: function()
	{
		this.className = 'bx-calendar-currow';
	},

	_unhightlightRow: function()
	{
		this.className = '';
	},
	
	_hightlightRowDiv: function()
	{
		this.className = 'bx-calendar-entry bx-calendar-color-' + this.bx_color_variant + ' bx-calendar-entry-active';
		if (null != this.__bx_user_id)
		{
			document.getElementById('bx_calendar_user_' + this.__bx_user_id).className = 'bx-calendar-currow';
		}
	},

	_unhightlightRowDiv: function()
	{
		this.className = 'bx-calendar-entry bx-calendar-color-' + this.bx_color_variant;
	
		if (null != this.__bx_user_id)
		{
			document.getElementById('bx_calendar_user_' + this.__bx_user_id).className = '';
		}
	},
	
	Generate: function()
	{
		jsBXAC.obTable = document.createElement('TABLE');
		jsBXAC.obTable.className = 'bx-calendar-main-table';
		jsBXAC.obTable.setAttribute('cellSpacing', '0');
		
		jsBXAC.obLayout.appendChild(jsBXAC.obTable);
		
		
		jsBXAC.obTable.appendChild(document.createElement('THEAD'));
		jsBXAC.obTable.appendChild(document.createElement('TBODY'));

		// generate controls
		var obRow = jsBXAC.obTable.tBodies[0].insertRow(-1);

		obRow.insertCell(-1);

		obRow.cells[0].className = 'bx-calendar-control';
		obRow.cells[0].innerHTML += 
			'<table class="bx-calendar-control-table"><tr>' + 
			'<td><a href="javascript:void(0)" onclick="jsBXAC.changeYear(-1)" class="bx-calendar-icon bx-calendar-bback"></a></td>' +
			'<td><a href="javascript:void(0)" onclick="jsBXAC.changeMonth(-1)" class="bx-calendar-icon bx-calendar-back"></a></td>' +
			'<td class="bx-calendar-control-text">' + 
			jsBXAC._months[jsBXAC.cur_date.getMonth()] + ', ' + jsBXAC.cur_date.getFullYear() + 
			'</td>' + 
			'<td><a href="javascript:void(0)" onclick="jsBXAC.changeMonth(1)" class="bx-calendar-icon bx-calendar-fwd"></a></td>' + 
			'<td><a href="javascript:void(0)" onclick="jsBXAC.changeYear(1)" class="bx-calendar-icon bx-calendar-ffwd"></a></td>' + 
			'</tr></table>';
		
		// generate dating cols
		var startMonth = jsBXAC.cur_date.getMonth();
		var cur_date = new Date(jsBXAC.cur_date.valueOf());
		while (cur_date.getMonth() == startMonth)
		{
			var obCell = obRow.insertCell(-1);

			obCell.className = 'bx-calendar-day';
			if (cur_date.getDay() == 0 || cur_date.getDay() == 6)
				obCell.className += ' bx-calendar-holiday';

			obCell.innerHTML = cur_date.getDate();
			cur_date.setDate(cur_date.getDate() + 1);
		}

		var date_start = jsBXAC.cur_date;
		var date_finish = new Date(date_start);
		date_finish.setMonth(date_finish.getMonth() + 1);
		date_finish.setDate(date_finish.getDate() - 1);
		
		for (var i = 0; i < jsBXAC.arData.length; i++)
		{
			var obRow = jsBXAC.obTable.tBodies[0].insertRow(-1);
			
			obRow.onmouseover = jsBXAC._hightlightRow;
			obRow.onmouseout = jsBXAC._unhightlightRow;

			obRow.insertCell(-1);
			obRow.cells[0].className = 'bx-calendar-first-col';

			obRow.id = 'bx_calendar_user_' + jsBXAC.arData[i]['ID'];

			var obNameContainer = obRow.cells[0].appendChild(document.createElement('DIV'));
			var strName = jsBXAC.arData[i]['LAST_NAME'] + ' ' + jsBXAC.arData[i]['NAME'] + ' ' + jsBXAC.arData[i]['SECOND_NAME'];

			obNameContainer.title = strName;
			
			var obName = document.createTextNode(strName);
			
			if (jsBXAC.detail_url)
			{
				var obAnchor = document.createElement('A');
				obAnchor.href = jsBXAC.detail_url.replace('#ID#', jsBXAC.arData[i]['ID']);
				obAnchor.appendChild(obName);
				//strName = '<a href="' +  + '">' + strName + '</a>';
				obNameContainer.appendChild(obAnchor);
			}
			else
				obNameContainer.appendChild(obName);
			
			var tmp_date = new Date(jsBXAC.cur_date.valueOf());

			var obDiv = null;
			while (tmp_date.getMonth() == startMonth)
			{
				var obCell = obRow.insertCell(-1);
				obCell.title = obNameContainer.title;
				obCell.className = 'bx-calendar-day';

				if (tmp_date.getDay() == 0 || tmp_date.getDay() == 6)
				obCell.className += ' bx-calendar-holiday';

				if (jsUtils.IsIE())
					obCell.innerHTML = '&nbsp;';

				tmp_date.setDate(tmp_date.getDate() + 1);
			}
		}
	},

	_refresh: function ()
	{
		var url = jsBXAC.url + '?';
		
		url += 'section_flag=' + (jsBXAC.bSectionFlag ? 'Y' : 'N') + '&';
		
		var arGetData = ['date=' + jsBXAC.cur_date.valueOf()/1000];
		
		if (null != jsBXAC.obFiltersForm)
		for (var i=0; i < jsBXAC.obFiltersForm.elements.length; i++)
		{
			if (null !== jsBXAC.obFiltersForm.elements[i].value)
			arGetData[arGetData.length] = jsBXAC.obFiltersForm.elements[i].name + '=' + jsBXAC.obFiltersForm.elements[i].value;
		}
		
		url += arGetData.join('&');
	
		jsAjaxUtil.ShowLocalWaitWindow(666, jsBXAC.obMainLayout);
		jsAjaxUtil.LoadData(url, jsBXAC.__loader);
	},
	
	changeMonth: function(dir)
	{
		if (dir != -1) dir = 1;
		jsBXAC.cur_date.setMonth(jsBXAC.cur_date.getMonth() + dir);
		
		jsBXAC._refresh();
	},

	changeYear: function(dir)
	{
		if (dir != -1) dir = 1;
		jsBXAC.cur_date.setYear(jsBXAC.cur_date.getFullYear() + dir);
		
		jsBXAC._refresh();
	}
}
