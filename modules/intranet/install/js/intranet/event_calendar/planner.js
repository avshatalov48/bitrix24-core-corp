// # # #  #  #  # Planner for Event Calendar  # # #  #  #  #
function ECPlanner(oEC) {this.Init(oEC);}
ECPlanner.prototype = {
Init: function(oEC)
{
	window._bx_plann_events = {};
	window._bx_plann_mr = {};
	this.oEC = oEC; // Event Calendar Object
	this.bCreated = false;
	this.bOpened = false;
	this.bMRShowed = false;
	this.bFreezed = true;

	var set = this.oEC.arConfig.Settings;
	this.scale = parseInt(set.planner_scale) || 1; // 0 - 30 min;   1 - 1 hour; 2 - 2 hour; 3 - 1day
	this.width = parseInt(set.planner_width) || 650;
	this.height = parseInt(set.planner_height) || 520;

	this.bOnlyWorkTime = true;
	this.preFetch = {back: 8, forward: 26};

	if (this.bOnlyWorkTime)
	{
		var wt = this.oEC.arConfig.workTime, arTF = wt[0].split('.'), arTT = wt[1].split('.');
		this.oTime = {from: {h: bxIntEx(arTF[0]), m: bxIntEx(arTF[1])}, to: {h: bxIntEx(arTT[0]), m: bxIntEx(arTT[1])}};
		this.oTime.count = this.oTime.to.h - this.oTime.from.h;
	}
	else
	{
		this.oTime = {from: {h: 0, m: 0}, to: {h: 24, m: 0}, count: 24};
	}
},

Freeze: function(bFreeze)
{
	this.bFreezed = bFreeze;
	if (bFreeze)
		BX.addClass(this.pCont, 'bxecpl-empty');
	else
		BX.removeClass(this.pCont, 'bxecpl-empty');

	if (BX.browser.IsIE()) // Fix IE Bug
	{
		var _this = this;
		setTimeout(function(){_this.BuildGridTitle();}, 1000);
	}
},

OpenDialog: function(Params)
{
	var
		bFirst = !this.bCreated,
		oEED = this.oEC.oEditEventDialog,
		tempG = [], id, initDate,
		_this = this;

	// INIT DATE
	this.bFromDialog = Params.bFromDialog;

	if (this.bFromDialog)
		initDate = bxGetDate(oEED.oFrom.value, true);
	this.initDate = initDate || new Date();
	this.SetCurrentDate(this.initDate);

	if (bFirst)
		this.CreateDialog();

	if (BX.browser.IsIE())
		setTimeout(function(){_this.BuildGridTitle();}, 1000);
	else
		this.BuildGridTitle();

	if (!this.pDialog.PreShow()) // Dialog opened
		return;

	this.pDialog.Show(this.pDialog.Resize(true));

	if (!bFirst)
		this.ClearUserList(true);

	this.arGuestsIds = [];
	this.arGuests = [];

	// Set From - To
	this.pFrom.value = oEED.oFrom.value;
	this.pTo.value = oEED.oTo.value;
	this.pFromTime.value = oEED.oFromTime.value;
	this.pToTime.value = oEED.oToTime.value;



	// Set location
	this.pLocation.Set(oEED.oLocation.curInd, oEED.oLocation.pInp.value);

	// Set Guests
	for (id in oEED.Guests)
		if (oEED.Guests[id])
			tempG.push(oEED.Guests[id].user);

	if (tempG.length > 0)
		this.AddGuests(tempG);

	this.FieldDatesOnChange();

	setTimeout(function()
	{
		_this.Resize(_this.width, _this.height);
		_this.oSel.Adjust();
	}, 100);

	if (this.bFromDialog)
	{
		this.pDialog.nextBut.style.display = this.pDialog.save2But.style.display = 'none';
		this.pDialog.saveBut.style.display = 'inline';
	}
	else
	{
		this.pDialog.nextBut.style.display = this.pDialog.save2But.style.display = 'inline';
		this.pDialog.saveBut.style.display = 'none';
	}

	this.bOpened = true;
},

CreateDialog: function()
{
	var
		_this = this,
		ecid = this.oEC.id;

	this.bCreated = true;
	this.pDialog = new this.oEC.BXECDialogCore({
		obj : this.oEC,
		name : 'ADD_EVENT',
		id : 'bxec_plan_' + ecid,
		close_id: ecid + '_plan_close',
		cancel_id: ecid + '_plan_cancel',
		save_id: ecid + '_plan_apply',
		shadow_id: 'bxec_plan_' + ecid + '_shadow',
		bEscClose: false,
		bClickClose: false,
		//onEnter: function(){	if (_this.SimpleSaveNewEvent()){_this.CloseAddEventDialog();}},
		width: this.width,
		height:  this.height
	});

	this.pDialog.saveBut.onclick = function(){_this.Submit();};

	this.pDialog.nextBut = BX(ecid + '_plan_next');
	this.pDialog.save2But = BX(ecid + '_plan_save');

	this.pDialog.save2But.onclick = function()
	{
		if (!_this.CheckSubmit())
			return;
		_this.Submit();

		// Set default NAME to dialog field
		_this.oEC.oEditEventDialog.oName.value = EC_MESS.DefMeetingName;

		// Save event
		if (window.pLHEEvDesc)
			window.pLHEEvDesc.SaveContent();

		_this.oEC.ExtendedSaveEvent({callback: BX.proxy(_this.oEC.CloseEditEventDialog, _this.oEC), bLocationChecked: false});
	};

	this.pDialog.nextBut.onclick = function()
	{
		if (!_this.CheckSubmit())
			return;
		_this.Submit();

		// Show Save Event Dialog
		var oEED = _this.oEC.oEditEventDialog;
		_this.oEC.EditEventDialog.style.display = 'block';
		_this.oEC.EditEventDialog.style.visibility = 'visible';
		oEED._shad.style.display = 'block';

		oEED.oName.value = EC_MESS.DefMeetingName;
		oEED.oName.select();
		oEED.oName.onchange();
	};

	this.BuildCore();
	this.pDialog._Close = function()
	{
		_this.Close();
		if (!_this.bFromDialog)
			_this.oEC.CloseEditEventDialog();
	};

	this.pDuration = new ECPlDuration(this);
	this.pLocation = new ECLocation(this.oEC, 2, function(P){_this.LocationOnChange(P)});

	this.pResizer = BX(ecid + '_plan_resizer');
	this.pResizer.onmousedown = function(){_this.ResizerMouseDown()};
	this.pResizer.ondrag = BX.False;
},

GetMRAccessibility1: function(ind)
{
	this.oEC.Request({
		postData: this.oEC.GetPostData('get_guests_accessability', {users: guests, from: from, to: to, cur_event_id: curEventId}),
		errorText: '',
		handler: function(result)
		{
			setTimeout(function(){_this.DisplayDiagram(window._bx_plann_events, true);}, 200);
			return true;
		}
	});
},

Close: function()
{
	if (!this.bDenyClose)
	{
		this.CloseDialog();
		this.pDialog.Close();
	}
},

CloseDialog: function()
{
	this.bOpened = false;
},

BuildCore: function()
{
	var
		_this = this,
		ecid = this.oEC.id;

	this.pCont = BX(ecid + '_plan_cont');
	this.pTopCont = BX(ecid + '_plan_top_cont');
	this.pGridCont = BX(ecid + '_plan_grid_cont');
	this.pBottomCont = BX(ecid + '_plan_bottom_cont');

	this.pGridTbl = this.pGridCont.firstChild;

	this.pUserListCont = this.pGridTbl.rows[2].cells[0];
	this.pGridTitleCont = this.pGridTbl.rows[0].cells[2];
	this.pGridCellCont = this.pGridTbl.rows[2].cells[2];

	this.pUserListDiv = this.pUserListCont.firstChild;
	this.pGridTitleDiv = this.pGridTitleCont.firstChild;
	this.pGridDiv = this.pGridCellCont.firstChild;
	this.pGAccCont = this.pGridDiv.firstChild;

	this.pUserListTable = this.pUserListDiv.appendChild(BX.create("TABLE", {props: {className: 'bxec-user-list'}}));
	this.pGridTitleTable = this.pGridTitleDiv.appendChild(BX.create("TABLE", {props: {className: 'bxec-grid-cont-tbl'}}));
	this.pGridTable = this.pGridDiv.appendChild(BX.create("TABLE", {props: {className: 'bxec-grid-bg-tbl'}}));

	if (BX.browser.IsIE())
		BX.addClass(this.pGridTitleTable, BX.browser.IsDoctype() ? 'bxec-iehack0': 'bxec-iehack');

	DenyDragEx(this.pGridTable);
	this.oSel = new ECPlSelection(this);

	var scrollTmt;
	this.pGridDiv.onscroll = function()
	{
		_this.pGridTitleTable.style.left = '-' + parseInt(this.scrollLeft) + 'px'; // Synchronized scrolling with title
		_this.pUserListTable.style.top = '-' + parseInt(this.scrollTop) + 'px'; // Synchronized scrolling with userlist

		if (_this.oSel._bScrollMouseDown && BX.browser.IsIE())
		{
			if (scrollTmt)
				clearTimeout(scrollTmt);

			scrollTmt = setTimeout(
				function()
				{
					var sl = parseInt(_this.pGridDiv.scrollLeft);
					if (!_this.oSel || sl != _this.oSel._gridScrollLeft)
						_this.GridSetScrollLeft(_this.CheckScrollLeft(sl));
					_this.oSel._bGridMouseDown = false;
					_this.oSel._bScrollMouseDown = false;
				}, 1000
			);
		}
	};

	// Add users block
	this.InitUserControll();

	this.pScale = BX(ecid + '_plan_scale_sel');
	this.pScale.value = this.scale;
	this.pScale.onchange = function(e)
	{
		if (_this.bFreezed)
		{
			this.value = _this.scale;
			return BX.PreventDefault(e);
		}
		_this.ChangeScale(this.value);
	};

	// From / To Limits
	this.pFrom = document.forms['bxec_planner_form_' + ecid].bxec_planner_from;
	this.pTo = document.forms['bxec_planner_form_' + ecid].bxec_planner_to;
	this.pFromTime = BX('bxec_pl_time_f_' + ecid);
	this.pToTime = BX('bxec_pl_time_t_' + ecid);

	this.pFrom.id = 'bxec_planner_from_' + ecid;
	this.pTo.id = 'bxec_planner_to_' + ecid;
	this.pFrom.onchange = this.pFromTime.onchange = function(e){_this.FieldDatesOnChange(true, true);};
	this.pTo.onchange = this.pToTime.onchange = function(e){_this.FieldDatesOnChange(true);};
},

Submit: function()
{
	//Check
	if (!bxGetDate(this.pFrom.value + ' ' + this.pFromTime.value, true))
		return alert(EC_MESS.EventDiapStartError);

	var oEED = this.oEC.oEditEventDialog;

	// Set from - to
	oEED.oFrom.value = this.pFrom.value;
	oEED.oTo.value = this.pTo.value;
	oEED.oFromTime.value = this.pFromTime.value;
	oEED.oToTime.value = this.pToTime.value;

	// Set location
	oEED.oLocation.Set(this.pLocation.curInd, this.pLocation.pInp.value);

	// Set guests
	this.oEC.EEUC.DelAllUsers(true);
	var l = this.arGuests.length;
	if (l > 0)
	{
		for (var i = 0; i < l; i++)
			this.arGuests[i].busy = (this.oSel.arBusyGuests && this.oSel.arBusyGuests[this.arGuests[i].id]) || false;

		this.oEC.EEUC.AddUsers(this.arGuests);
	}

	this.Close();
},

CheckSubmit: function()
{
	if (!_this.pFrom.value || !_this.pTo.value)
	{
		alert(EC_MESS.NoFromToErr);
		return false;
	}

	if (_this.arGuests.length == 0)
	{
		alert(EC_MESS.NoGuestsErr);
		return false;
	}

	return true;
},

ChangeScale: function(scale)
{
	this.scale = parseInt(scale, 10); // Set new scale

	// # CLEANING #
	while(this.pGridTitleTable.rows[0])
		this.pGridTitleTable.deleteRow(0);

	// # BUILDING #
	this.BuildGridTitle();
	this.BuildGrid(this.arGuests.length);

	this.GetTimelineLimits(true);

	this.DisplayDiagram(false, true);
	this.DisplayMRDiagram(false, true);

	if (this.oSel.pDiv)
	{
		this.oSel.Make({bFromTimeLimits: true, bSetTimeline: false});
		var _this = this;
		setTimeout(function(){_this.FieldDatesOnChange(true, true);}, 500);
	}

	this.oEC.SaveSettings();
},

AddGroupMembers: function()
{
	this.oEC.EEUC.AddGroupMembers();
},

AddGuests: function(arGuests, bFirst, bTimeout)
{
	var
		_this = this,
		arIds = [], bDel,
		r, guest, c1, c2, ch, id, pDel,
		s_ind = this.arGuests.length,
		i, l = arGuests.length;

	if (this.arIds_ && this.arIds_.length > 0 && !bTimeout)
	{
		arIds = arIds.concat(this.arIds_);
		this.arIds_ = [];
	}

	if (l > 0)
	{
		if (s_ind == 0)
			this.Freeze(false);

		this.arGuests = this.arGuests.concat(arGuests);
		this.BuildGrid(this.arGuests.length);

		for (i = 0; i < l; i++)
		{
			// Add row to user list
			guest = arGuests[i];
			guest.busy = false;
			arIds.push(guest.id);

			r = this.pUserListTable.insertRow(s_ind++);
			r.id = 'ec_pl_u_' + guest.id;

			c1 = r.insertCell(-1);
			c1.className = 'bxecp-user-icon';
			c1.title = EC_MESS.ImpGuest;
			c1.innerHTML = '<img src="/bitrix/images/1.gif"/>';
			c1.onclick = function()
			{
				//var img = this.firstChild;
				if (this.className == 'bxecp-user-icon')
				{
					this.className = 'bxecp-user-icon-q';
					this.title = EC_MESS.NotImpGuest;
				}
				else
				{
					this.className = 'bxecp-user-icon';
					this.title = EC_MESS.ImpGuest;
				}
			};

			c2 = r.insertCell(-1);
			c2.innerHTML = '<div>' + this.oEC.GetUserProfileLink(guest.id, true, guest) + '</div>';

			bDel = guest.bDel !== false;
			pDel = c2.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', title: bDel ? EC_MESS.DelGuestTitle : EC_MESS.CantDelGuestTitle, className: 'bxecp-del' + (bDel ? '' : ' bxecp-del-d')}}));
			pDel.onclick = function()
			{
				var tr = this.parentNode.parentNode;
				_this.DelGuest(tr.id.substr('ec_pl_u_'.length), tr);
			};

			c2.onmouseover = function(){this.className = 'bxex-pl-u-over';};
			c2.onmouseout = function(){this.className = '';};
		}

		this.arGuestsIds = this.arGuestsIds.concat(arIds);

		if (!bTimeout)
		{
			this.ReColourTable();
			this.GetAccessibility({guests: arIds});
		}
		else
		{
			this.arIds_ = arIds;
		}

		this.oSel.Adjust();
	}
},

DelGuest: function(id, pRow)
{
	var i, l1 = this.arGuests.length, l2 = this.arGuestsIds.length;
	for (i = 0; i < l1; i++)
	{
		if (this.arGuests[i].id == id)
		{
			if (this.arGuests[i].bDel === false)
			{
				if (confirm(EC_MESS.DelOwnerConfirm))
					this.ClearUserList(true);
				return true;
			}

			// Del from list
			pRow.parentNode.removeChild(pRow);
			// Del from arrays
			this.arGuests = deleteFromArray(this.arGuests, i);
			break;
		}
	}

	for (i = 0; i < l2; i++)
	{
		if (this.arGuestsIds[i] == id)
		{
			this.arGuestsIds = deleteFromArray(this.arGuestsIds, i);
			break;
		}
	}

	// Decrease grid height
	this.BuildGrid(this.arGuests.length);
	this.ReColourTable();
	if (this.arGuests.length == 0)
		this.Freeze(true);

	this.DisplayDiagram(false, true);
	this.DisplayMRDiagram(false);

	this.oSel.Adjust();
},

DelAllGuests: function()
{
	var i, l1 = this.arGuests.length, l2 = this.arGuestsIds.length;
	for (i = 0; i < l1; i++)
	{
		if (this.arGuests[i].id == id)
		{
			if (this.arGuests[i].bDel === false)
			{
				if (confirm(EC_MESS.DelOwnerConfirm))
					this.DelAllGuests();
				return true;
			}

			// Del from list
			pRow.parentNode.removeChild(pRow);
			// Del from arrays
			this.arGuests = deleteFromArray(this.arGuests, i);
			break;
		}
	}
},

GetAccessibility: function(Params)
{
	var
		_this = this,
		guests = Params.guests,
		from, to,
		curEventId = this.oEC.oEditEventDialog.bNew ? false : this.oEC.oEditEventDialog.currentEvent.ID,
		cd = this.currentDate,
		fromD = new Date(),
		toD = new Date();

	fromD.setFullYear(cd.Y, cd.M, cd.D - this.preFetch.back);
	toD.setFullYear(cd.Y, cd.M, cd.D + this.preFetch.forward);
	this.LoadedLimits = {from: fromD.getTime(), to: toD.getTime()};

	from = bxFormatDate(fromD.getDate(), fromD.getMonth() + 1, fromD.getFullYear());
	to = bxFormatDate(toD.getDate(), toD.getMonth() + 1, toD.getFullYear());

	this.oEC.Request({
		postData: this.oEC.GetPostData('get_guests_accessability', {users: guests, from: from, to: to, cur_event_id: curEventId}),
		errorText: '',
		handler: function(result)
		{
			setTimeout(function(){_this.DisplayDiagram(window._bx_plann_events, true);}, 200);
			return true;
		}
	});
},

GetMRAccessibility: function(ind)
{
	var
		_this = this,
		mrid = this.pLocation.Get(ind),
		from, to,
		curEventId = this.oEC.oEditEventDialog.loc_old_mrevid,
		cd = this.currentDate,
		fromD = new Date(),
		toD = new Date();

	if (mrid === false)
		return;

	fromD.setFullYear(cd.Y, cd.M, cd.D - this.preFetch.back);
	toD.setFullYear(cd.Y, cd.M, cd.D + this.preFetch.forward);
	this.MRLoadedLimits = {from: fromD.getTime(), to: toD.getTime()};

	from = bxFormatDate(fromD.getDate(), fromD.getMonth() + 1, fromD.getFullYear());
	to = bxFormatDate(toD.getDate(), toD.getMonth() + 1, toD.getFullYear());

	this.oEC.Request({
		postData: this.oEC.GetPostData('get_mr_accessability', {id: mrid, from: from, to: to, cur_event_id: curEventId}),
		errorText: '',
		handler: function(result)
		{
			setTimeout(function(){_this.DisplayMRDiagram(window._bx_plann_mr[mrid], true);}, 200);
			return true;
		}
	});
},

DisplayDiagram: function(arPlannEvents, bClean)
{
	if (bClean && this.arACC)
	{
		for (var i = 0, l = this.arACC.length; i < l; i++)
			if (this.arACC[i].div && this.arACC[i].div.parentNode)
				this.pGAccCont.removeChild(this.arACC[i].div);
	}

	if (!arPlannEvents)
		arPlannEvents = window._bx_plann_events;

	this.arACC = [];

	var
		i, l = this.arGuests.length, uid;

	for (i = 0; i < l; i++)
	{
		uid = this.arGuests[i].id;
		if (arPlannEvents[uid])
			this.DisplayAccRow({events: arPlannEvents[uid], ind: i, uid: uid});
	}

	if (this.oSel)
		this.oSel.TimeoutCheck();
		//this.oSel.Check(this.oSel.GetCurrent(), true, false);
},

DisplayMRDiagram: function(arEvents, bClean)
{
	if (!this.bMRShowed)
		return;

	if (bClean) // Clean only MR diagram
		this.CleanMRDiagram();

	this.arMRACC = [];
	var mrid = this.pLocation.Get();
	if (!arEvents && mrid !== false)
		arEvents = window._bx_plann_mr[mrid];

	var l = this.arGuests.length;
	this.DisplayAccRow({events: arEvents, ind: l + 2, bMR: true});
},

CleanMRDiagram: function()
{
	if (typeof this.arMRACC == 'object')
	{
		for (var i = 0, l = this.arMRACC.length; i < l; i++)
			if (this.arMRACC[i].div.parentNode)
				this.pGAccCont.removeChild(this.arMRACC[i].div);
	}
	this.arMRACC = [];
},

DisplayDiagramEx: function()
{
	var tl = this.GetTimelineLimits();

	if (!this.LoadedLimits || !tl)
		return;

	if (tl.from.getTime() < this.LoadedLimits.from || tl.to.getTime() > this.LoadedLimits.to)
		this.GetAccessibility({guests: this.arGuestsIds});
	else
		this.DisplayDiagram(false, true);

	if (this.bMRShowed && (tl.from.getTime() < this.MRLoadedLimits.from || tl.to.getTime() > this.MRLoadedLimits.to))
		this.GetMRAccessibility();
	else
		this.DisplayMRDiagram(false, true);
},

DisplayAccRow: function(Params)
{
	if (typeof Params.events != 'object')
		return false;

	var
		tlLimits = this.GetTimelineLimits(),
		limFrom = tlLimits.from.getTime(),
		limTo = tlLimits.to.getTime(),
		top = (Params.ind * 20 + 0) + 'px', // Get top
		PaS, event, df, dt, days, cn, title, rtf, rtt,
		frH, frM, from, to, rdf, rdt,
		dayLen = 86400000,
		dispTimeF = this.oTime.from.h + this.oTime.from.m / 60,
		dispTimeT = this.oTime.to.h + this.oTime.to.m / 60,
		dayCW = this.GetDayCellWidth(),
		width, left, i, l = Params.events.length;

	for (i = 0; i < l; i++)
	{
		event = Params.events[i];

		from = event.from;
		to = event.to;
		rdf = rdt = false;

		if (to < limFrom || from > limTo)
			continue;

		if (from < limFrom)
		{
			from = limFrom;
			rdf = new Date(event.from);
		}
		if (to > limTo)
		{
			to = limTo;
			rdt = new Date(event.to);
		}

		df = new Date(from);
		dt = new Date(to);

		// 1. Days count from limitFrom
		left = dayCW * Math.floor((from - limFrom) / dayLen);
		dfTime = df.getHours() + df.getMinutes() / 60;
		time = dfTime - dispTimeF;
		if (time > 0)
			left += Math.round((dayCW * time) / this.oTime.count);

		if (event.from == event.to) // One full day event
		{
			width = dayCW - 1;
		}
		else
		{
			right = dayCW * Math.floor((to - limFrom) / dayLen);
			if (this.CheckBTime(dt))
				right += dayCW;

			dtTime = dt.getHours() + dt.getMinutes() / 60;
			if (dtTime > dispTimeT)
				dtTime = dispTimeT;
			time2 = dtTime - dispTimeF;
			if (time2 > 0)
				right += Math.round((dayCW * time2) / this.oTime.count);

			width = (right - left) - 1;
		}

		// Display event
		if (width > 0)
		{
			cn = 'bxec-gacc-el';
			if (!Params.bMR)
			{
				if (event.acc != 'busy')
					cn += ' bxec-gacc-' + event.acc;

				//if (event.imp != 'normal') // high | low
				//	cn += ' bxec-gacc-' + event.imp;
			}

			if (!rdf)
				rdf = df;
			if (!rdt)
				rdt = dt;

			// Make title:
			rtf = zeroInt(rdf.getHours()) + ':' + zeroInt(rdf.getMinutes());
			rtt = zeroInt(rdt.getHours()) + ':' + zeroInt(rdt.getMinutes());
			rtf = (rtf == '00:00') ? '' : ' ' + rtf;
			rtt = (rtt == '00:00') ? '' : ' ' + rtt;

			title = Params.bMR ? bxSpChBack(event.name) + ";\n " : '';
			title += bxFormatDate(rdf.getDate(), rdf.getMonth() + 1, rdf.getFullYear()) + rtf
			+ ' - '
			+ bxFormatDate(rdt.getDate(), rdt.getMonth() + 1, rdt.getFullYear()) + rtt;
			if (!Params.bMR)
			{
				title += ";\n " + EC_MESS.UserAccessability + ': '+ EC_MESS['Acc_' + event.acc].toLowerCase() +
				";\n " + EC_MESS.Importance + ': ' + EC_MESS['Importance_' + event.imp].toLowerCase();
			}

			pDiv = this.pGAccCont.appendChild(BX.create("DIV", {props: {className: cn, title: title}, style: {top: top, left: left + 'px', width: width + 'px'}}));
			if (!rtf && !rtt)
				to += dayLen;

			if (Params.bMR)
				this.arMRACC.push({div: pDiv, from: from, to: to});
			else
				this.arACC.push({div: pDiv, from: from, to: to, uid: Params.uid, aac: event.acc});
		}
	}
},

BlinkDiagramDiv: function(div)
{
	var
		iter = 0,
		origClass = div.className,
		warnClass = "bxec-gacc-el bxec-gacc-warn";

	if (origClass != warnClass)
	{
		var blinkInterval = setInterval(
			function()
			{
				div.className = (div.className == warnClass) ? origClass : warnClass;
				if (++iter > 5)
					clearInterval(blinkInterval);
			},250
		);
	}
},

BuildGridTitle: function()
{
	if (this.pGridTitleTable.rows.length > 0)
		BX.cleanNode(this.pGridTitleTable);

	var
		r_day = this.pGridTitleTable.insertRow(-1),
		r_time = this.pGridTitleTable.insertRow(-1),
		c_day, c_time,
		l = this.GetDaysCount(),
		j, i, arCell;

	r_time.className = 'bxec-pl-time-row bxecpl-s' + this.scale;
	r_day.className = 'bxec-plan-grid-day-row';
	this.pGTCells = [];

	// Each day
	for (i = 0; i < l; i++)
	{
		c_day = r_day.insertCell(-1);
		c_day.innerHTML = '<img src="/bitrix/images/1.gif" class="day-t-left"/><div></div><img src="/bitrix/images/1.gif" class="day-t-right"/>';
		arCell = {pDay: c_day, pTitle: c_day.childNodes[1]};

		//c_day.style.width = dayWidth + 'px';
		this.SetDayInCell(c_day, arCell.pTitle, i);

		if (this.scale == 0)
			c_day.colSpan = this.oTime.count * 2;
		else if (this.scale == 1)
			c_day.colSpan = this.oTime.count;
		else if (this.scale == 2)
			c_day.colSpan = Math.ceil(this.oTime.count / 2);

		if (this.scale != 3)
		{
			for (j = this.oTime.from.h; j < this.oTime.to.h; j++)
			{
				c_time = r_time.insertCell(-1);
				c_time.innerHTML = '<div>' + j + ':00' + '</div>';

				if (this.scale == 2)
					j++;

				if (this.scale == 0)
				{
					c_time = r_time.insertCell(-1);
					c_time.className = 'bxecpl-half-t-cell';
					c_time.innerHTML = '<div>' + j + ':30' + '</div>';
				}
			}
		}
		else
		{
			c_time = r_time.insertCell(-1);
			c_time.innerHTML = '<div>' + this.oTime.from.h + ':00 - ' + this.oTime.to.h + ':00' + '</div>';

			arCell.pTime = c_time;
		}

		this.pGTCells.push(arCell);
	}
},

SetDayInCell: function(pCell, pTitle, ind)
{
	var
		realInd = ind - (this.scale == 3 ? 2 : 1),
		oDate = new Date();

	oDate.setFullYear(this.currentDate.Y, this.currentDate.M, this.currentDate.D + realInd);

	var
		day = this.oEC.convertDayIndex(oDate.getDay()),
		date = oDate.getDate(),
		month = oDate.getMonth(),
		year = oDate.getFullYear(),
		str = bxFormatDate(date, month + 1, year);

	var
		CD = this.oEC.currentDate,
		bHol = this.oEC.week_holidays[day] || this.oEC.year_holidays[date + '.' + month], //It's Holliday
		bCur = date == CD.date && month == CD.month && year == CD.year;

	if (bHol && bCur)
		pCell.className = 'cur-hol-day';
	else if(bHol)
		pCell.className = 'hol-day';
	else if(bCur)
		pCell.className = 'cur-day';
	else
		pCell.className = '';

	pTitle.innerHTML = str;
	pCell.title = this.oEC.arConfig.days[this.oEC.convertDayIndex(oDate.getDay())][0] + ', ' + str;
},

BuildGrid : function(length)
{
	var
		_this = this,
		oRow = this.pGridTable.rows[0] || this.pGridTable.insertRow(-1),
		c, dayWidth,
		cellWidth = 81,
		l = this.GetDaysCount(),
		h = length * 20,
		j, i;

	oRow.className = 'bxecp-bg-grid-row bxecpl-s' + this.scale;

	if (this.scale == 0)
		dayWidth = (cellWidth + 1) * this.oTime.count;
	else if(this.scale == 1)
		dayWidth = (cellWidth + 1) * this.oTime.count / 2;
	else if(this.scale == 2)
		dayWidth = (cellWidth + 1) * this.oTime.count / 4;
	else // this.scale == 3
		dayWidth = cellWidth;

	var wholeWidth = dayWidth * l;

	if (!this.oneGridDiv)
		this.oneGridDiv = oRow.insertCell(-1).appendChild(BX.create('DIV'));

	this.oneGridDiv.style.width = dayWidth * l + 'px';

	if (this.bMRShowed)
	{
		setTimeout(function(){_this.AdjustMRStub(true);}, 100);
		h += 60;
	}
	this.oneGridDiv.style.height = h + 'px';

	setTimeout(function(){_this.GridSetScrollLeft(_this.CheckScrollLeft(0, false));}, 100);
},

CheckScrollLeft: function(sl, bOffset)
{
	var
		sl = parseInt(sl),
		cellWidth = 80;

	if (this.scale == 0)
		minS = cellWidth * 2 * this.oTime.count / 2;
	else if(this.scale == 1)
		minS = cellWidth * this.oTime.count / 2;
	else if(this.scale == 2)
		minS = cellWidth * this.oTime.count / 4;
	else // this.scale == 3
		minS = cellWidth * 2;

	var maxS = Math.abs(parseInt(this.pGridDiv.scrollWidth) - this.gridDivWidth - minS);

	if (sl < minS)
	{
		sl = minS + sl;
		if (bOffset !== false)
			this.OffsetCurrentDate(- this.GetScrollOffset());
	}
	else if (sl > maxS)
	{
		sl = sl - minS;
		if (bOffset !== false)
			this.OffsetCurrentDate(this.GetScrollOffset());
	}

	return sl;
},

GridSetScrollLeft: function(sl)
{
	this.pGridTitleTable.style.left = '-' + sl + 'px';
	this.pGridDiv.scrollLeft = sl;
},

OffsetCurrentDate: function(offset, bMakeSel)
{
	var
		It, i, l = this.GetDaysCount(),
		oDate = new Date();

	oDate.setFullYear(this.currentDate.Y, this.currentDate.M, this.currentDate.D + offset);
	this.SetCurrentDate(oDate);
	this.GetTimelineLimits(true);
	this.DisplayDiagramEx();

	if (bMakeSel !== false && this.oSel.pDiv)
		this.oSel.Make({bFromTimeLimits : true, bSetTimeline: false});

	for (i = 0; i < l; i++)
	{
		It = this.pGTCells[i];
		this.SetDayInCell(It.pDay, It.pTitle, i);
	}
},

ClearUserList: function(bSilent)
{
	if (!bSilent && !confirm(EC_MESS.DelAllGuestsConf))
		return;

	var len = this.MRControll ? 2 : 0;
	// Del from list
	while(this.pUserListTable.rows.length > len)
		this.pUserListTable.deleteRow(0);

	// Del from arrays
	this.arGuests = [];
	this.arGuestsIds = [];

	// Decrease grid height
	this.BuildGrid(this.arGuests.length);
	this.ReColourTable();

	this.Freeze(true);
},

Resize: function(w, h)
{
	if (w < 660)
		w = 660; // Minimum width
	if (h < 300)
		h = 300; // Minimum height

	this.width = w;
	this.height = h;

	// Dialog
	this.oEC.ResizeDialogWin(this.pDialog.pWnd, w, h);

	// Container
	this.pCont.style.width = (w - 22) + 'px';
	this.pCont.style.height = (h - 70) + 'px';

	// Grid container
	var
		gridH = h - 70 - 60/*top cont*/ - 65/*bottom cont*/,
		gridW = w - 20;

	this.pGridCont.style.height = gridH + 'px';
	this.pGridTbl.style.height = gridH + 'px';

	//this.pGridTitle.style.width = (gridW - 180) + 'px';
	this.pUserListCont.style.height = (gridH - 45) + 'px';
	//this.pUserListDiv.style.height = (gridH - 40) + 'px';
	this.pGridCellCont.style.height = (gridH - 45) + 'px';

	this.gridDivWidth = gridW - 180 - 5;
	this.pGridDiv.style.width = (gridW - 180 - 5) + 'px';
	this.pGridTitleDiv.style.width = (gridW - 180 - 5) + 'px';

	// Resizer position
	this.pResizer.style.left = (w - 20) + 'px';
	this.pResizer.style.top = (h - 20) + 'px';
},

ResizerMouseDown: function()
{
	var _this = this;
	this.oPos = {top: parseInt(this.pDialog.pWnd.style.top, 10), left: parseInt(this.pDialog.pWnd.style.left, 10)};

	window[this.oEC.id + '_ResizerMouseUp'] = function(){_this.ResizerMouseUp()};
	window[this.oEC.id + '_ResizerMouseMove'] = function(e){_this.ResizerMouseMove(e)};

	BX.bind(document, "mouseup", window[this.oEC.id + '_ResizerMouseUp']);
	BX.bind(document, "mousemove", window[this.oEC.id + '_ResizerMouseMove']);
},

ResizerMouseUp: function()
{
	BX.unbind(document, "mouseup", window[this.oEC.id + '_ResizerMouseUp']);
	BX.unbind(document, "mousemove", window[this.oEC.id + '_ResizerMouseMove']);
	this.oSel.Adjust();
	this.oEC.SaveSettings();
},

ResizerMouseMove: function(e)
{
	var
		windowSize = BX.GetWindowSize(document),
		mouseX = e.clientX + windowSize.scrollLeft,
		mouseY = e.clientY + windowSize.scrollTop
		w = mouseX - this.oPos.left,
		h = mouseY - this.oPos.top;

	this.Resize(w, h);
},

SetUsersInfo: function()
{

},

SetCurrentDate: function(oDate)
{
	this.currentDate = {oDate: oDate, Y: oDate.getFullYear(), M: oDate.getMonth(), D: oDate.getDate()};
},

GetGridCellWidth: function()
{
	return this.scale == 3 ? 81 : 41;
},

GetTimelineLimits: function(bRecalc)
{
	if (bRecalc || !this.TimelineLimits)
	{
		var
			offset = this.GetScrollOffset(),
			cd = this.currentDate,
			D1 = new Date(), D2 = new Date();

		D1.setFullYear(cd.Y, cd.M, cd.D - offset);
		D2.setFullYear(cd.Y, cd.M, cd.D + (this.GetDaysCount() - offset - 1));
		D1.setHours(0, 0, 0, 0);
		D2.setHours(23, 59, 59, 999);
		this.TimelineLimits = {from: D1, to: D2};
	}

	return this.TimelineLimits;
},

GetScrollOffset: function()
{
	return this.scale == 3 ? 2 : 1;
},

GetDaysCount: function()
{
	if (this.scale == 2)
		return 15;
	if (this.scale == 3)
		return 20;
	return 10;
},

GetDayCellWidth: function()
{
	var
		tc = this.oTime.count,
		cw = this.GetGridCellWidth();

	switch(parseInt(this.scale))
	{
		case 0:
			return cw * tc * 2;
		case 1:
			return cw * tc;
		case 2:
			return Math.ceil(cw * tc / 2);
		case 3:
			return cw;
	}
},

SetFields: function(Params)
{
	var
		F = Params.from,
		T = Params.to,
		Ftime = zeroInt(F.getHours()) + ':' + zeroInt(F.getMinutes()),
		Ttime = zeroInt(T.getHours()) + ':' + zeroInt(T.getMinutes());

	this.oSel.curSelFT = {from: F, to: T};

	if (F && T)
	{
		this.pFrom.value = bxFormatDate(F.getDate(), F.getMonth() + 1, F.getFullYear());
		this.pTo.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());

		this.pFromTime.value = Ftime == '00:00' ? '' : Ftime;
		this.pToTime.value = Ttime == '00:00' ? '' : Ttime;

		this.pDuration.Set(T.getTime() - F.getTime());
	}
	else
	{
		this.pFrom.value = this.pTo.value = this.pFromTime.value = this.pToTime.value = '';
	}
},

GetFieldDate: function(type)
{
	var str;

	if (type == 'from')
		str = this.pFrom.value + ' ' + this.pFromTime.value;
	else
		str = this.pTo.value + ' ' + this.pToTime.value;

	return bxGetDate(str, true);
},

FieldDatesOnChange: function(bRefreshDur, bFrom)
{
	if (this.bFreezed)
		return false;

	if (bFrom && this.oSel)
		this.bFocusSelection = true;

	if (bFrom && !isNaN(parseInt(this.pDuration.pInp.value)))
		return this.pDuration.OnChange();

	var
		F = bxGetDate(this.pFrom.value + ' ' + this.pFromTime.value, true),
		T = bxGetDate(this.pTo.value + ' ' + this.pToTime.value, true);

	if (F && T)
	{
		if (bRefreshDur !== false)
			this.pDuration.Set(T.getTime() - F.getTime());

		this.oSel.Make({bFromTimeLimits : true, from: F, to: T, bSetFields: false});
	}
	else
	{
		this.oSel.Hide();
	}
},

CheckBTime: function(date)
{
	return date.getHours() == 0 && date.getMinutes() == 0;
},

ReColourTable: function()
{
	var i, l = this.pUserListTable.rows.length;
	if (this.bMRShowed)
	{
		l -= 2;
		this.MRControll.pLoc.className = (l / 2 == Math.round(l / 2)) ? '' : 'bx-grey';
	}

	for (i = 0; i < l; i++)
		this.pUserListTable.rows[i].className = (i / 2 == Math.round(i / 2)) ? '' : 'bx-grey';
},

LocationOnChange: function(P)
{
	if (P.ind === false)
	{
		this.ShowMRControll(false);
	}
	else
	{
		this.AddMR(P.ind);
		this.ShowMRControll();
	}
},

AddMR: function(ind)
{
	var
		_this = this,
		oMR = this.oEC.meetingRooms[ind];

	if (!oMR)
		return;

	if (!this.MRControll)
	{
		var
			r = this.pUserListTable.insertRow(-1),
			c = r.insertCell(-1);
		r.className = 'bxec-mr-title';
		c.colSpan = "2";
		c.innerHTML = '<b>' + EC_MESS.Location + '</b>';

		var
			r1 = this.pUserListTable.insertRow(-1),
			c1 = r1.insertCell(-1),
			c2 = r1.insertCell(-1);

		c1.innerHTML = '<img src="/bitrix/images/1.gif"/>';
		c1.className = 'bxecp-mr-icon';
		c2.onmouseover = function(){this.className = 'bxex-pl-u-over';};
		c2.onmouseout = function(){this.className = '';};

		var mrStubDiv = this.pGridDiv.appendChild(BX.create('DIV', {props:{className: 'bxecpl-mr-stub'}}));
		this.MRControll = {pTitle: r, pLoc: r1, pLocName: c2, stub: mrStubDiv};
	}

	this.MRControll.pLocName.innerHTML = '<div>' + (oMR.URL ? '<a href="' + oMR.URL+ '" target="_blank">' + bxSpCh(oMR.NAME) + '</a>' : bxSpCh(oMR.NAME)) + '</div>';
	var pDel = this.MRControll.pLocName.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', title: EC_MESS.FreeMR, className: 'bxecp-del'}}));
	pDel.onclick = function(){_this.pLocation.Set(false, '');};

	this.MRControll.pLoc.title = oMR.NAME;

	this.GetMRAccessibility(ind);
},

ShowMRControll: function(bShow)
{
	var
		dis = 'none',
		l1 = parseInt(this.arGuests.length),
		h = l1 * 20;
	bShow = bShow !== false;
	this.bMRShowed = bShow;

	if (bShow)
	{
		h += 60;
		dis = BX.browser.IsIE() ? 'inline' : 'table-row';
	}
	else
	{
		this.CleanMRDiagram();
	}

	if (this.oneGridDiv)
		this.oneGridDiv.style.height = h + 'px';

	this.oSel.Adjust();
	if (this.MRControll)
	{
		this.AdjustMRStub(bShow);
		this.MRControll.pLoc.className = (l1 / 2 == Math.round(l1 / 2)) ? '' : 'bx-grey';
		this.MRControll.pTitle.style.display = this.MRControll.pLoc.style.display = dis;
		this.MRControll.pTitle.className = 'bxec-mr-title';
	}
},

AdjustMRStub: function(bShow)
{
	if (this.MRControll && this.MRControll.stub)
	{
		this.MRControll.stub.style.display = bShow ? 'block' : 'none';
		if (bShow)
		{
			var w = parseInt(this.pGridTable.offsetWidth) - 1;
			if (isNaN(w) || w <= 0)
			{
				var _this = this;
				return setTimeout(function(){_this.AdjustMRStub(bShow);}, 100);
			}

			this.MRControll.stub.style.top = parseInt(this.arGuests.length) * 20 + 'px';
			this.MRControll.stub.style.width = (parseInt(this.pGridTable.offsetWidth) - 1) + 'px';
		}
	}
},

InitUserControll: function()
{
	var
		_this = this,
		ecid = this.oEC.id;

	if (!this.oEC.arConfig.bExtranet)
	{
		if (this.oEC.ownerType == 'GROUP')
		{
			this.pAddGrMembLink = BX(ecid + '_planner_add_from_group');
			this.pAddGrMembLink.onclick = function(){_this.AddGroupMembers()};
		}

		this.pAddFromStruc = BX(ecid + '_planner_add_from_struc');
		this.pAddFromStruc.onclick = function()
		{
			_this.bDenyClose = true;
			_this.oEC.oEditEventDialog.bDenyClose = true;
			oECUserContrEdEv.SetValue([]);
			oECUserContrEdEv.Show({className: 'bxec-user-con'});
		};
		jsUtils.addCustomEvent('onEmployeeSearchClose', function()
		{
			_this.oEC.oEditEventDialog.bDenyClose = false;
			_this.bDenyClose = false;
		}, [_this.oEED]);
	}

	this.pDelAll = BX(ecid + '_planner_del_all');
	this.pDelAll.onclick = function(){_this.ClearUserList();};

	window["PlannerAddGuest_" + ecid] = function(name){_this.oEC.EEUC.GetUsers(name, false);};
},

AttachSettings: function(Set)
{
	Set.planner_scale = this.scale;
	Set.planner_width = this.width;
	Set.planner_height = this.height;
	return Set;
},

GetScrollBarSize: function()
{
	if (!this._sbs)
	{
		var div = this.pDialog.pWnd.appendChild(BX.create('DIV', {props: {className: 'bxex-sbs'}, html: '&nbsp;'}));
		this._sbs = div.offsetWidth - div.clientWidth;
		setTimeout(function(){div.parentNode.removeChild(div);},50);
	}
	return this._sbs || 20;
}
};

function ECPlSelection(oPlanner)
{
	var _this = this;

	this.oPlanner = oPlanner;
	this.oEC = oPlanner.oEC;
	this.pGrid = oPlanner.pGridDiv;
	window[this.oEC.id + '_GridMouseMove'] = function(e){_this.MouseMove(e);};

	this.pGrid.onmousedown = function(e){_this.MouseDown(e);};
	this.pGrid.onmouseup = function(e){_this.MouseUp(e);};
}

ECPlSelection.prototype = {
Make: function(Params)
{
	var
		dcw, left, width,
		cellW = this.oPlanner.GetGridCellWidth(),
		_a, bInvert = false,
		from = Params.from,
		to = Params.to;

	if (!this.pDiv)
		this.Create();

	this.pDiv.style.display = 'block';
	if (Params.bFromTimeLimits)
	{
		Params.bSetTimeline = Params.bSetTimeline !== false;
		var tl = this.oPlanner.GetTimelineLimits(true);
		if (!from)
			from = this.curSelFT.from;
		if (!to)
			to = this.curSelFT.to;

		var
			off, offms,
			bOutOfLimits1 = from.getTime() < tl.from.getTime(),
			bOutOfLimits2 = to.getTime() > tl.to.getTime();

		if (bOutOfLimits1 || bOutOfLimits2)
		{
			if (Params.bSetTimeline)
			{
				// Get offset
				if (bOutOfLimits1)
					off = Math.round((from.getTime() - tl.from.getTime()) / 86400000) - 2;
				else
					off = Math.round((from.getTime() - tl.to.getTime()) / 86400000) + 5;

				this.oPlanner.OffsetCurrentDate(off, false);
			}
			else
			{
				this.Hide();
			}
		}

		var
			tl = this.oPlanner.GetTimelineLimits(true),
			dcw = this.oPlanner.GetDayCellWidth(),
			x1 = this._GetXByDate({date: from, tl: tl, dcw: dcw}),
			x2 = this._GetXByDate({date: to, tl: tl, dcw: dcw});

		if (this.oPlanner.CheckBTime(to) || x1 == x2)
			x2 = x2 + dcw;

		left = x1;
		width = x2 - x1 - 1;

		if (width <= 0)
			return false;

		this.curSelFT = {from: from, to: to};
	}
	else
	{
		if (from > to) // swap start_ind and end_ind
		{
			_a = from; from = to; to = _a;
			bInvert = true;
		}

		left = (from - 1) * cellW;
		width = (to) * cellW - left - 1;
	}

	this.pDiv.style.left = left + 'px'; // Set left
	this.pDiv.style.width = width + 'px'; // Set width

	this.Check(this.GetCurrent(), false, Params.bSetFields !== false);

	this.pMover.style.left = (Math.round(width / 2) - 6) + 'px'; // Set Mover

	// Focus
	if (this.oPlanner.bFocusSelection)
	{
		this.pGrid.scrollLeft = left - 50;
		this._bScrollMouseDown = true;
		this.MouseUp();
	}

	this.oPlanner.bFocusSelection = false;
},

Hide: function()
{
	if (this.pDiv)
		this.pDiv.style.display = 'none';
},

_GetXByDate: function(Params)
{
	var
		oTime = this.oPlanner.oTime,
		dayLen = 86400000,
		limFrom = Params.tl.from.getTime(),
		ts = Params.date.getTime(),
		dispTimeF = oTime.from.h + oTime.from.m / 60,
		x = Params.dcw * Math.floor((ts - limFrom) / dayLen),
		dfTime = Params.date.getHours() + Params.date.getMinutes() / 60,
		time = dfTime - dispTimeF;

	if (time > 0)
		x += Math.round((Params.dcw * time) / oTime.count);
	return x;
},

Create: function()
{
	this.pDiv = BX(this.oEC.id + '_plan_selection');
	var
		_this = this,
		imgL = this.pDiv.childNodes[0],
		imgR = this.pDiv.childNodes[1];

	imgL.onmousedown = function(e){_this.StartTransform({e: e, bLeft: true}); return BX.PreventDefault(e);};
	imgR.onmousedown = function(e){_this.StartTransform({e: e, bLeft: false}); return BX.PreventDefault(e);};

	this.pMover = this.pDiv.childNodes[2];
	this.pMover.onmousedown = function(e){_this.StartTransform({e: e, bMove: true}); return BX.PreventDefault(e);};

	this.bDenied = false;
	this.curSelFT = {};

	DenyDragEx(imgL);
	DenyDragEx(imgR);
	DenyDragEx(this.pDiv);

	this.Adjust();
},

Adjust: function(e)
{
	if (!this.pDiv)
		return;

	var
		h1 = parseInt(this.oPlanner.pGridTable.offsetHeight),
		h2 = parseInt(this.oPlanner.pGridCellCont.offsetHeight) - this.oPlanner.GetScrollBarSize();

	this.pDiv.style.height = Math.max(h1, h2) + 'px';
},

MouseDown: function(e)
{
	if (this.MoveParams)
		return;

	// Remember  scroll pos
	this._gridScrollLeft = parseInt(this.pGrid.scrollLeft);

	var
		grigPos = BX.pos(this.pGrid),
		mousePos = this.GetMouseXY(e);

	// Click on the scrollbar
	if ((grigPos.top + parseInt(this.pGrid.offsetHeight) - mousePos.y < this.oPlanner.GetScrollBarSize()) // Hor scroll
		|| (grigPos.left + parseInt(this.pGrid.offsetWidth) - mousePos.x < this.oPlanner.GetScrollBarSize())) // Vert scroll
	{
		this._bScrollMouseDown = true;
		return true;
	}

	this._bGridMouseDown = true;
	var ind = this.GetOverCellIndex({mousePos: mousePos, grigPos: grigPos});

	// Remember grigPos
	this.grigPos = grigPos;
	this.curSelection = {from: ind, to: ind};

	// Add mouse move handler
	BX.unbind(document, "mousemove", window[this.oEC.id + '_GridMouseMove']);
	BX.bind(document, "mousemove", window[this.oEC.id + '_GridMouseMove']);

	this.Make(this.curSelection);
},

MouseMove: function(e)
{
	if (this.MoveParams)
	{
		this.Transform({mousePos: this.GetMouseXY(e), grigPos: this.grigPos, MoveParams: this.MoveParams});
		this.TimeoutCheck();
	}
	else
	{
		var ind = this.GetOverCellIndex({mousePos: this.GetMouseXY(e), grigPos: this.grigPos});

		if (this.curSelection && ind != this.curSelection.to)
		{
			this.curSelection.to = ind;
			this.Make(this.curSelection);
		}
	}
},

MouseUp: function()
{
	if (this._bGridMouseDown)
	{
		BX.unbind(document, "mousemove", window[this.oEC.id + '_GridMouseMove']);
		if (this.MoveParams)
			this.MoveParams = false;

		this.Check(this.GetCurrent());
	}
	else if (this._bScrollMouseDown)
	{
		var sl = parseInt(this.pGrid.scrollLeft);
		if (sl != this._gridScrollLeft) // User move scroller - and we check and set correct 'middle' - position
			this.oPlanner.GridSetScrollLeft(this.oPlanner.CheckScrollLeft(sl));
	}

	this._bGridMouseDown = false;
	this._bScrollMouseDown = false;
},

StartTransform: function(Params)
{
	this._bDenyDragCell = true;

	if (!Params.bMove && this.oPlanner.pDuration.bLocked)
	{
		this.oPlanner.pDuration.LockerBlink();
		Params.bMoveBySide = !!Params.bLeft;
		Params.bLeft = null;
		Params.bMove = true;
	}
	this.MoveParams = Params;

	// Remember  scroll pos
	this._gridScrollLeft = parseInt(this.pGrid.scrollLeft);
	this._bGridMouseDown = true;

	var
		grigPos = BX.pos(this.pGrid),
		mousePos = this.GetMouseXY(Params.e);

	if (grigPos.top + parseInt(this.pGrid.offsetHeight) - mousePos.y < this.oPlanner.GetScrollBarSize()) // Click on the scrollbar
		return true;

	// Remember grigPos
	this.grigPos = grigPos;
	this.divCurPar = {left: parseInt(this.pDiv.style.left, 10), width: parseInt(this.pDiv.style.width, 10)};
	this.curSelection = false;

	// Add mouse move handler
	BX.unbind(document, "mousemove", window[this.oEC.id + '_GridMouseMove']);
	BX.bind(document, "mousemove", window[this.oEC.id + '_GridMouseMove']);
},

Transform: function(Params)
{
	if (!this.pDiv)
		return false;

	if (Params.MoveParams.bLeft) // Move left slider
	{
		var newLeft = parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - Params.grigPos.left);
		if (newLeft < 0)
			newLeft = 0;
		if (newLeft > this.divCurPar.left + this.divCurPar.width - 10)
			newLeft = this.divCurPar.left + this.divCurPar.width - 10;

		var newWidth = this.divCurPar.width + this.divCurPar.left - newLeft;

		this.pDiv.style.left = newLeft + 'px'; // Set new left
		this.pDiv.style.width = newWidth + 'px'; // Set new width
		this.pMover.style.left = (Math.round(newWidth / 2) - 6) + 'px'; // Set Mover
	}
	else if (!Params.MoveParams.bMove)// Move right slider
	{
		var newWidth = parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - Params.grigPos.left) - this.divCurPar.left;
		if (newWidth < 10)
			newWidth = 10;

		this.pDiv.style.width = newWidth + 'px'; // Set new width
		this.pMover.style.left = (Math.round(newWidth / 2) - 6) + 'px'; // Set Mover
	}
	else if (Params.MoveParams.bMove) // Move whole selection
	{
		var
			w = this.divCurPar.width / 2,
			mbs = Params.MoveParams.bMoveBySide;

		if (mbs === true) // left
			w =  0;
		else if(mbs === false)
			w =  this.divCurPar.width;

		var newLeft = Math.round(parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - Params.grigPos.left) - w);
		if (newLeft < 0)
			newLeft = 0;
		this.pDiv.style.left = newLeft + 'px'; // Set new left
	}
},

GetOverCellIndex: function(Params)
{
	var
		grigPos = Params.grigPos || BX.pos(this.pGrid),
		ind = Math.ceil((parseInt(this.pGrid.scrollLeft) + (Params.mousePos.x - grigPos.left)/*dx*/) / this.oPlanner.GetGridCellWidth());
	return ind;
},

GetCurrent: function()
{
	if (!this.pDiv)
		return;
	var
		tl = this.oPlanner.GetTimelineLimits(),
		dcw = this.oPlanner.GetDayCellWidth(),
		left = parseInt(this.pDiv.style.left, 10),
		width = parseInt(this.pDiv.style.width, 10) + 0.5;

	return {
		from: this._GetDateByX({x: left, fromD: tl.from, dcw: dcw}),
		to: this._GetDateByX({x: left + width, fromD: tl.from, dcw: dcw})
	};
},

_GetDateByX: function(Params)
{
	var
		oTime = this.oPlanner.oTime,
		day = Math.floor(Params.x / Params.dcw),
		time = oTime.count * (Params.x - day * Params.dcw) / Params.dcw,
		timeH = Math.floor(time),
		hour = oTime.from.h + timeH,
		_k = this.oPlanner.scale == 3 ? 10 : 5,
		min = Math.round((time - timeH) * 60 / _k) * _k,
		D = new Date(),
		Df = Params.fromD;

	D.setFullYear(Df.getFullYear(), Df.getMonth(), Df.getDate() + day);
	D.setHours(hour, min, 0, 0);

	return D;
},

Check: function(curSel, bBlink, bSetFields)
{
	if (!this.oPlanner.arACC || !this.pDiv)
		return;

	var
		bDeny = false, i, l,
		aac = this.oPlanner.arACC,
		f = curSel.from.getTime() + 1,
		t = curSel.to.getTime() - 1;

	this.arBusyGuests = {};
	if (this.oPlanner.bMRShowed && typeof this.oPlanner.arMRACC == 'object')
		aac = aac.concat(this.oPlanner.arMRACC);

	l = aac.length;

	for (i = 0; i < l; i++)
	{
		if (aac[i].from < t && aac[i].to > f)
		{
			bDeny = true;

			if (aac[i].uid > 0)
				this.arBusyGuests[aac[i].uid] = aac[i].acc || 'busy';

			if (bBlink !== false)
				this.oPlanner.BlinkDiagramDiv(aac[i].div);
		}
	}

	if (bSetFields !== false)
		this.oPlanner.SetFields(curSel);

	this.SetDenied(bDeny);
},

SetDenied: function(bDeny)
{
	if (!this.pDiv || this.bDenied == bDeny)
		return;

	this.bDenied = bDeny;
	if (bDeny)
		BX.addClass(this.pDiv, 'bxecp-sel-deny');
	else
		BX.removeClass(this.pDiv, 'bxecp-sel-deny');
},

TimeoutCheck: function()
{
	if (!this.bTimeoutCheck)
	{
		var _this = this;
		this.bTimeoutCheck = true;
		setTimeout(
			function()
			{
				_this.Check(_this.GetCurrent(), false);
				_this.bTimeoutCheck = false;
			},
			200
		);
	}
},

GetMouseXY: function(e)
{
	if (!e)
		e = window.event;

	var x = 0, y = 0;
	if (e.pageX || e.pageY)
	{
		x = e.pageX;
		y = e.pageY;
	}
	else if (e.clientX || e.clientY)
	{
		x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
		y = e.clientY + (document.documentElement.scrollTop || document.body.scrollTop) - document.documentElement.clientTop;
	}

	return {x: x, y: y};
}
};

function ECPlDuration(oPlanner)
{
	this.oPlanner = oPlanner;
	this.oEC = oPlanner.oEC;
	var
		_this = this,
		ecid = this.oEC.id;

	this.pInp = BX(ecid + '_pl_dur');
	this.pType = BX(ecid + '_pl_dur_type');
	this.pLock = BX(ecid + '_pl_dur_lock');

	this.bLocked = false;
	this.pLock.onclick = function(){_this.Lock();};
	this.pLock.onmouseover = function(){BX.addClass(this, 'icon-hover');};
	this.pLock.onmouseout = function(){BX.removeClass(this, 'icon-hover');};

	this.pInp.onclick = function(){_this.ShowPopup();};

	this.pType.onchange = this.pInp.onchange = function(){_this.OnChange();};
}

ECPlDuration.prototype = {
Set: function(ms)
{
	var
		days,
		type = 'min',
		val = Math.round(ms / (1000 * 60 * 5)) * 5,
		hours = val / 60;

	if (val <= 0)
		return false;

	if (hours == Math.round(hours))
	{
		val = hours;
		type = 'hour';
		days = hours / this.oPlanner.oTime.count;
		days2 = hours / 24;

		if (days == Math.round(days))
		{
			type = 'day';
			val = days;
		}
		else if(days2 == Math.round(days2))
		{
			type = 'day';
			val = days2;
		}
	}

	this.pInp.value = val;
	this.pType.value = type;
},

Lock: function(bLock)
{
	this.bLocked = !this.bLocked;
	if (this.bLocked)
		BX.addClass(this.pLock, 'bxecpl-lock-pushed');
	else
		BX.removeClass(this.pLock, 'bxecpl-lock-pushed');
},

LockerBlink: function()
{
	if (!this.bLocked)
		return;
	var
		pel = this.pLock,
		iter = 0,
		origClass = 'bxecpl-lock-dur bxecpl-lock-pushed',
		warnClass = "bxecpl-lock-dur icon-blink";

	if (origClass != warnClass)
	{
		var blInt = setInterval(
			function()
			{
				pel.className = (pel.className == warnClass) ? origClass : warnClass;
				if (++iter > 5)
					clearInterval(blInt);
			},250
		);
	}
},

OnChange: function()
{
	var
		dur, // duration in minutes
		Date = this.oPlanner.GetFieldDate('from', false),
		count = parseInt(this.pInp.value, 10),
		type = this.pType.value;

	if (isNaN(count) || count <= 0)
		count = 1;
	else if (type == 'min')
		count = Math.round(count / 5) * 5;

	this.pInp.value = count;

	if (Date)
	{
		if (type == 'min')
			dur = count;
		if (type == 'hour')
			dur = count * 60;
		else if (type == 'day')
			dur = count * 60 * 24;

		Date.setTime(Date.getTime() + dur * 60 * 1000); // Set end of the event
		this.oPlanner.pTo.value = bxFormatDate(Date.getDate(), Date.getMonth() + 1, Date.getFullYear());
		var Ttime = zeroInt(Date.getHours()) + ':' + zeroInt(Date.getMinutes());
		this.oPlanner.pToTime.value = Ttime == '00:00' ? '' : Ttime;
	}

	this.oPlanner.FieldDatesOnChange(false);
},

ShowPopup: function()
{
	var _this = this;
	this.pInp.select();

	if (this.bPopupShowed)
		return this.ClosePopup();

	if (!this.Popup)
		this.CreatePopup();

	this.Popup.style.display = 'block';
	this.bPopupShowed = true;
	this.oPlanner.bDenyClose = true;

	this.Popup.style.zIndex = 1000;
	var pos = BX.pos(this.pInp);
	jsFloatDiv.Show(this.Popup, pos.left + 2, pos.top + 22, 5, false, false);

	// Add events
	BX.bind(document, "keypress", window['BXEC_DURDEF_CLOSE_' + this.oEC.id]);
	setTimeout(function(){BX.bind(document, "click", window['BXEC_DURDEF_CLOSE_' + _this.oEC.id]);}, 1);
},

ClosePopup: function()
{
	this.Popup.style.display = 'none';
	this.bPopupShowed = false;
	this.oPlanner.bDenyClose = false;
	jsFloatDiv.Close(this.Popup);
	BX.unbind(document, "keypress", window['BXEC_DURDEF_CLOSE_' + this.oEC.id]);
	BX.unbind(document, "click", window['BXEC_DURDEF_CLOSE_' + this.oEC.id]);
},

CreatePopup: function()
{
	this.arDefValues = [
		{val: 15, type: 'min', title: '15 ' + EC_MESS.DurDefMin},
		{val: 30, type: 'min', title: '30 ' + EC_MESS.DurDefMin},
		{val: 1, type: 'hour', title: '1 ' + EC_MESS.DurDefHour1},
		{val: 2, type: 'hour', title: '2 ' + EC_MESS.DurDefHour2},
		{val: 3, type: 'hour', title: '3 ' + EC_MESS.DurDefHour2},
		{val: 4, type: 'hour', title: '4 ' + EC_MESS.DurDefHour2},
		{val: 1, type: 'day', title: '1 ' + EC_MESS.DurDefDay}
	];

	var
		_this = this,
		pRow, i, l = this.arDefValues.length;

	this.Popup = document.body.appendChild(BX.create("DIV", {props: {className: "bxecpl-dur-popup"}}));

	for (i = 0; i < l; i++)
	{
		pRow = this.Popup.appendChild(BX.create("DIV", {props: {id: 'ecpp_' + i, title: this.arDefValues[i].title}, text: this.arDefValues[i].title}));

		pRow.onmouseover = function(){this.className = 'bxecpldur-over';};
		pRow.onmouseout = function(){this.className = '';};
		pRow.onclick = function()
		{
			var cur = _this.arDefValues[this.id.substr('ecpp_'.length)];
			_this.pInp.value = cur.val;
			_this.pType.value = cur.type;
			_this.OnChange();
			_this.ClosePopup();
		};
	}

	window['BXEC_DURDEF_CLOSE_' + this.oEC.id] = function(e){_this.ClosePopup();};
}
};









