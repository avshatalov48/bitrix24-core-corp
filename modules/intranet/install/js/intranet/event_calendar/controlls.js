var ECCalMenu = function(oEC)
{
	this.bCreated = false;
	this.oEC = oEC;
}

ECCalMenu.prototype = {
Create: function()
{
	this.ecid = this.oEC.id;
	var
		_this = this,
		pWin = BX.create('DIV', {
			props: {id: 'bxec_cal_menu_' + this.ecid, className : 'bxec-cal-menu-div'},
			events: {mouseover: function(){_this.bOver = true;}, mouseout:function(){_this.bOver = false;}}
		}),
		tbl = BX.create('TABLE', {props: {className: 'bxec-cal-menu-tbl'}});

	this._ItemsTbl = tbl;
	this.Items = {};
	if (!this.oEC.bReadOnly)
	{
		this.Items.Edit = this.AddItem(tbl, EC_MESS.Edit, EC_MESS.EditCalendarTitle, function(){_this.oEC.ShowEditCalDialog(_this.currentCalendar); _this.Close();}, false, 'bxec-edit');
	}

	this.Items.AddToSP = this.AddItem(tbl, EC_MESS.CalAdd2SP, EC_MESS.CalAdd2SPTitle, function(){_this.oEC.Add2SPCalendar(_this.currentCalendar); _this.Close();}, false, 'bxec-add2sp');

	this.outlookRun = function()
	{
		if(_this.currentCalendar.OUTLOOK_JS && _this.currentCalendar.OUTLOOK_JS.length > 0)
			try{eval(_this.currentCalendar.OUTLOOK_JS);}catch(e){};
		_this.Close();
	}

	//this.Items.Outlook = this.AddItem(tbl, EC_MESS.ConnectToOutlook, EC_MESS.ConnectToOutlookTitle, function()
	//{
	//	if (!window.jsOutlookUtils)
	//		return BX.loadScript('/bitrix/js/intranet/outlook.js', _this.outlookRun);
	//	_this.outlookRun();
	//}, true, 'bxec-outlook');

	this.Items.Export = this.AddItem(tbl, EC_MESS.Export, EC_MESS.ExportTitle, function(){_this.oEC.ShowExportCalDialog(_this.currentCalendar); _this.Close();}, true, 'bxec-export');

	if (!this.oEC.bReadOnly)
	{
		this.Items.Del = this.AddItem(tbl, EC_MESS.Delete, EC_MESS.DelCalendarTitle, function(){_this.oEC.DeleteCalendar(_this.currentCalendar);}, false, 'bxec-del');
	}

	pWin.appendChild(tbl);

	window['BXEC_CalMenu_OnKeypress_' + this.ecid] = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.Close();
	};
	window['BXEC_CalMenu_OnClick_' + this.ecid] = function(e)
	{
		if(!e) e = window.event;
		if(!e) return;
		if (!_this.bShow || _this.bOver)
			return;
		_this.Close();
	};

	this.pWin = document.body.appendChild(pWin);
	this.bCreated = true;
},

AddItem: function(tbl, name, title, action, bHide, icon)
{
	var r = tbl.insertRow(-1);
	var cell = r.insertCell(-1);
	BX.adjust(cell, {
		props: {title: title},
		events: {click: action, mouseover: function(){BX.addClass(this, 'bxec-cm-td-over');}, mouseout: function(){BX.removeClass(this, 'bxec-cm-td-over');}},
		html: '<div class="bxec-cm-item"><img class="' + (icon || '') + '" src="/bitrix/images/1.gif">' + name + '</div>'
	});

	if (bHide)
		r.style.display = 'none';
	return r;
},

Show: function(oCalen, pCell, bSuperpose)
{
	if (!this.bCreated)
		this.Create();

	if(this.bShow)
	{
		this.Close();
		if (this.currentCalendar && this.currentCalendar.ID == oCalen.ID)
			return;
	}

	if (bSuperpose)
	{
		this.ShowItem('Edit', false);
		this.ShowItem('Del', false);
		this.ShowItem('AddToSP', false);
		this.ShowItem('Outlook', false);

		if (!this.Items.Hide)
			this.Items.Hide = this.AddItem(this._ItemsTbl, EC_MESS.CalHide, EC_MESS.CalHideTitle, function(){_this.oEC.HideSPCalendar(_this.currentCalendar); _this.Close();}, false, 'bxec-hide');
		else
			this.ShowItem('Hide', true);
	}
	else
	{
		var bReadOnly = this.bReadOnly || oCalen.CALDAV_CON;
		this.ShowItem('Edit', !bReadOnly);
		this.ShowItem('Del', !bReadOnly);

		this.ShowItem('Outlook', true);
		this.ShowItem('Hide', false);
		this.ShowItem('AddToSP', this.oEC.bSuperpose && !oCalen._bro && this.oEC.arConfig.allowAdd2SP);
	}
	this.ShowItem('Export', oCalen.EXPORT);

	BX.bind(document, "keypress", window['BXEC_CalMenu_OnKeypress_' + this.ecid]);
	var _this = this;
	setTimeout(function()
	{
		BX.unbind(document, "click", window['BXEC_CalMenu_OnClick_' + _this.ecid]);
		BX.bind(document, "click", window['BXEC_CalMenu_OnClick_' + _this.ecid]);
	}, 1);

	this.pWin.style.display = 'block';
	this.bShow = true;
	this.currentCalendar = oCalen;
	var h = parseInt(this.pWin.firstChild.offsetHeight);
	var w = 200;

	if (w < this._ItemsTbl.offsetWidth)
		w = parseInt(this._ItemsTbl.offsetWidth) + 20;

	this.oEC.ResizeDialogWin(this.pWin, w, h);

	var pos = BX.pos(pCell);
	var top = pos.top + 18;
	var left = pos.left + 2;

	jsFloatDiv.Show(this.pWin, left, top, 5, false, false);
},

ShowItem: function(ItemName, bShow)
{
	var dis = BX.browser.IsIE() ? 'inline' : 'table-row';
	if (this.Items[ItemName])
		this.Items[ItemName].style.display = bShow ? dis : 'none';
},

Close: function()
{
	this.bShow = false;
	this.pWin.style.display = 'none';
	jsFloatDiv.Close(this.pWin);
	BX.unbind(document, "keypress", window['BXEC_CalMenu_OnKeypress_' + this.ecid]);
	BX.unbind(document, "click", window['BXEC_CalMenu_OnClick_' + this.ecid]);
}
}

var ECDialog = function(oEC)
{
	this.bCreated = false;
	this.oEC = oEC;
}

var ECUserControll = function(oEC)
{
	this.oEC = oEC;
	this.oEED = oEC.oEditEventDialog;
	var _this = this;

	window["EdEventAddGuest_" + oEC.id] = function(name){_this.GetUsers(name);};
	window["oECUserContrEdEvOnSave"] = function(arGuests)
	{
		var i, l = arGuests.length, arSelected = [];
		for (i = 0; i < l; i++)
			if(arGuests[i])
				arSelected.push({id: arGuests[i].ID, name: arGuests[i].NAME, status: 'Q'});

		if (_this.oEED.bAddOwner || window._bx_add_cur_user) // Add author
			_this.AddOwner(l > 0);

		_this.AddUsersEx(arSelected, false);
	};

	this.pNotice = BX(oEC.id + '_edev_uc_notice');
	this.pGuestsTable = BX(oEC.id + '_edev_guests_table');
	this.pDelAllLink = BX(oEC.id + '_edev_del_all_guests');

	if (!this.oEC.arConfig.bExtranet)
	{
		if (this.oEC.ownerType == 'GROUP')
		{
			this.pAddGrMembLink = BX(oEC.id + '_add_from_group');
			this.pAddGrMembLink.onclick = function(){_this.AddGroupMembers()};
		}
		this.pAddFromStruc = BX(oEC.id + '_add_from_struc');
		this.pAddFromStruc.onclick = function()
		{
			_this.oEED.bDenyClose = true;
			oECUserContrEdEv.SetValue([]);
			oECUserContrEdEv.Show({className: 'bxec-user-con'});
		};
		jsUtils.addCustomEvent('onEmployeeSearchClose', function(){_this.oEED.bDenyClose = false;}, [_this.oEED]);
	}

	this.pDelAllLink.onclick = function(){_this.DelAllUsers()};
}

ECUserControll.prototype = {
GetUsers : function(name, bCheckAcc)
{
	if (name.length <= 0)
		return;
	var
		_this = this,
		arPost = {name : name};

	if (bCheckAcc !== false)
	{
		var
			fd = bxGetDate(this.oEED.oFrom.value + ' ' + this.oEED.oFromTime.value),
			td = bxGetDate(this.oEED.oTo.value + ' ' + this.oEED.oToTime.value);

		if (fd)
		{
			arPost.from = bxFormatDate(fd.date, fd.month, fd.year) + (fd.bTime ? ' ' + zeroInt(fd.hour) + ':' + zeroInt(fd.min) : '');
			if (td)
				arPost.to = bxFormatDate(td.date, td.month, td.year) + (td.bTime ? ' ' + zeroInt(td.hour) + ':' + zeroInt(td.min) : '');
		}
	}

	this.oEC.Request({
		postData: this.oEC.GetPostData('get_guests', arPost),
		handler: function(result)
		{
			if (!window._bx_result)
				return false;

			if (_this.oEED.bAddOwner || window._bx_add_cur_user) // Add author
				_this.AddOwner(window._bx_result.length > 0);

			_this.AddUsersEx(window._bx_result, true);
			_this.RecolorGuestTable();
		}
	});
},

AddUsers : function(arUsers, bRequest, bFirst)
{
	this.ShowNotice();
	if (!arUsers || arUsers.length <= 0 && bRequest)
		return this.ShowNotice('not_found');

	var
		busyCount = 0,
		i, l = arUsers.length,
		_this = this,
		ri,
		tbl = this.pGuestsTable, //bShowDelAll = false,
		r, cName, c, user, delBut, ind, bDel, status;

	for(i = 0; i < l; i++)
	{
		user = arUsers[i];
		if (user.bHost)
		{
			this.AddOwner();
			continue;
		}

		bDel = user.bDel !== false;
		if (this.oEED.Guests[user.id] && this.oEED.Guests[user.id].user)
			continue;

		r = tbl.insertRow(bFirst ? 1 : -1);
		ind = r.rowIndex;
		r.className = (ind / 2 == Math.round(ind / 2)) ? 'bxec-guest-r1' : 'bxec-guest-r2';

		c = r.insertCell(-1);
		c.style.width = '20px';
		status = user.status ? user.status.toLowerCase() : 'Q';
		cn = 'bxec-guest-stat-' + status;
		c.appendChild(BX.create('IMG', {props:{src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-g-status ' + cn, title: EC_MESS['GuestStatus_' + status]}}));

		cName = r.insertCell(-1);
		cName.innerHTML = this.oEC.GetUserProfileLink(user.id, true, user) + this.GetBusyHTML(user);

		c = r.insertCell(-1);
		c.style.width = '20px';

		delBut = c.appendChild(BX.create('IMG', {props:{src: '/bitrix/images/1.gif', className: 'bxec-iconkit ' + (bDel ? 'bxec-g-del-but' : 'bxec-g-del-but-d'), title: bDel ? EC_MESS.DelGuestTitle : EC_MESS.CantDelGuestTitle, id: 'bxec_del_' + user.id}}));
		delBut.onclick = function(){_this.DelUser(this, bDel);};

		this.oEED.GuestsLength++;
		this.oEED.Guests[user.id] = {user: user, row: r};

		if (user.busy)
		{
			this.ShowNotice('user_busy', this.oEC.GetUserProfileLink(user.id, true, user));
			busyCount++;
		}
	}

	if (busyCount > 1 && bRequest)
	{
		this.ShowNotice('users_busy');
	}

	if (this.oEED.GuestsLength > 0)
	{
		BX.addClass(tbl, 'bxec-non-empty');
		this.pDelAllLink.style.display = 'inline';
	}
	else
	{
		BX.removeClass(tbl, 'bxec-non-empty');
		this.pDelAllLink.style.display = 'none';
	}
},

AddUsersEx : function(arr, bRequest, bFirst, bTimeout)
{
	if (!arr || typeof arr != 'object')
		return;

	if (this.oEC.Planner && this.oEC.Planner.bOpened)
		this.oEC.Planner.AddGuests(arr, bFirst, bTimeout);
	else
		this.AddUsers(arr, bRequest, bFirst);
},

DelAllUsers : function(bSilent)
{
	if (!bSilent && !confirm(EC_MESS.DelAllGuestsConf))
		return;
	var
		i,
		newGuests = {}, newLen = 0, g,
		tbl = this.pGuestsTable,
		_this = this;

	if (this.oEED.Guests)
	{
		for(i in this.oEED.Guests)
		{
			g = this.oEED.Guests[i];
			if (!g || typeof g != 'object' || !g.user)
				continue;

			tbl.deleteRow(this.oEED.Guests[i].row.rowIndex);
		}
	}

	this.oEED.bAddOwner = true;
	this.oEED.Guests = newGuests;
	this.oEED.GuestsLength = newLen;
	this.pDelAllLink.style.display = 'none';
	if (newLen == 0)
		BX.removeClass(tbl, 'bxec-non-empty');
	this.ShowNotice();
},

AddOwner : function(bTimeout)
{
	this.AddUsersEx([{
		busy: false,
		id: this.oEC.arConfig.userId,
		name: this.oEC.arConfig.userName,
		status: 'Y',
		bDel: this.oEC.ownerType == 'USER' ? false : true
	}], false, true, bTimeout !== false);

	this.oEED.bAddOwner = false; // We already add owner
},

CheckUsersAccessibility : function()
{
	var
		u,
		from = '', to = '',
		_this = this,
		fd = bxGetDate(this.oEED.oFrom.value + ' ' + this.oEED.oFromTime.value),
		td = bxGetDate(this.oEED.oTo.value + ' ' + this.oEED.oToTime.value),
		arPost = {guests : []};

	if (!fd || this.oEED.GuestsLength < 1)
		return false;

	arPost.from = bxFormatDate(fd.date, fd.month, fd.year) + (fd.bTime ? ' ' + zeroInt(fd.hour) + ':' + zeroInt(fd.min) : '');
	if (td)
		arPost.to = bxFormatDate(td.date, td.month, td.year) + (td.bTime ? ' ' + zeroInt(td.hour) + ':' + zeroInt(td.min) : '');

	for(i in this.oEED.Guests)
		if (this.oEED.Guests[i])
			arPost.guests.push(bxInt(i));

	if (this.oEED.currentEvent && !this.oEED.bNew)
		arPost.event_id = this.oEED.currentEvent.ID;

	this.oEC.Request({
		postData: this.oEC.GetPostData('check_guests', arPost),
		handler: function(result)
		{
			if (!window._bx_result)
				return false;
			var arUsers = window._bx_result;
			if (!arUsers || arUsers.length <= 0)
				return true;
			_this.DelAllUsers(true);
			_this.AddUsers(arUsers, true);
		}
	});
},

DelUser : function(pBut, bDel)
{
	if (bDel === false)
	{
		if (confirm(EC_MESS.DelOwnerConfirm))
			this.DelAllUsers(true);
		return true;
	}

	if (!confirm(EC_MESS.DelGuestConf))
		return;
	var
		id = pBut.id.toString().substr(9),
		tbl = this.pGuestsTable;
	if (!this.oEED.Guests[id] || !this.oEED.Guests[id].user)
		return;

	this.oEED.bGuestWasDeleted = true;
	tbl.deleteRow(this.oEED.Guests[id].row.rowIndex);
	this.RecolorGuestTable();
	this.oEED.Guests[id] = null;

	this.oEED.GuestsLength--;
	if (this.oEED.GuestsLength <= 0)
	{
		BX.removeClass(tbl, 'bxec-non-empty');
		this.pDelAllLink.style.display = 'none';
	}
	this.ShowNotice();
},

RecolorGuestTable : function()
{
	var
		i,
		tbl = this.pGuestsTable,
		l = tbl.rows.length;
	if (!tbl || l < 2)
		return;
	for(i = 1; i < l; i++)
		tbl.rows[i].className = (i / 2 == Math.round(i / 2)) ? 'bxec-guest-r1' : 'bxec-guest-r2';
},

GetBusyHTML : function(user)
{
	if (!user || !user.busy || user.status == 'Y')
		return '';
	return ' <span title="' + EC_MESS['UserAccessability'] + ': ' + EC_MESS['Acc_' + user.busy] + '">(' + EC_MESS['Acc_' + user.busy] + ')</span>';
},

ShowNotice : function(id, userName)
{
	if (!id)
	{
		this.pNotice.innerHTML = '';
		this.pNotice.style.visibility = 'hidden';
		return;
	}
	this.pNotice.style.visibility = 'visible';

	if (id == 'not_found')
		this.pNotice.innerHTML = EC_MESS.UsersNotFound;
	else if (id == 'user_busy')
		this.pNotice.innerHTML = EC_MESS.UserBusy.replace('#USER#', userName);
	else if (id == 'users_busy')
		this.pNotice.innerHTML = EC_MESS.UsersNotAvailable;

	var _this = this;
},

AddFromStructureDialog: function()
{
	var arSelected = [], i;
	this.oEC.oCompStrucDialog.arSelectedCh = [];

	if (!this.oEC.oCompStrucDialog.arCheckboxes)
	{
		this.oEC.oCompStrucDialog.arCheckboxes = [];
		var
			arCh = this.oEC.oCompStrucDialog.Cont.getElementsByTagName('INPUT'),
			l = arCh.length, val;

		for (i = 0; i < l; i++)
		{
			if (arCh[i].id.substr(0, 10) == 'vscd_user_')
			{
				val = arCh[i].value.split('||');
				this.oEC.oCompStrucDialog.arCheckboxes.push({ch:arCh[i], id: val[0], name: val[1]});

				if (arCh[i].checked)
					arSelected.push({id: val[0], name: val[1], status: 'Q'});
			}
		}
	}
	else
	{
		var
			arCh = this.oEC.oCompStrucDialog.arCheckboxes,
			l = arCh.length;

		for (i = 0; i < l; i++)
		{
			if (arCh[i].ch.checked)
				arSelected.push({id: arCh[i].id, name: arCh[i].name, status: 'Q'});
		}
	}

	this.AddUsersEx(arSelected, false);

	this.oEC.oCompStrucDialog.Close();
},

AddGroupMembers : function()
{
	var
		_this = this,
		fd = bxGetDate(this.oEED.oFrom.value + ' ' + this.oEED.oFromTime.value),
		td = bxGetDate(this.oEED.oTo.value + ' ' + this.oEED.oToTime.value),
		arPost = {};

	if (fd && td)
	{
		arPost.from = bxFormatDate(fd.date, fd.month, fd.year) + (fd.bTime ? ' ' + zeroInt(fd.hour) + ':' + zeroInt(fd.min) : '');
		arPost.to = bxFormatDate(td.date, td.month, td.year) + (td.bTime ? ' ' + zeroInt(td.hour) + ':' + zeroInt(td.min) : '');
	}

	this.oEC.Request({
		postData: this.oEC.GetPostData('get_group_members', arPost),
		handler: function(result)
		{
			if (!window._bx_result)
				return false;

			if (window._bx_add_cur_user) // Add author every time if author is group member
				_this.AddOwner(window._bx_result.length > 0);

			_this.AddUsersEx(window._bx_result, false);
		}
	});
}
}

var ECLocation = function(oEC, k, handler)
{
	this.oEC = oEC;
	this.oEED = oEC.oEditEventDialog;
	this.handler = handler || false;

	var
		_this = this,
		ecid = this.oEC.id;

	this.pInp = BX(ecid + '_planner_location' + k);
	this.curInd = false;

	if (this.oEC.bUseMR)
	{
		this.fid = 'BXEC_LOC_CLOSE_' + k + this.oEC.id;
		this.Popup = document.body.appendChild(BX.create("DIV", {props:{className: "bxecpl-loc-popup"}}));

		this.pInp.onclick = function(e)
		{
			if (this.value == EC_MESS.SelectMR)
			{
				this.value = '';
				this.className = '';
			}
			_this.ShowPopup();
			return BX.PreventDefault(e);
		};

		this.pInp.onfocus = function(e)
		{
			if (this.value == EC_MESS.SelectMR)
			{
				this.value = '';
				this.className = '';
			}
			_this.ShowPopup();
		};

		this.pInp.onblur = function()
		{
			if (!_this.bPopupShowed)
				_this.OnChange();
		};

		window[this.fid + '_k'] = function(e) {_this.ClosePopup(false);};
		window[this.fid] = function(e) {_this.ClosePopup();};
	}
	else
	{
		this.pInp.className = 'ec-no-rm';
		this.pInp.onblur = function(){_this.OnChange();};
	}
}

ECLocation.prototype = {
ShowPopup: function()
{
	if (this.bPopupShowed)
		return;

	var _this = this;
	this.pInp.select();

	if (window['BXEC_DURDEF_CLOSE_' + this.oEC.id])
		window['BXEC_DURDEF_CLOSE_' + this.oEC.id]();

	if (!this.bPopupCreated)
		this.CreatePopup();

	this.Popup.style.display = 'block';
	this.bPopupShowed = true;

	if (this.oEC.Planner)
		this.oEC.Planner.bDenyClose = true;

	// Add events
	BX.bind(document, "keypress", window[this.fid + '_k']);
	setTimeout(function(){BX.bind(document, "click", window[_this.fid]);}, 100);

	//GetRealPos
	this.Popup.style.zIndex = 1000;
	var pos = BX.pos(this.pInp);

	jsFloatDiv.Show(this.Popup, pos.left + 1, pos.top + 24, 5, false, false);
},

ClosePopup: function(bCheck)
{
	this.Popup.style.display = 'none';
	this.bPopupShowed = false;
	if (this.oEC.Planner)
		this.oEC.Planner.bDenyClose = false;

	jsFloatDiv.Close(this.Popup);

	if (bCheck !== false && this.pInp.value == '')
		this.OnChange();

	BX.unbind(document, "keypress", window[this.fid + '_k']);
	BX.unbind(document, "click", window[this.fid]);
},

CreatePopup: function()
{
	var
		arMR = this.oEC.meetingRooms,
		_this = this, el,
		pRow, i, l = arMR.length;

	this.bPopupCreated = true;

	for (i = 0; i < l; i++)
	{
		pRow = this.Popup.appendChild(BX.create("DIV", {
			props: {id: 'bxecmr_' + i, title: BX.util.htmlspecialcharsback(arMR[i].DESCRIPTION)},
			text: BX.util.htmlspecialcharsback(arMR[i].NAME),
			events: {
				mouseover: function(){this.className = 'bxecplloc-over';},
				mouseout: function(){this.className = '';},
				click: function()
				{
					var ind = this.id.substr('bxecmr_'.length);
					_this.pInp.value = BX.util.htmlspecialcharsback(arMR[ind].NAME);
					_this.curInd = ind;
					_this.OnChange();
					_this.ClosePopup();
				}
			}
		}));

		if (arMR[i].URL)
			pRow.appendChild(BX.create('A', {props: {href: arMR[i].URL, className: 'bxecplloc-view', target: '_blank', title: EC_MESS.OpenMRPage}, html: '<img src="/bitrix/images/1.gif" />'}));
	}
},

OnChange: function()
{
	var val = this.pInp.value;
	if (this.oEC.bUseMR)
	{
		if (this.pInp.value == '' || this.pInp.value == EC_MESS.SelectMR)
		{
			this.pInp.value = EC_MESS.SelectMR;
			this.pInp.className = 'ec-label';
			val = '';
		}
		else
		{
			this.pInp.className = '';
		}
	}

	if (isNaN(parseInt(this.curInd)) || this.curInd !==false && val != this.oEC.meetingRooms[this.curInd].NAME)
		this.curInd = false;
	else
		this.curInd = parseInt(this.curInd);

	if (this.handler)
		this.handler({ind: this.curInd, value: val});
},

Set: function(ind, val, bOnChange)
{
	this.curInd = ind;
	if (this.curInd !== false)
		this.pInp.value = BX.util.htmlspecialcharsback(this.oEC.meetingRooms[this.curInd].NAME);
	else
		this.pInp.value = val;

	if (bOnChange !== false)
		this.OnChange();
},

Get: function(ind)
{
	var
		id = false;
	if (typeof ind == 'undefined')
		ind = this.curInd;

	if (ind !== false && this.oEC.meetingRooms[ind])
		id = this.oEC.meetingRooms[ind].ID;
	return id;
},

GetMRInd: function(mrid)
{
	for (var i = 0, l = this.oEC.meetingRooms.length; i < l; i++)
		if (this.oEC.meetingRooms[i].ID == mrid)
			return i;
	return false;
},

Deactivate: function(bDeactivate)
{
	if (this.pInp.value == '' || this.pInp.value == EC_MESS.SelectMR)
	{
		if (bDeactivate)
		{
			this.pInp.value = '';
			this.pInp.className = 'ec-no-rm';
		}
		else if (this.oEC.bUseMR)
		{
			this.pInp.value = EC_MESS.SelectMR;
			this.pInp.className = 'ec-label';
		}
	}
	this.pInp.disabled = bDeactivate;
}
};

var ECBanner = function(oEC)
{
	var _this = this;
	this.oEC = oEC;

	BX(oEC.id + '_ban_close').onclick = function(){_this.CloseBanner();};

	this.pOutlSel = BX(oEC.id + '_outl_sel');
	this.pOutlSel.onclick = function(){_this.ShowPopup('outlook');};
	this.pOutlSel.onmouseover = function(){BX.addClass(this, "bxec-ban-over");};
	this.pOutlSel.onmouseout = function(){BX.removeClass(this, "bxec-ban-over");};

	if (this.oEC.arConfig.bCalDAV)
	{
		this.pMobSel = BX(oEC.id + '_mob_sel');
		this.pMobSel.onclick = function(){_this.ShowPopup('mobile');};
		this.pMobSel.onmouseover = function(){BX.addClass(this, "bxec-ban-over");};
		this.pMobSel.onmouseout = function(){BX.removeClass(this, "bxec-ban-over");};
	}

	if (this.oEC.arConfig.bExchange)
	{
		var pLink = BX(oEC.id + '_exch_sync');
		if (pLink)
			pLink.onclick = function(){_this.oEC.SyncExchange();return false;};
	}

	this.Popup = {};

	if (!window.jsOutlookUtils)
		return BX.loadScript('/bitrix/js/intranet/outlook.js', _this.outlookRun);
}

ECBanner.prototype =
{
	ShowPopup: function(type)
	{
		var _this = this;
		if (!this.Popup[type])
			this.CreatePopup(type);

		if (this.Popup[type].bShowed)
			return this.ClosePopup(type);

		this.ClosePopup(type);
		var pWnd = this.Popup[type].pWin.Get();
		this.Popup[type].bShowed = true;

		var
			rowsCount = 0,
			i, l = this.oEC.arCalendars.length, cal, name, pItem;

		BX.cleanNode(pWnd);

		if (type == 'mobile')
		{
			rowsCount++;
			pItem = pWnd.appendChild(BX.create("DIV", {
				props: {id: 'ecpp_all', title: EC_MESS.AllCalendars},
				style: {backgroundColor: '#F2F8D6'},
				text: EC_MESS.AllCalendars,
				events: {
					mouseover: function(){BX.addClass(this, 'bxec-over');},
					mouseout: function(){BX.removeClass(this, 'bxec-over');}
				}
			}));

			pItem.onclick = function()
			{
				_this.RunMobile(this.id.substr('ecpp_'.length));
				_this.ClosePopup();
			}

		}

		for (i = 0; i < l; i++)
		{
			cal = this.oEC.arCalendars[i];
			if(type == 'outlook' && (!cal.OUTLOOK_JS || cal.OUTLOOK_JS.length <= 0))
				continue;

			rowsCount++;
			name = BX.util.htmlspecialcharsback(cal.NAME);
			pItem = pWnd.appendChild(BX.create("DIV", {
				props: {id: 'ecpp_' + i, title: name, className: 'bxec-text-overflow' + (cal.bDark ? ' bxec-dark' : '')},
				style: {backgroundColor: cal.COLOR},
				text: name,
				events: {
					mouseover: function(){BX.addClass(this, 'bxec-over');},
					mouseout: function(){BX.removeClass(this, 'bxec-over');}
				}
			}));

			if (type == 'outlook')
			{
				pItem.onclick = function()
				{
					_this.RunOutlook(this.id.substr('ecpp_'.length));
					_this.ClosePopup();
				}
			}
			else if (type == 'mobile')
			{
				pItem.onclick = function()
				{
					_this.RunMobile(this.id.substr('ecpp_'.length));
					_this.ClosePopup();
				}
			}
		}

		if (rowsCount == 0)
			this.CloseBanner(false);

		// Add events
		if (!this.bCloseEventsAttached)
		{
			BX.bind(document, "keyup", BX.proxy(this.OnKeyUp, this));
			setTimeout(function()
			{
				_this.bPreventClickClosing = false;
				BX.bind(document, "click", BX.proxy(_this.ClosePopup, _this));
			}, 100);
			this.bCloseEventsAttached = true;
		}

		var pos = BX.pos(this.Popup[type].pSel);
		this.Popup[type].pWin.Show(true); // Show window
		pWnd.style.width = '200px';
		pWnd.style.height = (rowsCount * 20 + 2) + 'px';

		// Set start position
		pWnd.style.left = (pos.left + 90) + 'px';
		pWnd.style.top = (pos.top + 40) + 'px';
	},

	OnKeyUp: function(e)
	{
		if(!e) e = window.event;
		if(e.keyCode == 27)
			this.ClosePopup();
	},

	ClosePopup: function()
	{
		// if (this.bPreventClickClosing)
			// return;
		for (var type in this.Popup)
		{
			this.Popup[type].pWin.Get().style.display = "none";
			this.Popup[type].bShowed = false;
			this.Popup[type].pWin.Close();
		}

		if (this.bCloseEventsAttached)
		{
			this.bCloseEventsAttached = false;
			BX.unbind(document, "keyup", BX.proxy(this.OnKeyUp, this));
			BX.unbind(document, "click", BX.proxy(this.ClosePopup, this));
		}
	},

	CreatePopup: function(type)
	{
		var _this = this;
		this.Popup[type] = {
			pWin: new BX.CWindow(false, 'float')
		};

		if (type == 'outlook')
			this.Popup[type].pSel = this.pOutlSel;
		else if (type == 'mobile')
			this.Popup[type].pSel = this.pMobSel;

		BX.addClass(this.Popup[type].pWin.Get(), "bxec-ban-popup");
	},

	CloseBanner: function(bSaveSettings)
	{
		var pCont = BX(this.oEC.id + '_banner').parentNode;
		pCont.parentNode.removeChild(pCont);

		if (bSaveSettings !== false)
		{
			BX.admin.panel.Notify(EC_MESS.CloseBannerNotify);

			this.oEC.arConfig.Settings.ShowBanner = 'N';
			this.oEC.SaveSettings();
		}
	},

	RunOutlook: function(id)
	{
		var oCal = this.oEC.arCalendars[id];
		if(oCal.OUTLOOK_JS && oCal.OUTLOOK_JS.length > 0)
			try{eval(oCal.OUTLOOK_JS);}catch(e){};
	},

	RunMobile: function(id)
	{
		this.oEC.ShowMobileHelpDialog(id);
	}
};

var ECMonthSelector = function(oEC)
{
	this.oEC = oEC;
	this.Build();
}

ECMonthSelector.prototype = {
Build : function()
{
	this.pWnd = BX(this.oEC.id + '_month_selector');
	var
		_this = this,
		r = this.pWnd.appendChild(BX.create('TABLE', {props:{align: 'center'}})).insertRow(-1);

	this.ppm = r.insertCell(-1);
	this.pm = r.insertCell(-1);
	var PrevMonthButtCell = r.insertCell(-1);
	this.cm1 = r.insertCell(-1);
	var NextMonthButtCell = r.insertCell(-1);
	this.nm = r.insertCell(-1);
	this.nnm = r.insertCell(-1);

	this.ppm.className = 'bxec-ppm_nnm';
	this.pm.className = 'bxec-pm_nm';
	this.cm1.className = 'bxec-cm';
	this.nm.className = 'bxec-pm_nm';
	this.nnm.className = 'bxec-ppm_nnm';

	this.ppm.onclick = function(){_this.IncreaseCurMonth(-2);};
	this.pm.onclick = function(){_this.IncreaseCurMonth(-1);};
	this.nm.onclick = function(){_this.IncreaseCurMonth(1);};
	this.nnm.onclick = function(){_this.IncreaseCurMonth(2);};

	this.cm = this.cm1.appendChild(BX.create('SPAN', {props: {title: EC_MESS.SelectMonth}}));

	PrevMonthButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-pr-m-but', title: EC_MESS.ShowPrevMonth}, events: {click: function(){_this.IncreaseCurMonth(-1);}}}));

	NextMonthButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-nx-m-but', title: EC_MESS.ShowNextMonth}, events: {click: function(){_this.IncreaseCurMonth(1);}}}));

	this.cm1.onclick = function(){_this.ShowMonthWin(this);};
},

IncreaseCurMonth : function(delta)
{
	var m =bxInt(this.oEC.activeDate.month) + delta;
	var y = this.oEC.activeDate.year;
	if (m < 0)
	{
		m += 12;
		y--;
	}
	else if (m > 11)
	{
		m -= 12;
		y++;
	}
	this.oEC.SetMonth(m, y);
},

OnChange : function(month, year)
{
	month = parseInt(month, 10);
	year = parseInt(year);
	if (month < 0 || month > 11)
		return alert('Error! Incorrect month');
	var arInd;
	if (month == 0)
		arInd = [{m: 10, y: year - 1},{m: 11, y: year - 1},{m: month, y: year},{m: 1, y: year},{m: 2, y: year}];
	else if (month == 1)
		arInd = [{m: 11, y: year - 1},{m: 0, y: year},{m: month, y: year},{m: 2, y: year},{m: 3, y: year}];
	else if (month == 11)
		arInd = [{m: 9, y: year},{m: 10, y: year},{m: month, y: year},{m: 0, y: year + 1},{m: 1, y: year + 1}];
	else if (month == 10)
		arInd = [{m: 8, y: year},{m: 9, y: year},{m: month, y: year},{m: 11, y: year},{m: 0, y: year + 1}];
	else
		arInd = [{m: month - 2, y: year},{m: month - 1, y: year},{m: month, y: year},{m: month + 1, y: year},{m: month + 2, y: year}];

	// Fill month selector cells
	this.cm.innerHTML = this.oEC.arConfig.month[arInd[2].m] + ', ' + arInd[2].y;
	this.ppm.innerHTML = this.oEC.arConfig.month[arInd[0].m];
	this.pm.innerHTML = this.oEC.arConfig.month[arInd[1].m];
	this.nm.innerHTML = this.oEC.arConfig.month[arInd[3].m];
	this.nnm.innerHTML = this.oEC.arConfig.month[arInd[4].m];
},

CreateMonthWin : function()
{
	var
		_this = this,
		pWin = BX.create('DIV', {
			props: {id: 'bxec_month_win_' + this.oEC.id, className : 'bxec-month-dialog'},
			events: {mouseover: function(){_this.MonthWin.bOver = true;}, mouseout: function(){_this.MonthWin.bOver = false;}}
		}),
		c, div, cn, m, i,
		selTable = pWin.appendChild(BX.create('TABLE', {props: {className: 'bxec-month-tbl'}})),
		arM = [0, 4, 8, 1, 5, 9, 2, 6, 10, 3, 7, 11],
		r = selTable.insertRow(-1),
		PY_Cell = r.insertCell(-1),
		PrevYearButtCell = r.insertCell(-1),
		CY_Cell = r.insertCell(-1),
		NextYearButtCell = r.insertCell(-1),
		NY_Cell = r.insertCell(-1);

	this.MonthWin = {bShow: false, bOver: false, curYear: this.oEC.activeDate.year};

	r.className = 'bxec-year-sel';
	PY_Cell.className = 'bxec-py-ny';
	CY_Cell.className = 'bxec-cy';
	NY_Cell.className = 'bxec-py-ny';

	r = selTable.insertRow(-1);
	c = r.insertCell(-1);
	c.colSpan = 5;
	c.className = 'bxec-months';

	for (i = 0; i < 12; i++)
	{
		m = arM[i];
		div = c.appendChild(BX.create("DIV", {
			props: {id: 'bxec_ms_m_' + m, className: 'bxec-month-div' + (m == this.oEC.activeDate.month ? ' bxec-month-act' : '') + ' bxec-' + this.GetSeason(m)},
			html: this.oEC.arConfig.month[m],
			events: {click: function(){_this.MonthWinSetMonth(this);}}
		}));
		if (m == this.oEC.activeDate.month)
			this.MonthWin.curPMonth = div;
	}

	PrevYearButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-prev-year', title: EC_MESS.ShowPrevYear}}));
	PrevYearButtCell.onclick = function(){_this.MonthWinIncreaseYear(-1);};

	NextYearButtCell.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-next-year', title: EC_MESS.ShowNextYear}}));
	NextYearButtCell.onclick = function(){_this.MonthWinIncreaseYear(1);};

	this.oMonthWinYears = {py: PY_Cell, cy: CY_Cell, ny: NY_Cell};

	window['BXEC_MonthWin_OnKeypress_' + this.oEC.id] = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.CloseMonthWin();
	};
	window['BXEC_MonthWin_OnClick_' + this.oEC.id] = function(e)
	{
		if(!e) e = window.event;
		if(!e) return;
		if (!_this.MonthWin.bShow || _this.MonthWin.bOver)
			return;
		_this.CloseMonthWin();
	};

	this.MonthWin.pWin = document.body.appendChild(pWin);
},

ShowMonthWin : function(pCell)
{
	//try{ // For anonymus  users to catch phpVars init errors
	if (!this.MonthWin)
		this.CreateMonthWin();
	if(this.MonthWin.bShow)
		return this.CloseMonthWin();

	BX.bind(document, "keypress", window['BXEC_MonthWin_OnKeypress_' + this.oEC.id]);
	var _this = this;
	setTimeout(function()
	{
		BX.unbind(document, "click", window['BXEC_MonthWin_OnClick_' + _this.oEC.id]);
		BX.bind(document, "click", window['BXEC_MonthWin_OnClick_' + _this.oEC.id]);
	}, 1);

	this.oMonthWinYears.cy.innerHTML = this.MonthWin.curYear;
	this.MonthWin.pWin.style.display = 'block';
	this.MonthWin.bShow = true;

	var pos = BX.pos(pCell);

	jsFloatDiv.Show(this.MonthWin.pWin, pos.left, pos.bottom, 5, false, false);
	//}catch(e){}
},

CloseMonthWin : function()
{
	this.MonthWin.bShow = false;
	this.MonthWin.pWin.style.display = 'none';
	jsFloatDiv.Close(this.MonthWin.pWin);
	BX.unbind(document, "keypress", window['BXEC_MonthWin_OnKeypress_' + this.oEC.id]);
	BX.unbind(document, "click", window['BXEC_MonthWin_OnClick_' + this.oEC.id]);
},

GetSeason : function(m)
{
	switch(m)
	{
		case 11: case 0: case 1:
			return 'winter';
		case 2: case 3: case 4:
			return 'spring';
		case 5: case 6: case 7:
			return 'summer';
		case 8: case 9: case 10:
			return 'autumn';
	}
},

MonthWinSetMonth : function(el)
{
	BX.removeClass(this.MonthWin.curPMonth, 'bxec-month-act');
	BX.addClass(el, 'bxec-month-act');
	this.MonthWin.curPMonth = el;
	m = el.id.substr(10);
	this.oEC.SetMonth(m, this.MonthWin.curYear);
	this.CloseMonthWin();
},

MonthWinIncreaseYear : function(inc)
{
	this.MonthWin.curYear = parseInt(this.MonthWin.curYear) + inc;
	this.oMonthWinYears.cy.innerHTML = this.MonthWin.curYear;
}
}
