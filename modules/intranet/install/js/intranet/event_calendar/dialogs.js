/* special child for admin forms loaded into public page */
CECDialog = function(arParams)
{
	CECDialog.superclass.constructor.apply(this, arguments);
	//if (this.PARTS.HEAD.parentNode)
	//	this.PARTS.HEAD.parentNode.removeChild(this.PARTS.HEAD);

	// this.PARTS.CONTENT.insertBefore(this.PARTS.HEAD, this.PARTS.CONTENT.firstChild);

	// this.PARTS.HEAD.className = 'bx-core-admin-dialog-head';
	// this.PARTS.CONTENT.className += ' bx-core-admin-dialog-content';
}
BX.extend(CECDialog, BX.CDialog);


// # # #  #  #  # Add Event Dialog  # # #  #  #  #
JCEC.prototype.CreateAddEventDialog = function()
{
	var _this = this;
	this.oAddEventDialog = new this.BXECDialogCore({
		obj : this,
		name : 'ADD_EVENT',
		id : 'bxec_add_ed_' + this.id,
		close_id: this.id + '_add_ed_close',
		cancel_id: this.id + '_add_ed_cancel',
		save_id: this.id + '_add_ed_save',
		shadow_id: 'bxec_add_ed_' + this.id + '_shadow',
		fClose : 'CloseAddEventDialog',
		bEscClose: true,
		bClickClose: true,
		onEnter: function(){if (_this.SimpleSaveNewEvent()){_this.CloseAddEventDialog();}},
		width: 360
	});
	var O = this.oAddEventDialog;
	this.oAddEventDialog.saveBut.onclick = function()
	{
		if (_this.SimpleSaveNewEvent())
			_this.CloseAddEventDialog()
	};

	O.oName = BX(this.id + '_add_ed_name');
	O.oDesc = BX(this.id + '_add_ed_desc');
	O.oPeriodText = BX(this.id + '_add_ed_per_text');
	O.oCalendSelect = BX(this.id + '_add_ed_calend_sel');
	O.ExtModeLink = BX(this.id + '_ext_dialog_mode');

	if (this.bUser)
		O.oAccessibility = BX(this.id + '_add_ed_acc');

	O.oCalendSelect.onclick = O.oName.onfocus = O.oDesc.onfocus = function(){_this.oAddEventDialog.bHold = true;};
	O.oCalendSelect.onblur = O.oName.onblur = O.oDesc.onblur = function(){_this.oAddEventDialog.bHold = false;};

	O.oCalendSelect.onchange = function()
	{
		if (_this.arCalendars.length < 1)
			return;
		var curVal = this.nextSibling.style.display || 'none';
		var newVal = (_this.oActiveCalendars[this.value]) ? 'none' : 'block';
		if (curVal == newVal)
			return;
		this.nextSibling.style.display = newVal;
		_this.oAddEventDialog.Resize(false);
	};
	O.ExtModeLink.onclick = function(){_this.OpenExFromSimple();};
}

JCEC.prototype.ShowAddEventDialog = function(bShowCalendars)
{
	if (this.bReadOnly)
		return;
	if (!this.oAddEventDialog)
		this.CreateAddEventDialog();
	if (!this.oAddEventDialog.PreShow()) // Dialog opened
		return;

	var O = this.oAddEventDialog,
		f, t, cts, a, cdts, perHTML,
		time_f = '', time_t = '';
	O.oName.value = O.oDesc.value = '';
	if (!O.bCalendarsActual)
	{
		this.UpdateCalendarSelector(O.oCalendSelect);
		O.bCalendarsActual = true;
	}
	calId = O.oCalendSelect.value;
	if (!this.oActiveCalendars[calId])
	{
		for (var i in this.oActiveCalendars)
		{
			if (bxInt(i) > 0 && this.oActiveCalendars[i])
			{
				O.oCalendSelect.value = i;
				break;
			}
		}
	}
	O.oCalendSelect.onchange();

	if (this.selectDaysMode) // Month view
	{
		var
			start_ind = parseInt(this.selectDaysStartObj.id.substr(9)),
			end_ind = parseInt(this.selectDaysEndObj.id.substr(9));
		if (start_ind > end_ind) // swap start_ind and end_ind
		{
			a = end_ind;
			end_ind = start_ind;
			start_ind = a;
		}

		f = this.activeDateDays[start_ind];
		t = this.activeDateDays[end_ind];
	}
	else if (this.selectTimeMode) // Week view - time select
	{
		cts = this.curTimeSelection;
		f = new Date(cts.sDay.year, cts.sDay.month, cts.sDay.date, cts.sHour, cts.sMin);
		t = new Date(cts.eDay.year, cts.eDay.month, cts.eDay.date, cts.eHour, cts.eMin);

		if (f.getTime() > t.getTime())
		{
			a = f;
			f = t;
			t = a; // swap "f" and "t"
		}
	}
	else if (this.selectDayTMode) // Week view - days select
	{
		cdts = this.curDayTSelection;
		f = new Date(cdts.sDay.year, cdts.sDay.month, cdts.sDay.date);
		t = new Date(cdts.eDay.year, cdts.eDay.month, cdts.eDay.date);
	}
	else
		return;

	var
		f_day = this.convertDayIndex(f.getDay()),
		t_day = this.convertDayIndex(t.getDay());

	if (f.getTime() == t.getTime()) // one day
	{
		perHTML = this.arConfig.days[f_day][0] + ' ' + bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear());
	}
	else
	{
		var
			d_f = f.getDate(), m_f = f.getMonth() + 1, y_f = f.getFullYear(), h_f = f.getHours(), mi_f = f.getMinutes(),
			d_t = t.getDate(), m_t = t.getMonth() + 1, y_t = t.getFullYear(), h_t = t.getHours(), mi_t = t.getMinutes(),
			bTime = !(h_f == h_t && h_f == 0 && mi_f == mi_t && mi_f == 0);

		if (bTime)
		{
			time_f = zeroInt(h_f) + ':' + zeroInt(mi_f);
			time_t = zeroInt(h_t) + ':' + zeroInt(mi_t);
		}

		if (m_f == m_t && y_f == y_t && d_f == d_t && bTime) // Same day, different time
			perHTML = this.arConfig.days[f_day][0] + ' ' + bxFormatDate(d_f, m_f, y_f) + ', ' + time_f + ' - ' + time_t;
		else
			perHTML = this.arConfig.days[f_day][0] + ' ' + bxFormatDate(d_f, m_f, y_f) + ' ' +  time_f + ' - ' +
				this.arConfig.days[t_day][0] + ' ' + bxFormatDate(d_t, m_t, y_t) + ' ' + time_t;
	}

	O.oPeriodText.style.display = 'block';
	O.oPeriodText.innerHTML = perHTML;
	O.curDialogParams  = {from: f, to: t, time_f: time_f || '', time_t: time_t || ''};

	setTimeout(function(){BX.focus(O.oName);}, 500);

	if (this.bUser)
		O.oAccessibility.value = 'busy';

	pos = this.GetAddDialogPosition();
	if (pos === false)
		pos = O.Resize(true);
	else
		O.Resize(false);
	this.oAddEventDialog.Show(pos);
}

JCEC.prototype.OpenExFromSimple = function(bCallback)
{
	this.CloseAddEventDialog();
	if (!bCallback)
		return this.ShowEditEventDialog({bExFromSimple: true});

	var
		ED = this.oEditEventDialog,
		AD = this.oAddEventDialog,
		f = AD.curDialogParams.from,
		t = AD.curDialogParams.to;

	ED.oFrom.value = bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear());
	ED.oTo.value = bxFormatDate(t.getDate(), t.getMonth() + 1, t.getFullYear());
	ED.oFromTime.value = AD.curDialogParams.time_f || '';
	ED.oToTime.value = AD.curDialogParams.time_t || '';
	ED.oName.value = AD.oName.value;

	if (ED.oAccessibility && AD.oAccessibility)
		ED.oAccessibility.value = AD.oAccessibility.value;

	//Set WUSIWUG Editor Content
	setTimeout(function(){window.pLHEEvDesc.SetEditorContent(AD.oDesc.value);}, 100);

	if (ED.oCalendSelect.value != AD.oCalendSelect.value)
	{
		ED.oCalendSelect.value = AD.oCalendSelect.value;
		ED.oCalendSelect.onchange();
	}
}

JCEC.prototype.CloseAddEventDialog = function()
{
	if (!this.oAddEventDialog)
		return;
	switch (this.activeTabId)
	{
		case 'month':
			this.DeSelectDays();
			break;
		case 'week':
			this.DeSelectTime(this.activeTabId);
			this.DeSelectDaysT();
			break;
		case 'day':
			break;
	}
	this.oAddEventDialog.Close();
}

JCEC.prototype.GetAddDialogPosition = function()
{
	if (this.activeTabId == 'month')
	{
		var last_selected = this.arSelectedDays[this.bInvertedDaysSelection ? 0 : this.arSelectedDays.length - 1];
		if (!last_selected)
			return false;

		var pos = BX.pos(last_selected);
		pos.top += parseInt(this.dayCellHeight / 2) + 20;
		pos.left += parseInt(this.dayCellWidth / 2) + 20;
	}
	else //if (this.activeTabId == 'week')
	{
		return false;
	}
	pos.right = pos.left;
	pos.bottom = pos.top;
	pos = BX.align(pos, 360, 180);
	this.oAddEventDialog.pos = pos;
	return pos;
}

// # # #  #  #  # Edit Event Dialog  # # #  #  #  #
JCEC.prototype.CreateEditEventDialog = function(bCheck)
{
	this.bEditEventDialogShow = false;
	this.EditEventDialog = BX('bxec_edit_ed_' + this.id);

	var _this = this;
	BX(this.id + '_edit_ed_close').onclick = BX(this.id + '_edit_ed_cancel').onclick = function() {_this.CloseEditEventDialog();};

	var saveBut = BX(this.id + '_edit_ed_save');
	saveBut.onclick = function()
	{
		if (window.pLHEEvDesc)
			window.pLHEEvDesc.SaveContent();
		_this.ExtendedSaveEvent({callback: BX.proxy(_this.CloseEditEventDialog, _this), bLocationChecked: false});
	};

	var delBut = BX(this.id + '_edit_ed_delete');
	delBut.onclick = function(){if (_this.DeleteEvent(_this.oEditEventDialog.currentEvent)){_this.CloseEditEventDialog();}};
	window['BXEC_EditED_OnKeypress_' + this.id] = function(e)
	{
		if (_this.oEditEventDialog.bDenyClose)
			return;

		if(!e) e = window.event;
		if(!e) return;
		if(e.keyCode == 27)
			_this.CloseEditEventDialog();
		else if(EnterAndNotTextArea(e, 'guest_search'))
			saveBut.onclick();
	};
	window['BXEC_EditED_OnClick_' + this.id] = function(e)
	{
		if (_this.oEditEventDialog.bDenyClose)
			return;

		var bKeepShow = (jsCalendar && jsCalendar.floatDiv) || !_this.bEditEventDialogShow;
		setTimeout(function(){
			if(!e) e = window.event;
			if(!e) return;
			if (_this.bEditEventDialogOver || bKeepShow)
				return;
			_this.CloseEditEventDialog();
		}, 100);
	};

	this.EditEventDialog.onmouseover = function(){_this.bEditEventDialogOver = true;};
	this.EditEventDialog.onmouseout = function(){_this.bEditEventDialogOver = false;};
	this.EditEventDialog.style.zIndex = 500;

	var O = {
		oName: BX(this.id + '_edit_ed_name'),
		oDesc: BX(this.id + '_edit_ed_desc'),
		oFrom: document.forms['bxec_edit_ed_form_' + this.id].edit_event_from,
		oTo: document.forms['bxec_edit_ed_form_' + this.id].edit_event_to,
		oFromTime: BX(this.id + '_edev_time_from'),
		oToTime: BX(this.id + '_edev_time_to'),
		oRepeatSelect: BX(this.id + '_edit_ed_rep_sel'),
		oRepeatSect: BX(this.id + '_edit_ed_repeat_sect'),
		oRepeatPhrase1: BX(this.id + '_edit_ed_rep_phrase1'),
		oRepeatPhrase2: BX(this.id + '_edit_ed_rep_phrase2'),
		oRepeatWeekDays: BX(this.id + '_edit_ed_rep_week_days'),
		oRepeatCount: BX(this.id + '_edit_ed_rep_count'),
		oRepeatDiapTo: document.forms['bxec_edit_ed_form_' + this.id].date_calendar,
		delBut: delBut,
		dialogTitle: BX(this.id + '_edit_ed_d_title'),
		oCalendSelect: BX(this.id + '_edit_ed_calend_sel'),
		oImportance: BX(this.id + '_bxec_importance'),
		oLocation: new ECLocation(this, 1, function(P){_this._LocOnChange(P)})
	};

	if (this.arConfig.bSocNet)
	{
		O.oPlannerLink = BX(this.id + '_planner_link');

		O.oAddMeetTextLink = BX(this.id + '_add_meet_text');
		O.oHideMeetTextLink = BX(this.id + '_hide_meet_text');
		O.oMeetTextCont = BX(this.id + '_meet_text_cont');
		O.oMeetText = BX(this.id + '_meeting_text');

		O.oRemCheck = BX(this.id + '_bxec_reminder');
		O.oRemCont = BX(this.id + '_bxec_rem_cont');
		O.oRemCount = BX(this.id + '_bxec_rem_count');
		O.oRemType = BX(this.id + '_bxec_rem_type');
		O.oRemSave = BX(this.id + '_bxec_rem_save');
	}

	if (this.bUser)
	{
		O.oAccessibility = BX(this.id + '_bxec_accessibility');
		O.oPrivate = BX(this.id + '_bxec_private');
	}

	O.oRepeatSelect.onchange = function() {_this.OnChangeRepeatSelect(this.value);};
	O.oRepeatCount.onmousedown = function() {_this.bEditEventDialogOver = true;};
	O.oCalendSelect.onclick = function() {_this.bEditEventDialogOver = true;};
	O.oCalendSelect.onchange = function(){_this.EdEvCalendarSelect(this);};

	O.oRepeatDiapTo.onblur = O.oRepeatDiapTo.onchange = function()
	{
		if (this.value)
		{
			this.style.color = '#000000';
			return;
		}
		this.value = EC_MESS.NoLimits;
		this.style.color = '#c0c0c0';
	}
	O.oRepeatDiapTo.onfocus = function()
	{
		if (!this.value || this.value == EC_MESS.NoLimits)
			this.value = '';
		this.style.color = '#000000';
	}

	if (this.arConfig.bSocNet)
	{
		O.oRemCheck.onclick = function()
		{
			O.oRemCont.style.display = this.checked ? 'inline' : 'none';
			_this._ShowRemSaveDefSet();
		}
		O.oRemCount.onblur =
		O.oRemCount.onchange =
		O.oRemType.onchange = function(){_this._ShowRemSaveDefSet();}


		O.oAddMeetTextLink.onclick = function()
		{
			this.parentNode.style.display = 'none';
			O.oMeetTextCont.style.display = 'block';
			O.oMeetText.focus();
			_this._ResizeEditEventDialog_Ex();
		};

		O.oHideMeetTextLink.onclick = function()
		{
			O.oAddMeetTextLink.parentNode.style.display = 'block';
			O.oMeetTextCont.style.display = 'none';
			_this._ResizeEditEventDialog_Ex();
		};

		O.oPlannerLink.onclick = function(){_this.RunPlanner({bFromDialog: true});};
	}

	O.oName.onkeydown = O.oName.onchange = function()
	{
		setTimeout(
		function(){
			var
				D = _this.oEditEventDialog,
				val = BX.util.htmlspecialchars(D.oName.value),
				t1 = D.bNew ? EC_MESS.NewEvent : EC_MESS.EditEvent;

			D.dialogTitle.title = t1 + (val.length > 0 ? ': ' + D.oName.value : '');
			D.dialogTitle.innerHTML = t1 + (val.length > 0 ? ': ' + val : '');
		}, 20);
	};

	this.oEditEventDialog = O;
	this.InitEditEventTabControl();
}

JCEC.prototype.ShowEditEventDialog = function(Params)
{
	if (this.bReadOnly)
		return;

	if (!Params)
		Params = {};

	var
		oEvent = Params.oEvent,
		tabId = Params.tabId,
		bLoadLHEEditor = Params.bLoadLHEEditor,
		bExFromSimple = Params.bExFromSimple;

	var _this = this;
	var bFirst = !this.EditEventDialog;

	if (bFirst)
	{
		if (!bLoadLHEEditor && !window.pLHEEvDesc)
			LoadLHE_LHEEvDesc(); //

		if (!window.pLHEEvDesc)
			return setTimeout(function()
			{
				Params.bLoadLHEEditor = true;
				_this.ShowEditEventDialog(Params);
			}, 50);

		this.CreateEditEventDialog();
	}
	else if(this.bEditEventDialogShow)
		return this.CloseEditEventDialog();

	var bNew = false;
	if (!oEvent)
	{
		oEvent = {};
		bNew = true;
	}

	if (oEvent.bSuperposed)
		return;

	if (Params.bRunPlanner)
	{
		this.EditEventDialog.style.display = 'none';
		this.EditEventDialog.style.visibility = 'hidden';

	}
	else
	{
		this.EditEventDialog.style.visibility = 'visible';
		this.EditEventDialog.style.display = 'block';
	}

	if(this.MoreEventsWin && this.MoreEventsWin.bShow)
		this.CloseMoreEventsWin();

	var fd = bxGetDate(oEvent.DATE_FROM);
	var O = this.oEditEventDialog;
	if (fd)
	{
		O.oFrom.value = bxFormatDate(fd.date, fd.month, fd.year);
		O.oFromTime.value = fd.bTime ? zeroInt(fd.hour) + ':' + zeroInt(fd.min) : '';
	}
	else
	{
		O.oFrom.value = O.oFromTime.value = '';
	}

	var td = bxGetDate(oEvent.DATE_TO);
	if (td)
	{
		O.oTo.value = bxFormatDate(td.date, td.month, td.year);
		O.oToTime.value = td.bTime ? zeroInt(td.hour) + ':' + zeroInt(td.min) : '';
	}
	else
	{
		O.oTo.value = O.oToTime.value = '';
	}

	O.oName.value = bxSpChBack(oEvent.NAME) || '';
	O.oName.onchange();

	//SetEditorContent
	window.pLHEEvDesc.SetEditorContent(bxSpChBack(oEvent.DETAIL_TEXT) || '');

	var need2Cange = O.bNew !== bNew;
	O.bNew = bNew;
	O.currentEvent = oEvent;
	O.bRepSetDiapFrom = false;
	this.EditEventDialog.style.display = 'block';
	BX.bind(document, "keypress", window['BXEC_EditED_OnKeypress_' + this.id]);
	this.bEditEventDialogOver = false;

	this.EdEvTabOnclick(O.arTabs[tabId || 0].tab); // Activate first tab

	if (this.arConfig.bSocNet)
	{
		O.bAddOwner = bNew || !oEvent.GUESTS || oEvent.GUESTS.length <= 0;

		if (oEvent.MEETING_TEXT && oEvent.MEETING_TEXT.length > 0)
		{
			O.oAddMeetTextLink.parentNode.style.display = 'none'; // Hide add link
			O.oMeetTextCont.style.display = 'block';
			O.oMeetText.value = oEvent.MEETING_TEXT;
			BX.addClass(O.oMeetText, 'bxec-mt-t-dis');

			O.oMeetText.onfocus = function(){alert(EC_MESS.MeetTextChangeAlert); O.oMeetText.onfocus = null; BX.removeClass(this, 'bxec-mt-t-dis');}
		}
		else
		{
			// Normilize meeting text
			BX.removeClass(O.oMeetText, 'bxec-mt-t-dis');
			O.oMeetText.onfocus = null;
			O.oAddMeetTextLink.parentNode.style.display = 'block'; // Show add link
			O.oMeetTextCont.style.display = 'none';
			O.oMeetText.value = '';
		}

		this.EEUC.DelAllUsers(true);
		if (!bNew && oEvent.GUESTS)
		{
			var l = oEvent.GUESTS.length, i;
			for(i = 0; i < l; i++)
			{
				if (oEvent.GUESTS[i].bHost)
				{
					//this.EEUC.AddOwner();
					break;
				}
			}

			this.EEUC.AddUsers(oEvent.GUESTS);
		}
		//setTimeout(function(){_this.EEUC.CheckUsersAccessibility();}, 100);
	}

	if (oEvent.PERIOD)
		O.oRepeatSelect.value = oEvent.PERIOD.TYPE.toLowerCase();
	else
		O.oRepeatSelect.value = 'none';
	O.oRepeatSelect.onchange();

	if (!bNew)
	{
		this.ClearBlink(oEvent);
		if (O.oRepeatDiapFrom == oEvent.DATE_FROM)
			O.bRepSetDiapFrom = true;
	}

	if (need2Cange)
	{
		if (bNew)
		{
			O.delBut.style.display = 'none';
			O.dialogTitle.innerHTML = EC_MESS.NewEvent;
		}
		else
		{
			O.delBut.style.display = 'inline';
			O.dialogTitle.innerHTML = EC_MESS.EditEvent;
		}
	}
	if (!O.bCalendarsActual)
	{
		this.UpdateCalendarSelector(O.oCalendSelect);
		O.bCalendarsActual = true;
	}

	var calId = oEvent.IBLOCK_SECTION_ID || this.defaultCalendarId;
	if (!this.oActiveCalendars[calId])
	{
		for (var i in this.oActiveCalendars)
		{
			if (bxInt(i) > 0 && this.oActiveCalendars[i])
			{
				calId = i;
				break;
			}
		}
	}

	O.oCalendSelect.value = calId || 0;
	if (!O.oCalendSelect.value && O.oCalendSelect.options.length > 0)
		O.oCalendSelect.options[0].selected = true;
	O.oCalendSelect.onchange();

	// Set reminder & accessibility params
	var
		def_reminder = false,
		def_count = 15,
		def_type = 'min',
		def_accessibility = 'busy',
		def_importance = 'normal',
		def_private = false;

	O.loc_old_mrevid = O.loc_old_mrid = false;
	if (O.bNew)
	{
		O.oImportance.value = def_importance;

		if (this.arConfig.bSocNet)
		{
			O.oRemCheck.checked = def_reminder;
			O.oRemCount.value = def_count;
			O.oRemType.value = def_type;
			O.oAddMeetTextLink.parentNode.style.display = 'block';
			O.oMeetTextCont.style.display = 'none';
		}

		O.oLocation.Set(false, '');
		O.loc_new = O.loc_old = '';
		O.loc_change = false;

		if (this.bUser)
		{
			O.oAccessibility.value = def_accessibility;
			O.oPrivate.checked = def_private;
		}
	}
	else
	{
		if (this.arConfig.bSocNet)
		{
			var _rem = O.currentEvent.REMIND.split('_');
			O.oRemCheck.checked = O.currentEvent.REMIND ? true : false;
			O.oRemCount.value = _rem[0] || def_count;
			O.oRemType.value = _rem[1] || def_type;
		}
		O.oImportance.value = O.currentEvent.IMPORTANCE || def_importance;

		var loc = bxSpChBack(O.currentEvent.LOCATION);

		O.loc_old = loc;
		O.loc_new = loc;
		O.loc_change = false;

		var arLoc = this.ParseLocation(loc, true);
		if (arLoc.mrid && arLoc.mrevid)
		{
			O.oLocation.Set(arLoc.mrind, '');
			O.loc_old_mrid = arLoc.mrid;
			O.loc_old_mrevid = arLoc.mrevid;
		}
		else
		{
			O.oLocation.Set(false, loc);
		}

		if (this.bUser)
		{
			O.oAccessibility.value = O.currentEvent.ACCESSIBILITY || def_accessibility;
			O.oPrivate.checked = O.currentEvent.PRIVATE || def_private;
		}
	}

	if (this.arConfig.bSocNet)
		O.oRemCheck.onclick();

	if (oEvent.HOST)
		this.DeactivateEditEventFields(true);
	else// if (O.bDeactivatedFields)
		this.DeactivateEditEventFields(false);

	var pos = this.GetCenterWindowPos(500, 400);
	this.bEditEventDialogShow = true;

	jsFloatDiv.Show(this.EditEventDialog, pos.left, pos.top, 5, false, false);

	if (!O.bDeactivatedFields && !Params.bRunPlanner)
		O.oName.focus();

	if(!this.oEditEventDialog._shad)
	{
		this.oEditEventDialog._shad = BX('bxec_edit_ed_' + _this.id + '_shadow');
		this.EditEventDialog.parentNode.appendChild(this.oEditEventDialog._shad);
	}

	if (this.oEditEventDialog._shad)
		this.oEditEventDialog._shad.style.display = Params.bRunPlanner ? 'none' : 'block';

	setTimeout(function(){_this._ResizeEditEventDialog_Ex();}, 1000);

	if (Params.bRunPlanner)
		this.RunPlanner({bFromDialog: false});

	if (bExFromSimple)
		this.OpenExFromSimple(true);
}


JCEC.prototype.EdEvCalendarSelect = function(pSel)
{
	if (this.bUser && !this.oCalendars[pSel.value])
		return;

	if (this.arCalendars.length > 0)
	{
		if (this.arConfig.bSocNet && this.ownerType == 'USER')
		{
			if (this.IsDavCalendar(pSel.value))
			{
				//Disable guests section
				this.oEditEventDialog.arTabs[2].bDisabled = true;
				BX.addClass(this.oEditEventDialog.arTabs[2].tab, "bxec-d-tab-dis")
			}
			else if(this.oEditEventDialog.arTabs[2] && this.oEditEventDialog.arTabs[2].bDisabled)
			{
				// Enable guests section
				this.oEditEventDialog.arTabs[2].bDisabled = false;
				BX.removeClass(this.oEditEventDialog.arTabs[2].tab, "bxec-d-tab-dis")
			}
		}

		var
			curVal = pSel.nextSibling.style.display || 'none',
			newVal = (this.oActiveCalendars[pSel.value]) ? 'none' : 'block';

		if (curVal != newVal)
		{
			pSel.nextSibling.style.display = newVal;
			this._ResizeEditEventDialog_Ex();
		}
	}
}

JCEC.prototype._ShowRemSaveDefSet = function()
{
	return;
	var
		def_reminder = false,
		def_count = 15,
		def_type = 'min';

	if (def_reminder != this.oEditEventDialog.oRemCheck.checked || def_count != this.oEditEventDialog.oRemCount.value || def_type != this.oEditEventDialog.oRemType.value)
		this.oEditEventDialog.oRemSave.style.visibility = 'visible';
	else
		this.oEditEventDialog.oRemSave.style.visibility = 'hidden';
};

JCEC.prototype._LocOnChange = function(P)
{
	var O = this.oEditEventDialog;
	if (P.ind === false)
	{
		O.loc_new = P.value || '';
	}
	else
	{
		if (P.ind != O.loc_old_mrid) // Same meeting room
			O.loc_change = true;

		O.loc_new = 'ECMR_' + this.meetingRooms[P.ind].ID;
	}
};

JCEC.prototype.InitEditEventTabControl = function()
{
	var arTabs = [
		{
			tab : BX(this.id + '_ed_tab_0'),
			cont : BX(this.id + '_ed_tab_cont_0')
		},
		{
			tab : BX(this.id + '_ed_tab_1'),
			cont : BX(this.id + '_ed_tab_cont_1')
		}
	];

	var _this = this;
	if (this.arConfig.bSocNet)
	{
		arTabs[2] = {
			tab : BX(this.id + '_ed_tab_2'),
			cont : BX(this.id + '_ed_tab_cont_2')
		};
		this.EEUC = new ECUserControll(this);
	}

	arTabs[3] = {
		tab : BX(this.id + '_ed_tab_3'),
		cont : BX(this.id + '_ed_tab_cont_3')
	};

	this.oEditEventDialog.tabSet = BX(this.id + '_edit_ed_d_tabset');
	this.oEditEventDialog.tabSetCont = BX(this.id + '_edit_ed_d_tabcont');

	this.oEditEventDialog.arTabs = arTabs;
	for (var i in arTabs)
	{
		if (arTabs[i] && arTabs[i].tab)
			arTabs[i].tab.onclick = function(){_this.EdEvTabOnclick(this)};
	}
}


JCEC.prototype._ResizeEditEventDialog_Ex = function()
{
	var
		O = this.oEditEventDialog,
		ev = O.currentEvent,
		Tab = O.arTabs[O.activeTab],
		contH = Tab.cont.offsetHeight;

	if (contH < 290)
		contH = 290;

	O.tabSetCont.style.height = contH + 'px';
	this.oEditEventDialog.tabSet.style.height = contH + 30 + 'px';
	var h = parseInt(this.EditEventDialog.firstChild.firstChild.offsetHeight) + 10;
	this.ResizeDialogWin(this.EditEventDialog, 480, h);
}

JCEC.prototype.EdEvTabOnclick = function(pTab)
{
	var
		curInd = parseInt(pTab.id.substr((this.id + '_ed_tab_').length)),
		i, Tab,
		arTabs = this.oEditEventDialog.arTabs,
		_this = this;

	if (this.oEditEventDialog.activeTab == curInd || arTabs[curInd].bDisabled)
		return;

	for (i in arTabs)
	{
		Tab = arTabs[i];
		if (!Tab || !Tab.cont)
			continue;

		if (i == curInd)
		{
			Tab.cont.style.display = 'block';
			BX.addClass(Tab.tab, 'bxec-d-tab-act');
		}
		else
		{
			Tab.cont.style.display = 'none';
			BX.removeClass(Tab.tab, 'bxec-d-tab-act');
		}
	}
	this.oEditEventDialog.activeTab = curInd;
	this._ResizeEditEventDialog_Ex();
}


JCEC.prototype.DeactivateEditEventFields = function(bDeactivate)
{
	var O = this.oEditEventDialog;

	O.arTabs[1].tab.style.display = bDeactivate ? 'none' : 'block'; // Description

	if (this.arConfig.bSocNet)
		O.arTabs[2].tab.style.display = bDeactivate ? 'none' : 'block'; // Guests

	O.oLocation.Deactivate(bDeactivate);
	if (bDeactivate)
		BX.addClass(O.arTabs[0].cont, 'bxec-disable-calendar-clock');
	else
		BX.removeClass(O.arTabs[0].cont, 'bxec-disable-calendar-clock');

	if (!O.oFromTime.value && !O.oToTime.value && bDeactivate)
	{
		O.oFromTime.style.display = 'none';
		O.oToTime.style.display = 'none';
	}
	else
	{
		O.oFromTime.style.display = 'inline';
		O.oToTime.style.display = 'inline';
	}

	O.oName.disabled = bDeactivate;
	O.oFrom.disabled = bDeactivate;
	O.oTo.disabled = bDeactivate;
	O.oFromTime.disabled = bDeactivate;
	O.oToTime.disabled = bDeactivate;
	O.oRepeatSelect.disabled = bDeactivate;

	if (O.oRepeatWeekDaysCh)
		for (i = 0; i < 7; i++)
			O.oRepeatWeekDaysCh[i].disabled = bDeactivate;
	O.oRepeatCount.disabled = bDeactivate;
	O.oRepeatDiapTo.disabled = bDeactivate;

	O.bDeactivatedFields = bDeactivate;
}

JCEC.prototype.CloseEditEventDialog = function()
{
	this.bEditEventDialogShow = false;
	this.EditEventDialog.style.display = 'none';
	jsFloatDiv.Close(this.EditEventDialog);
	BX.unbind(document, "keypress", window['BXEC_EditED_OnKeypress_' + this.id]);
}

JCEC.prototype.OnChangeRepeatSelect = function(val)
{
	var i, l, ardays;
	var Ob = this.oEditEventDialog;
	if (val == 'none')
	{
		Ob.oRepeatSect.style.display =  'none';
	}
	else
	{
		Ob.oRepeatSect.style.display =  'block';
		Ob.oRepeatPhrase2.innerHTML = EC_MESS.DeDot; // Works only for de lang

		if (val == 'weekly')
		{
			Ob.oRepeatPhrase1.innerHTML = EC_MESS.EveryF;
			Ob.oRepeatPhrase2.innerHTML += EC_MESS.WeekP;
			Ob.oRepeatWeekDays.style.display = (val == 'weekly') ? 'block' : 'none';
			if (!Ob.oRepeatWeekDaysCh)
			{
				Ob.oRepeatWeekDaysCh = [];
				for (i = 0; i < 7; i++)
					Ob.oRepeatWeekDaysCh[i] = BX(this.id + 'bxec_week_day_' + i);
			}
			if (!Ob.bNew && Ob.currentEvent && Ob.currentEvent.PERIOD && Ob.currentEvent.PERIOD.DAYS)
			{
				ardays = Ob.currentEvent.PERIOD.DAYS;
			}
			else
			{
				ardays = {};
				if (Ob.currentEvent.DATE_FROM)
					ardays[this.convertDayIndex(bxGetDate(Ob.currentEvent.DATE_FROM, true).getDay())] = true;
				else if(Ob.oFrom.value)
					ardays[this.convertDayIndex(bxGetDate(Ob.oFrom.value, true).getDay())] = true;
			}
			for (i = 0; i < 7; i++)
				Ob.oRepeatWeekDaysCh[i].checked = ardays[i] || false;
		}
		else
		{
			if (val == 'yearly')
				Ob.oRepeatPhrase1.innerHTML = EC_MESS.EveryN;
			else
				Ob.oRepeatPhrase1.innerHTML = EC_MESS.EveryM;

			if (val == 'daily')
				Ob.oRepeatPhrase2.innerHTML += EC_MESS.DayP;
			else if (val == 'monthly')
				Ob.oRepeatPhrase2.innerHTML += EC_MESS.MonthP;
			else if (val == 'yearly')
				Ob.oRepeatPhrase2.innerHTML += EC_MESS.YearP;

			Ob.oRepeatWeekDays.style.display = 'none';
		}
		var bPer = Ob.currentEvent && Ob.currentEvent.PERIOD;
		Ob.oRepeatCount.value = (Ob.bNew || !bPer) ? 1 : Ob.currentEvent.PERIOD.COUNT;
		Ob.oRepeatDiapFrom = (Ob.bNew || !bPer) ? Ob.oFrom.value : Ob.currentEvent.PERIOD.FROM;
		if (Ob.bNew || !bPer)
		{
			Ob.oRepeatDiapTo.value = '';
		}
		else
		{
			var pd = bxGetDate(Ob.currentEvent.PERIOD.TO);
			if (pd.date == 1 && pd.month == 1 && pd.year == 2038)
				Ob.oRepeatDiapTo.value = '';
			else
				Ob.oRepeatDiapTo.value = Ob.currentEvent.PERIOD.TO;
		}
		Ob.oRepeatDiapTo.onchange();
	}

	this._ResizeEditEventDialog_Ex();
}

// # # #  #  #  # View Event Dialog  # # #  #  #  #
JCEC.prototype.CreateViewEventDialog = function()
{
	var VD = new this.BXECDialogCore({
		obj : this,
		name : 'VIEW_EVENT',
		id : 'bxec_view_ed_' + this.id,
		close_id: this.id + '_view_ed_close',
		cancel_id: this.id + '_view_ed_cancel',
		shadow_id: 'bxec_view_ed_' + this.id + '_shadow',
		bEscClose: true,
		bClickClose: true,
		width: 475
	});

	var _this = this;
	this.oViewEventDialog = VD;
	this.InitViewEventTabControl();

	// Tab 0: Basic
	var T0 = this.oViewEventDialog.arTabs[0].cont.firstChild;
	VD.oName = T0.rows[0].cells[1].firstChild;
	VD.oCreatedByName = T0.rows[1].cells[1].firstChild;
	VD.oPeriod = T0.rows[2].cells[0];
	VD.repRow = T0.rows[3];

	VD.locationRow = T0.rows[4];
	VD.oLocation = VD.locationRow.cells[1];

	VD.meetingTextRow = T0.rows[5];
	VD.guestsRow = T0.rows[6];
	VD.guestsCont = BX(this.id + '_view_ed_guest_div');
	VD.guestsCount = VD.guestsRow.cells[0].getElementsByTagName('SPAN')[0];
	VD.confRow = T0.rows[7];
	VD.oMeetingText = BX(this.id + '_view_ed_meet_text');

	// Tab 1: Description
	VD.oDesc = BX(this.id + '_view_ed_desc');

	// Tab 2: Additional
	var T2 = this.oViewEventDialog.arTabs[2].cont.firstChild;
	VD.calRow = T2.rows[0];
	VD.accessRow = T2.rows[2];
	VD.oImpSpan = BX(this.id + '_view_ed_imp');
	VD.oAccessSpan = BX(this.id + '_view_ed_accessibility');
	VD.privateRow = T2.rows[4];

	// Buttons
	VD.editBut = BX(this.id + '_view_ed_edit');
	VD.delBut = BX(this.id + '_view_ed_delete');
	VD.editBut.onclick = function() {_this.ShowEditEventDialog({oEvent: VD.currentEvent}); VD._Close();};
	VD.delBut.onclick = function() {window._BXEC_EvDynCloseInt_onclick = true;if(_this.DeleteEvent(VD.currentEvent)){VD._Close();window._BXEC_EvDynCloseInt_onclick = false;}};
}

JCEC.prototype.ShowViewEventDialog = function(oEvent)
{
	if (!this.oViewEventDialog)
		this.CreateViewEventDialog();
	if (!this.oViewEventDialog.PreShow()) // Dialog opened
		return;

	var
		VD = this.oViewEventDialog,
		perHTML,
		_this = this,
		d_from = bxGetDate(oEvent.DATE_FROM, false, true),
		d_to = bxGetDate(oEvent.DATE_TO, false, true),
		s_day_from = this.arConfig.days[this.convertDayIndex(d_from.oDate.getDay())][0],
		s_day_to = this.arConfig.days[this.convertDayIndex(d_to.oDate.getDay())][0],
		priv = oEvent.PRIVATE ? ' [' + EC_MESS.PrivateEvent + ']' : '',
		//rowDisp = BX.browser.IsIE() ? 'inline' : 'table-row',
		rowDisp = "",
		title = EC_MESS.ViewingEvent + ': ' + oEvent.NAME;

	if (title.length > 42)
		title = title.substr(0, 40) + '...';

	VD.SetTitle(title);
	VD.oName.innerHTML = '<span' + this.GetEventLabelStyle(oEvent) + '>' + oEvent.NAME + '</span>';
	VD.oName.title = bxSpChBack(oEvent.NAME);
	VD.oName.style.width = "100px";
	setTimeout(function()
	{
		var nameW= VD.oName.parentNode.offsetWidth;
		if (nameW)
			VD.oName.style.width = (nameW - 5) + "px";
	}, 100);

	VD.oCreatedByName.innerHTML = oEvent.CREATED_BY_NAME_LINK;

	perHTML = s_day_from + ' ' + oEvent.DATE_FROM;
	if (d_from.oDate.getTime() != d_to.oDate.getTime())
		perHTML += ' - ' + s_day_to + ' ' + oEvent.DATE_TO;

	VD.oPeriod.innerHTML = perHTML;
	VD.oImpSpan.innerHTML = EC_MESS['Importance_' + oEvent.IMPORTANCE];

	// Calendar
	if (this.oCalendars[oEvent.IBLOCK_SECTION_ID])
	{
		VD.calRow.style.display = rowDisp;
		VD.calRow.cells[1].innerHTML = this.oCalendars[oEvent.IBLOCK_SECTION_ID].NAME;
	}
	else
	{
		VD.calRow.style.display = 'none';
	}

	// Description
	if (oEvent.DETAIL_TEXT.toString().length > 0)
	{
		VD.oDesc.innerHTML = bxSpChBack(oEvent.DETAIL_TEXT);
		VD.arTabs[1].tab.style.display = 'block'; // Show tab
	}
	else
	{
		VD.arTabs[1].tab.style.display = 'none'; // Hide tab
	}

	// Location
	var lochtml = '', loc = this.ParseLocation(oEvent.LOCATION, true);

	if (loc.mrid == false && loc.str.length > 0)
		lochtml = loc.str;
	else if (loc.mrid && loc.MR)
		lochtml = loc.MR.URL ? '<a href="' + loc.MR.URL+ '" target="_blank">' + loc.MR.NAME + '</a>' : loc.MR.NAME;

	if (lochtml.length > 0)
	{
		VD.locationRow.style.display = rowDisp;
		VD.oLocation.innerHTML = lochtml;
	}
	else
	{
		VD.locationRow.style.display = 'none';
	}

	// repeating
	if (oEvent.PERIOD)
	{
		VD.repRow.style.display = rowDisp;
		var oPer = oEvent.PERIOD;
		repeatHTML = '';
		switch (oEvent.PERIOD.TYPE)
		{
			case 'DAILY':
				repeatHTML += '<b>' + EC_MESS.EveryM_ + ' ' + oEvent.PERIOD.COUNT + EC_MESS.DeDot + EC_MESS._J + ' ' + EC_MESS.DayP + '</b>';
				break;
			case 'WEEKLY':
				repeatHTML += '<b>' + EC_MESS.EveryF_ + ' ';
				if (oEvent.PERIOD.COUNT > 1)
					repeatHTML += oEvent.PERIOD.COUNT + EC_MESS.DeDot + EC_MESS._U + ' ';
				repeatHTML += EC_MESS.WeekP + ': ';
				var n = 0;
				for (var i in oEvent.PERIOD.DAYS)
				{
					if(oEvent.PERIOD.DAYS[i] === true)
						repeatHTML += (n++ > 0 ? ', ' : '') + this.arConfig.days[i][0];
				}
				repeatHTML += '</b>';
				break;
			case 'MONTHLY':
				repeatHTML += '<b>' + EC_MESS.EveryM_ + ' ';
				if (oEvent.PERIOD.COUNT > 1)
					repeatHTML += oEvent.PERIOD.COUNT + EC_MESS.DeDot + EC_MESS._J + ' ';
				repeatHTML +=  EC_MESS.MonthP + ', ' + EC_MESS.DeAm + bxInt(d_from.date) + EC_MESS.DeDot + EC_MESS.DateP_ + '</b>';
				break;
			case 'YEARLY':
				repeatHTML += '<b>' + EC_MESS.EveryN_ + ' ';
				if (oEvent.PERIOD.COUNT > 1)
					repeatHTML += oEvent.PERIOD.COUNT + EC_MESS.DeDot + EC_MESS._J + ' ';
				repeatHTML +=  EC_MESS.YearP + ', ' + EC_MESS.DeAm + bxInt(d_from.date) + EC_MESS.DeDot + EC_MESS.DateP_ + ' ' + EC_MESS.DeDes + bxInt(d_from.month) + EC_MESS.DeDot + EC_MESS.MonthP_ + '</b>';
				break;
		}

		repeatHTML += '<br> ' + EC_MESS.From_ + ' ' + oEvent.PERIOD.FROM;
		var pd = bxGetDate(oEvent.PERIOD.TO);
		if (pd.date != 1 || pd.month != 1 || pd.year != 2038)
			repeatHTML += ' ' + EC_MESS.To_ + ' ' + oEvent.PERIOD.TO;
		VD.repRow.cells[1].innerHTML = repeatHTML;
	}
	else
	{
		VD.repRow.style.display = 'none';
	}
	VD.currentEvent = oEvent;

	if (this.arConfig.bSocNet && oEvent.IS_MEETING)
	{
		if (oEvent.HOST && !oEvent.bSuperposed && !this.bReadOnly)
		{
			VD.confRow.style.display = rowDisp;
			var cConf = VD.confRow.cells[1];
			BX.cleanNode(cConf);

			if (oEvent.STATUS == 'Q')
			{
				cConf.appendChild(BX.create('A', {
					props: {href: 'javascript:void(0);', title: EC_MESS.ConfirmEncYTitle, className: 'bxec-conf-link'},
					events: {click: function(){_this.ConfirmEvent(VD.currentEvent, true); VD._Close();}},
					html: '<img src="/bitrix/images/1.gif" class="bxec-vd-g-status-y"/>' + EC_MESS.ConfirmEncY
				}));
				cConf.appendChild(document.createTextNode(' | '));
			}
			else if(oEvent.STATUS == 'Y')
			{
				cConf.appendChild(BX.create('IMG', {props: {src: '/bitrix/images/1.gif', className: 'bxec-iconkit bxec-g-status-y1', title: EC_MESS['GuestStatus_Y']}}));
				cConf.innerHTML += '<b>' + EC_MESS.Confirmed + '</b>&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			cConf.appendChild(BX.create('A', {
				props:{href: 'javascript:void(0);', title: EC_MESS.ConfirmEncNTitle, className: 'bxec-conf-link' + (oEvent.STATUS == 'Y' ? '-h' : '')},
				events: {click: function(){if(_this.DeleteEvent(VD.currentEvent)){VD._Close();}}},
				html: '<img src="/bitrix/images/1.gif" class="bxec-vd-g-status-n"/>' + EC_MESS.ConfirmEncN
			}));
		}
		else
		{
			VD.confRow.style.display = 'none';
		}

		if (oEvent.GUESTS && oEvent.GUESTS.length > 0)
		{
			VD.guestsRow.style.display = rowDisp;

			var
				user, guestsHTML = '', maxCount = 7,
				div = VD.guestsCont,
				i, l = oEvent.GUESTS.length,
				h, status, cn, statImg, lcn;

			VD.guestsCount.innerHTML = ' (' + l + ')';
			for (i = 0; i < l; i++)
			{
				user = oEvent.GUESTS[i];
				status = (user.status || 'Q').toLowerCase();
				cn = 'bxec-guest-stat-' + status;
				statImg = '<img src="/bitrix/images/1.gif" title="' + EC_MESS['GuestStatus_' + status] + '" class="bxec-iconkit bxec-g-status ' + cn + '" align="top">';
				lcn = (status == 'y' || status == 'n') ? 'bxec-guest-link-' + status : false;
				h = this.GetUserProfileLink(user.id, true, user, lcn, oEvent.GUESTS[i].bHost);
				guestsHTML += (i > 0 ? ', ' : '') + statImg + h;
			}
			div.innerHTML = guestsHTML;
			div.className = l > maxCount ? 'bxec-guests-div bxec-many-guests' : 'bxec-guests-div';
		}
		else
		{
			VD.guestsRow.style.display = 'none';
		}

		// Show invitation text
		if (oEvent.MEETING_TEXT && oEvent.MEETING_TEXT.length > 0)
		{
			var text = oEvent.MEETING_TEXT.replace(/\n/g, "<br>");
			VD.meetingTextRow.style.display = rowDisp;
			VD.oMeetingText.innerHTML = text;
		}
		else
		{
			VD.meetingTextRow.style.display = 'none';
		}
	}
	else
	{
		// It's not a meeting - hide all unnecessary fields
		VD.confRow.style.display = 'none';
		VD.meetingTextRow.style.display = 'none';
		VD.guestsRow.style.display = 'none';
	}

	if (oEvent.ACCESSIBILITY)
	{
		VD.accessRow.style.display = rowDisp;
		VD.oAccessSpan.innerHTML = EC_MESS['Acc_' + oEvent.ACCESSIBILITY];
	}
	else
	{
		VD.accessRow.style.display = 'none';
	}

	if (oEvent.PRIVATE)
	{
		VD.privateRow.style.display = rowDisp;
	}
	else
	{
		VD.privateRow.style.display = 'none';
	}

	this.ViewEvTabOnclick(VD.arTabs[0].tab); // Activate first tab

	// Hide edit & delete links for read only events
	var disp = (!this.bReadOnly && !oEvent.bSuperposed) ? 'block' : 'none';
	VD.editBut.style.display = disp;
	VD.delBut.style.display = disp;

	this.oViewEventDialog.Show(this.oViewEventDialog.Resize(true));

	this.ClearBlink(oEvent);

	setTimeout(function(){_this._ResizeViewEventDialog_Ex();}, 50);
}

JCEC.prototype.InitViewEventTabControl = function()
{
	var arTabs = [
		{
			tab : BX(this.id + '_view_tab_0'),
			cont : BX(this.id + '_view_tab_cont_0')
		},
		{
			tab : BX(this.id + '_view_tab_1'),
			cont : BX(this.id + '_view_tab_cont_1')
		},
		{
			tab : BX(this.id + '_view_tab_2'),
			cont : BX(this.id + '_view_tab_cont_2')
		}
	];

	this.oViewEventDialog.tabSet = BX(this.id + '_view_d_tabset');
	this.oViewEventDialog.tabSetCont = BX(this.id + '_view_d_tabcont');
	this.oViewEventDialog.arTabs = arTabs;

	var
		_this = this,
		i, l = arTabs.length;

	for (i = 0; i < l; i++)
		arTabs[i].tab.onclick = function(){_this.ViewEvTabOnclick(this)};
}

JCEC.prototype._ResizeViewEventDialog_Ex = function()
{
	var
		contH = bxInt(this.oViewEventDialog.arTabs[0].cont.offsetHeight) + 5,
		minH = 180;

	if (contH < minH)
		contH = minH;

	this.oViewEventDialog.tabSetCont.style.height = contH + 'px';
	this.oViewEventDialog.tabSet.style.height = contH + 30 + 'px';
	this.oViewEventDialog.oDesc.style.height = (contH - 45) + 'px'; // Description div

	var h = parseInt(this.oViewEventDialog.pWnd.firstChild.offsetHeight) + 10;
	this.ResizeDialogWin(this.oViewEventDialog.pWnd, 475, h);
}

JCEC.prototype.ViewEvTabOnclick = function(pTab)
{
	var
		curInd = parseInt(pTab.id.substr((this.id + '_view_tab_').length)),
		i, Tab,
		arTabs = this.oViewEventDialog.arTabs,
		l = arTabs.length,
		_this = this;

	if (this.oViewEventDialog.activeTab == curInd)
		return;

	for (i = 0; i < l; i++)
	{
		Tab = arTabs[i];
		if (i == curInd)
		{
			Tab.cont.style.display = 'block';
			BX.addClass(Tab.tab, 'bxec-d-tab-act');
		}
		else
		{
			Tab.cont.style.display = 'none';
			BX.removeClass(Tab.tab, 'bxec-d-tab-act');
		}
	}
	this.oViewEventDialog.activeTab = curInd;
}

// # # #  #  #  # Edit Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateEditCalDialog = function()
{
	this.bEditCalDialogShow = false;
	this.EditCalDialog = BX('bxec_edcal_' + this.id);

	var _this = this;
	var closeBut = BX(this.id + '_edcal_close');
	var cancelBut = BX(this.id + '_edcal_cancel');
	closeBut.onclick = cancelBut.onclick = function() {_this.CloseEditCalDialog();};

	var saveBut = BX(this.id + '_edcal_save');
	saveBut.onclick = function(){if (_this.SaveCalendar()){_this.CloseEditCalDialog();}};

	var delBut = BX(this.id + '_edcal_delete');
	delBut.onclick = function() {_this.DeleteCalendar(_this.oEdCalDialog.currentCalendar); _this.CloseEditCalDialog();};
	window['BXEC_EdCal_OnKeypress_' + this.id] = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.CloseEditCalDialog();
		else if(EnterAndNotTextArea(e))
			saveBut.onclick();
	};
	window['BXEC_EdCal_OnClick_' + this.id] = function(e)
	{
		setTimeout(function(){
			if(!e) e = window.event;
			if(!e) return;
			if (!_this.bEditCalDialogShow || _this.bEditCalDialogOver || _this.oEdCalDialog.bHold)
				return;
			_this.CloseEditCalDialog();
		}, 10);
	};
	this.EditCalDialog.onmouseover = function(){_this.bEditCalDialogOver = true;};
	this.EditCalDialog.onmouseout = function(){_this.bEditCalDialogOver = false;};
	this.EditCalDialog.style.zIndex = 500;

	var colorTable = BX(this.id + '_edcal_color_table');
	var setcol = function(r, c, col_ind)
	{
		var ar  = _this.arConfig.arCalColors;
		var cell = colorTable.rows[r].cells[c];
		var color = ar[col_ind] || ar[0];
		cell.style.backgroundColor = color;
		cell.onclick = function(){_this._CalDialogSetColor(color);};
	};
	setcol(0, 1, 0); setcol(0, 2, 1); setcol(0, 3, 2); setcol(0, 4, 3);
	setcol(1, 0, 4); setcol(1, 1, 5); setcol(1, 2, 6); setcol(1, 3, 7);

	var colorInput = BX(this.id + '_edcal_color');
	colorInput.onblur = function()
	{
		_this.oEdCalDialog.bHold = false;
		_this._CalDialogSetColor(this.value);
	};

	this.oEdCalDialog = {
		oName: BX(this.id + '_edcal_name'),
		oDesc: BX(this.id + '_edcal_desc'),
		oColor: BX(this.id + '_edcal_color'),
		delBut: delBut,
		dialogTitle: BX(this.id + '_edcal_d_title'),
		colorInput: colorInput,
		colorCell: colorTable.rows[0].cells[0],
		oExpAllow: BX(this.id + '_bxec_cal_exp_allow')
	};

	if (this.arConfig.bExchange)
		this.oEdCalDialog.pExch = BX(this.id + '_bxec_cal_exch');

	if (this.bUser)
	{
		this.oEdCalDialog.oStatus = BX(this.id + '_cal_priv_status');
		this.oEdCalDialog.oMeetingCalendarCh = BX(this.id + '_bxec_meeting_calendar');
	}

	this.oEdCalDialog.oName.onfocus = this.oEdCalDialog.oDesc.onfocus = this.oEdCalDialog.oColor.onfocus = function(){_this.oEdCalDialog.bHold = true;};
	this.oEdCalDialog.oName.onblur = this.oEdCalDialog.oDesc.onblur = function(){_this.oEdCalDialog.bHold = false;};

	if (this.bSuperpose)
	{
		this.oEdCalDialog.add2SPCont = BX(this.id + '_bxec_cal_add2sp_cont');
		this.oEdCalDialog.add2SP = BX(this.id + '_bxec_cal_add2sp');
	}
	this.oEdCalDialog.oExpAllow.onclick = function() {_this._AllowCalendarExportHandler(this.checked);};
}

JCEC.prototype.ShowEditCalDialog = function(oCalen)
{
	if (!this.EditCalDialog)
		this.CreateEditCalDialog();
	else if(this.bEditCalDialogShow)
		return this.CloseEditCalDialog();

	this.EditCalDialog.style.display = 'block';

	if (!oCalen)
	{
		oCalen = {};
		this.oEdCalDialog.bNew = true;
		this.oEdCalDialog.dialogTitle.innerHTML = EC_MESS.NewCalenTitle;
		this.oEdCalDialog.delBut.style.display = 'none';
		this._CalDialogSetColor(this.arConfig.arCalColors[0]);
		this.oEdCalDialog.oExpAllow.checked = true;
		this._AllowCalendarExportHandler(true);
		if (this.oEdCalDialog.oExpSet)
			this.oEdCalDialog.oExpSet.value = 'all';

		if (this.bSuperpose)
		{
			this.oEdCalDialog.add2SP.checked = true;
			this.oEdCalDialog.add2SPCont.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
		}

		if (this.bUser)
			this.oEdCalDialog.oStatus.value = 'full';

		if (this.arConfig.bExchange)
		{
			this.oEdCalDialog.pExch.disabled = false;
			this.oEdCalDialog.pExch.checked = true;
		}
	}
	else
	{
		if (this.arConfig.bExchange)
		{
			this.oEdCalDialog.pExch.disabled = true;
			this.oEdCalDialog.pExch.checked = !!oCalen.IS_EXCHANGE;
		}

		this.oEdCalDialog.bNew = false;
		this.oEdCalDialog.dialogTitle.innerHTML = EC_MESS.EditCalenTitle;
		this.oEdCalDialog.delBut.style.display = 'inline';
		this._CalDialogSetColor(oCalen.COLOR || this.arConfig.arCalColors[0]);

		this.oEdCalDialog.oExpAllow.checked = oCalen.EXPORT || false;
		this._AllowCalendarExportHandler(oCalen.EXPORT);
		if (oCalen.EXPORT)
			this.oEdCalDialog.oExpSet.value = oCalen.EXPORT_SET || 'all';
		if (this.bSuperpose)
			this.oEdCalDialog.add2SPCont.style.display = 'none';
		if (this.bUser)
			this.oEdCalDialog.oStatus.value = oCalen.PRIVATE_STATUS || 'full';
	}
	this.oEdCalDialog.currentCalendar = oCalen;
	this.bEditCalDialogOver = false;

	if (this.bUser)
		this.oEdCalDialog.oMeetingCalendarCh.checked = (!this.oEdCalDialog.bNew && this.meetingCalendarId == oCalen.ID);

	var _this = this;
	BX.bind(document, "keypress", window['BXEC_EdCal_OnKeypress_' + this.id]);
	setTimeout(function(){BX.bind(document, "click", window['BXEC_EdCal_OnClick_' + _this.id]);},1);
	this.oEdCalDialog.oName.value = bxSpChBack(oCalen.NAME) || '';
	this.oEdCalDialog.oDesc.value = bxSpChBack(oCalen.DESCRIPTION) || '';

	var h = parseInt(this.EditCalDialog.firstChild.offsetHeight) + 10;
	this.ResizeDialogWin(this.EditCalDialog, 430, h);
	var pos = this.GetCenterWindowPos(430, h);
	this.bEditCalDialogShow = true;
	jsFloatDiv.Show(this.EditCalDialog, pos.left, pos.top, 5, false, false);
	this.oEdCalDialog.oName.focus();
	if(!this.oEdCalDialog._shad)
	{
		this.oEdCalDialog._shad = BX('bxec_edcal_' + _this.id + '_shadow');
		this.EditCalDialog.parentNode.appendChild(this.oEdCalDialog._shad);
	}
}

JCEC.prototype.CloseEditCalDialog = function()
{
	this.bEditCalDialogShow = false;
	this.EditCalDialog.style.display = 'none';
	jsFloatDiv.Close(this.EditCalDialog);
	BX.unbind(document, "keypress", window['BXEC_EdCal_OnKeypress_' + this.id]);
	BX.unbind(document, "click", window['BXEC_EdCal_OnClick_' + this.id]);
}

JCEC.prototype._CalDialogSetColor = function(color)
{
	try{
		this.oEdCalDialog.colorCell.style.backgroundColor = color;
	}
	catch(e)
	{
		color = this.arConfig.arCalColors[0];
		this.oEdCalDialog.colorCell.style.backgroundColor = color;
	}
	this.oEdCalDialog.colorInput.value = color;
}

JCEC.prototype._AllowCalendarExportHandler = function(bAllow)
{
	if (!this.oEdCalDialog.oExpDiv)
		this.oEdCalDialog.oExpDiv = BX(this.id + '_bxec_calen_exp_div');
	if (!this.oEdCalDialog.oExpSet && bAllow)
		this.oEdCalDialog.oExpSet = BX(this.id + '_bxec_calen_exp_set');
	this.oEdCalDialog.oExpDiv.style.display = bAllow ? 'block' : 'none';
	var h = parseInt(this.EditCalDialog.firstChild.offsetHeight) + 20; // resize dialog
	this.ResizeDialogWin(this.EditCalDialog, 400, h);
}

// # # #  #  #  # Export Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateExportCalDialog = function()
{
	this.oExpCalDialog = new this.BXECDialogCore({
		obj : this,
		name : 'EXPORT',
		id : 'bxec_excal_' + this.id,
		close_id: this.id + '_excal_close',
		cancel_id: this.id + '_excal_cancel',
		shadow_id: 'bxec_excal_' + this.id + '_shadow',
		bEscClose: true,
		bClickClose: true,
		width: 750
	});

	this.oExpCalDialog.oLink = BX(this.id + '_excal_link');
	this.oExpCalDialog.oNoticeLink = BX(this.id + '_excal_link_outlook');
	this.oExpCalDialog.oTitle = BX(this.id + '_excal_dial_title');
	this.oExpCalDialog.oText = BX(this.id + '_excal_text');
	this.oExpCalDialog.oWarn = BX(this.id + '_excal_warning');

	var _this = this;
	this.oExpCalDialog.oNoticeLink.onclick = function()
	{
		this.parentNode.className = "";
		_this.oExpCalDialog.Resize(false);
	};
}

JCEC.prototype.ShowExportCalDialog = function(oCalen)
{
	if (!this.oExpCalDialog)
		this.CreateExportCalDialog();
	if (!this.oExpCalDialog.PreShow()) // Dialog opened
		return;

	this.oExpCalDialog.oNoticeLink.parentNode.className = "bxec-excal-notice-hide"; // Hide help
	this.oExpCalDialog.oWarn.className = 'bxec-export-warning-hidden';

	// Create link
	var link = this.arConfig.fullUrl;
	link += (link.indexOf('?') >= 0) ? '&' : '?';

	if (oCalen)
	{
		this.oExpCalDialog.oTitle.innerHTML = EC_MESS.ExpDialTitle;
		this.oExpCalDialog.oText.innerHTML = EC_MESS.ExpText;
		link += 'action=export' + oCalen.EXPORT_LINK;
	}
	else
	{
		this.oExpCalDialog.oTitle.innerHTML = EC_MESS.ExpDialTitleSP;
		this.oExpCalDialog.oText.innerHTML = EC_MESS.ExpTextSP;
		link += 'action=export' + this.arConfig.superposeExportLink;
	}

	var webCalLink = 'webcal' + link.substr(link.indexOf('://'));
	this.oExpCalDialog.oLink.onclick = function(e) {window.location.href = webCalLink; BX.PreventDefault(e);};
	this.oExpCalDialog.oLink.href = link;
	this.oExpCalDialog.oLink.innerHTML = link;

	var _this = this;
	var handler = function(result)
	{
		setTimeout(function()
			{
				_this.CloseWaitWindow();
				if (!result || result.length <= 0 || result.toUpperCase().indexOf('BEGIN:VCALENDAR') == -1)
					_this.oExpCalDialog.oWarn.className = 'bxec-export-warning';
			}, 300);
	};
	this.NullServerVars();
	this.ShowWaitWindow();
	BX.ajax.get(link + '&check=Y', "", handler);

	this.oExpCalDialog.Show(this.oExpCalDialog.Resize(true));
}

// # # #  #  #  # Superpose Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateSuperposeDialog = function()
{
	this.oSuperposeDialog = new this.BXECDialogCore({
		obj : this,
		name : 'SUPERPOSE',
		id : 'bxec_sprpose_' + this.id,
		close_id: this.id + '_sprpose_close',
		cancel_id: this.id + '_sprpose_cancel',
		save_id: this.id + '_sprpose_save',
		shadow_id: 'bxec_sprpose_' + this.id + '_shadow',
		bEscClose: true,
		bClickClose: false,
		width: 560
	});
	this.oSuperposeDialog.oCont = BX(this.id + '_sprpose_cont');
	var _this = this;
	this.oSuperposeDialog.saveBut.onclick = function(){_this.AppendSPCalendars(_this.SPD_GetSelectedSPCalendars()); _this.oSuperposeDialog._Close();};

	this.oSuperposeDialog.arGroups = {};
	this.oSuperposeDialog.arCals = {};

	var i, l, el, oGroup;
	for (i = 0, l = this.arSPCalendars.length; i < l; i++)
	{
		el = this.arSPCalendars[i];
		if (el.ITEMS.length < 1)
			continue;

		oGroup = this.SPD_GetGroup(el.GROUP, el.GROUP_TITLE); // SPD - SuperPose Dialog
		this.SPD_DisplayCalendars(oGroup, el);
	}

	if (this.arConfig.bSPUserCals)
	{
		this.oSuperposeDialog.pSPUSICont = BX(this.id + '_sp_user_search_input_cont');
		this.oSuperposeDialog.pSPUSICont_parent = this.oSuperposeDialog.pSPUSICont.parentNode;
		this.oSuperposeDialog.pSPUSICont.style.display = 'block';
		this.oSuperposeDialog.oUserGroup = this.SPD_GetGroup('SOCNET_USERS', EC_MESS.UserCalendars); // SPD - SuperPose Dialog
		this.oSuperposeDialog.oUserGroup.ElementsCont.appendChild(this.oSuperposeDialog.pSPUSICont);
		this.SPD_ExtendUserSearchInput();
		window["SPAddUser_" + this.id] = function(name) {_this.SPD_GetUserCalendars(name);};
	}
}

JCEC.prototype.SPD_Renew = function()
{
	if (this.oSuperposeDialog)
	{
		this.oSuperposeDialog.pSPUSICont_parent.appendChild(this.oSuperposeDialog.pSPUSICont); // Save user search input
		BX.cleanNode(this.oSuperposeDialog.oCont);
		this.oSuperposeDialog = null;
		//window.SonetTcLoadTI = false;
		window.oObject = {};
	}
}

JCEC.prototype.SPD_ExtendUserSearchInput = function()
{
	if (!window.SonetTCJsUtils)
		return;
	var _this = this;
	if (!SonetTCJsUtils.EC__GetRealPos)
		SonetTCJsUtils.EC__GetRealPos = SonetTCJsUtils.GetRealPos;

	SonetTCJsUtils.GetRealPos = function(el)
	{
		var res = SonetTCJsUtils.EC__GetRealPos(el);
		if (_this.oSuperposeDialog && _this.oSuperposeDialog.bShow)
		{
			scrollTop = _this.oSuperposeDialog.oCont.scrollTop;
			res.top = bxInt(res.top) - scrollTop;
			res.bottom = bxInt(res.bottom) - scrollTop;
		}
		return res;
	}

	if (BX.browser.IsIE())
	{
		if (!SonetTCJsUtils._show)
			SonetTCJsUtils._show = SonetTCJsUtils.show;
		SonetTCJsUtils.show = function(oDiv, iLeft, iTop)
		{
			var _oDiv = SonetTCJsUtils._show(oDiv, iLeft, iTop);
			if (!_this._hideFrame)
			{
				_this._hideFrame = true;
				var oFrame = BX(oDiv.id+"_frame");
				if (oFrame)
					oFrame.style.display = 'none';
			}
			return _oDiv;
		}
	}
}

JCEC.prototype.SPD_GetUserCalendars = function(name)
{
	var
		_this = this,
		iter = 0;
	var handler = function(result)
	{
		var handleRes = function()
		{
			_this.CloseWaitWindow();
			iter++;
			if (!result || result.length <= 0 || result.toLowerCase().indexOf('bx_event_calendar_action_error') != -1)
				return _this.DisplayError();
			if (window._bx_result)
				_this.SPD_HandleUserCalendars(window._bx_result);
			else if(iter < 20)
				setTimeout(handleRes, 5);
		};
		setTimeout(handleRes, 10);
	};
	this.NullServerVars();
	this.ShowWaitWindow();
	BX.ajax.post(this.actionUrl, this.GetPostData('spcal_user_cals', {name : name}), handler);
}

JCEC.prototype.SPD_HandleUserCalendars= function(ob)
{
	if (ob.length <= 0)
	{
		if (!this.oSuperposeDialog.oUsersNFCont)
			this.oSuperposeDialog.oUsersNFCont= BX(this.id + '_sp_user_nf_notice');
		var div = this.oSuperposeDialog.oUsersNFCont;
		div.style.visibility = 'visible';
		setTimeout(function(){div.style.visibility = 'hidden';}, 3000)
	}

	for (var i = 0, l = ob.length; i < l; i++)
	{
		ob[i].bDynamic = true;
		ob[i].bDeletable = true;
		this.SPD_DisplayCalendars(this.oSuperposeDialog.oUserGroup, ob[i], true);
	}
}

JCEC.prototype.SPD_DelAllTrackingUsers = function()
{
	if (!confirm(EC_MESS.DelAllTrackingUsersConfirm))
		return;
	this.SPD_DelAllTrackingUsersClientSide();
	var _this = this;
	var handler = function() {_this.CloseWaitWindow();};
	this.NullServerVars();
	this.ShowWaitWindow();

	BX.ajax.post(this.actionUrl, this.GetPostData('spcal_del_all_user'), handler);
}

JCEC.prototype.SPD_DelAllTrackingUsersClientSide = function()
{
	var i, l, j, n, arDeletedCals = {}, id, cal, el;
	var bRenew = false;
	l = this.arSPCalendars.length;
	var _newAr = [];
	for (i = 0; i < l; i++)
	{
		el = this.arSPCalendars[i];
		if (el.GROUP != 'SOCNET_USERS' || this.arConfig.userId == el.USER_ID)
		{
			_newAr.push(el);
			continue;
		}

		n = el.ITEMS.length;
		for (j = 0; j < n; j++)
		{
			cal = el.ITEMS[j];
			arDeletedCals[cal.ID] = true;
		}
	}
	this.arSPCalendars = _newAr;

	for (i = 0, l = this.arSPCalendarsShow.length; i < l; i++)
	{
		cal = this.arSPCalendarsShow[i];
		if (arDeletedCals[cal.ID])
		{
			if (!cal._bro)
			{
				this.oActiveCalendars[cal.ID] = false;
				this.oCalendars[cal.ID] = null;
			}
			else
			{
				this.arCalendars[cal._bro.ind]._bro = null;
			}
			this.arSPCalendarsShow = deleteFromArray(this.arSPCalendarsShow, i);
			i--;
			l = this.arSPCalendarsShow.length;
		}
	}
	this.oSuperposeDialog.Close();
	this.SPD_Renew();
	this.ShowSuperposeDialog();
	this.oSuperposeDialog.oDelAllUsersLink.style.display = 'none'; // Hide link
}

JCEC.prototype.SPD_DelTrackingUser = function(user_id, pElement)
{
	this.SPD_DelTrackingUserClientSide(pElement, user_id);
	var _this = this, iter = 0;
	var handler = function(result)
	{
		var handleRes = function()
		{
			_this.CloseWaitWindow();
			iter++;
			if (!result || result.length <= 0 || result.toLowerCase().indexOf('bx_event_calendar_action_error') != -1)
				return _this.DisplayError();
			if (window._bx_result)
				return true;
			else if(iter < 20)
				setTimeout(handleRes, 5);
		};
		setTimeout(handleRes, 10);
	};
	this.NullServerVars();
	this.ShowWaitWindow();
	BX.ajax.post(this.actionUrl, this.GetPostData('spcal_del_user', {id : bxInt(user_id)}), handler);
}

JCEC.prototype.SPD_DelTrackingUserClientSide= function(pEl, uid)
{
	var tbl = BX.findParent(pEl, {tagName: 'TABLE'});
	tbl.parentNode.removeChild(tbl);

	var i, l, j, n, arDeletedCals = {}, id, cal;
	for (i = 0, l = this.arSPCalendars.length; i < l; i++)
	{
		el = this.arSPCalendars[i];
		if (el.USER_ID != uid)
			continue;
		for (j = 0, n = el.ITEMS.length; j < n; j++)
		{
			cal = el.ITEMS[j];
			arDeletedCals[cal.ID] = true;
			this.oSuperposeDialog.arCals[cal.ID] = {};
		}
		this.arSPCalendars = deleteFromArray(this.arSPCalendars, i);
		break;
	}

	for (i = 0, l = this.arSPCalendarsShow.length; i < l; i++)
	{
		cal = this.arSPCalendarsShow[i];
		if (arDeletedCals[cal.ID])
		{
			if (!cal._bro)
			{
				this.oActiveCalendars[cal.ID] = false;
				this.oCalendars[cal.ID] = null;
			}
			else
			{
				this.arCalendars[cal._bro.ind]._bro = null;
			}
			this.arSPCalendarsShow = deleteFromArray(this.arSPCalendarsShow, i);
			i--;
			l = this.arSPCalendarsShow.length;
		}
	}
}

JCEC.prototype.SPD_GetGroup = function(id, title)
{
	if (this.oSuperposeDialog.arGroups[id])
		return this.oSuperposeDialog.arGroups[id];

	var
		tbl = BX.create('TABLE', {props: {className: 'bxec-spd-group'}}),
		r = tbl.insertRow(-1),
		c_t = r.insertCell(-1);

	c_t.className = 'bxec-spd-group-title';
	c_t.innerHTML = '<img class="bxec-iconkit bxec-spd-big-plus" src="/bitrix/images/1.gif"/>' + BX.util.htmlspecialchars(title);
	if (id == 'SOCNET_USERS')
	{
		var c = r.insertCell(-1);
		c.className = 'bxec-spd-group-title';
		c.style.textAlign = 'right';
		var link = c.appendChild(BX.create('A', {
			props: {href: 'javascript:void(0)', title: EC_MESS.DeleteAllUserCalendars, className: 'bxec-del-dyn-spgr'},
			style: {display: 'none'},
			events: {click: function(){_this.SPD_DelAllTrackingUsers();}},
			html: EC_MESS.DeleteDynSPGroup
		}));
		this.oSuperposeDialog.oDelAllUsersLink = link;
	}
	r = tbl.insertRow(-1);
	var c = r.insertCell(-1);
	c.className = 'bxec-spd-group-cont';
	if (id == 'SOCNET_USERS')
		c.colSpan = '2';

	var _gr = {ID: id, bHidden: false, ElementsCont: c};
	var _this = this;
	c_t.onclick = function()
	{
		_gr.bHidden = !_gr.bHidden;
		if (_gr.bHidden)
		{
			_gr.ElementsCont.parentNode.style.display = 'none';
			BX.addClass(this, 'bxec-spd-title-hide');
		}
		else
		{
			_gr.ElementsCont.parentNode.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
			BX.removeClass(this, 'bxec-spd-title-hide');
		}
		_this.oSuperposeDialog.Resize(false);
	}
	this.oSuperposeDialog.oCont.appendChild(tbl);
	this.oSuperposeDialog.arGroups[id] = _gr;
	return _gr;
}

JCEC.prototype.SPD_DisplayCalendars = function(oGroup, Item, bDefCheck)
{
	if (!bDefCheck)
		bDefCheck = false;
	var
		tbl = BX.create('TABLE', {props: {className: 'bxec-spd-cal-sec'}}),
		r = tbl.insertRow(-1),
		c_t = r.insertCell(-1);

	c_t.className = 'bxec-spd-cal-sec-title';
	c_t.innerHTML = '<img class="bxec-iconkit bxec-spd-small-plus" src="/bitrix/images/1.gif"/>' + Item.NAME;
	if (Item.bDeletable)
	{
		var _this = this;
		var c = r.insertCell(-1);
		c.className = 'bxec-spd-cal-sec-title';
		c.style.textAlign = 'right';
		c.appendChild(BX.create('A', {
			props: {href: 'javascript:void(0)', title: EC_MESS.DeleteDynSPGroupTitle, className: 'bxec-del-dyn-spgr'},
			events: {click: function(){_this.SPD_DelTrackingUser(Item.USER_ID, this);}},
			html: EC_MESS.DeleteDynSPGroup
		}));
	}
	r = tbl.insertRow(-1);
	var c_cont = r.insertCell(-1);
	c_cont.className = 'bxec-spd-cal-sec-cont';
	if (Item.bDeletable)
		c_cont.colSpan = '2'

	var _cont = {bHidden: false, pRow: r};
	c_t.onclick = function()
	{
		_cont.bHidden = !_cont.bHidden;
		if (_cont.bHidden)
		{
			_cont.pRow.style.display = 'none';
			BX.addClass(this, 'bxec-cal-sec-hide');
		}
		else
		{
			_cont.pRow.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
			BX.removeClass(this, 'bxec-cal-sec-hide');
		}
	}

	// Append calendars with checkboxes
	var i, l, el, elid, eltbl, elrow, c_ch, c_name, elcheck;
	if (Item.ITEMS.length < 1)
	{
		c_cont.innerHTML = '<span class="bxec-spd-notice">' + EC_MESS.CalsAreAbsent + '</span>';
	}
	for (i = 0, l = Item.ITEMS.length; i < l; i++)
	{
		el = Item.ITEMS[i];

		eltbl = BX.create('TABLE', {props: {className: 'bxec-spd-cal'}});
		elrow = eltbl.insertRow(-1);

		elid = 'bxec_superpose_cal_' + el.ID;
		c_ch = elrow.insertCell(-1);
		elcheck = c_ch.appendChild(BX.create('INPUT', {props: {type: 'checkbox', id: elid, checked: bDefCheck}}));

		c_name = elrow.insertCell(-1);
		c_name.style.paddingLeft = "5px";
		c_name.innerHTML = '<label for="' + elid + '">' + el.NAME + '</label>';
		eltbl.title = BX.util.htmlspecialcharsback(el.NAME + (el.DESCRIPTION ? "\n" + el.DESCRIPTION : ''));
		c_cont.appendChild(eltbl);
		this.oSuperposeDialog.arCals[el.ID] = {checkbox: elcheck, item: el};
	}

	if (Item.bDynamic)
		oGroup.ElementsCont.insertBefore(tbl, oGroup.ElementsCont.lastChild);
	else
		oGroup.ElementsCont.appendChild(tbl);

	if (Item.USER_ID && Item.USER_ID != this.arConfig.userId)
	{
		if (this.oSuperposeDialog.oDelAllUsersLink)
			this.oSuperposeDialog.oDelAllUsersLink.style.display = 'inline';
	}
}

JCEC.prototype.SPD_SetSelectedSPCalendars = function()
{
	var i, l, id;
	var arCals = this.oSuperposeDialog.arCals;
	for (i in arCals)
	{
		if (typeof arCals[i] == 'object' && arCals[i].checkbox)
			arCals[i].checkbox.checked = false; // check OFF all checkboxes
	}

	for (i = 0, l = this.arSPCalendarsShow.length; i < l; i++)
	{
		id = bxInt(this.arSPCalendarsShow[i].ID);
		if (arCals[id] && arCals[id].checkbox)
			arCals[id].checkbox.checked = true; // Check ON all displayed calendars
	}
}

JCEC.prototype.SPD_GetSelectedSPCalendars = function()
{
	var arCals = this.oSuperposeDialog.arCals, new_ar = [], i;
	for (i in arCals)
	{
		if (typeof arCals[i] == 'object' && arCals[i].checkbox && arCals[i].checkbox.checked)
			new_ar.push(arCals[i].item);
	}
	return new_ar;
}

JCEC.prototype.ShowSuperposeDialog = function()
{
	if (!this.oSuperposeDialog)
		this.CreateSuperposeDialog();
	if (!this.oSuperposeDialog.PreShow()) // Dialog opened
		return;

	this.SPD_SetSelectedSPCalendars();
	this.oSuperposeDialog.Show(this.oSuperposeDialog.Resize(true));
}

// # # #  #  #  # View Company Structure Dialog # # #  #  #  #
JCEC.prototype.CreateCompStrucDialog = function()
{
	var CS = new this.BXECDialogCore({
		obj : this,
		name : 'COMP_STRUC',
		id : 'bxec_vcsd_' + this.id,
		close_id: this.id + '_vcsd_close',
		cancel_id: this.id + '_vcsd_cancel',
		shadow_id: 'bxec_vcsd_' + this.id + '_shadow',
		bEscClose: true,
		bClickClose: true,
		width: 470,
		height: 411,
		zIndex: 550
	});

	var _this = this;
	CS.Cont = BX(this.id + '_vcsd_cont');
	this.oCompStrucDialog = CS;

	this.Request({
		postData: _this.GetPostData('get_company_structure'),
		handler: function(result)
		{
			if (result.indexOf('bx_ec_no_structure_data') != -1)
				return alert(EC_MESS.NoCompanyStructure);

			_this.oCompStrucDialog.Cont.innerHTML = result;
			_this.ShowCompStrucDialog(result);
		}
	});

	// Save button
	BX(this.id + '_vcsd_save').onclick = function(){_this.EEUC.AddFromStructureDialog();};
}

JCEC.prototype.ShowCompStrucDialog = function()
{
	if (!this.oCompStrucDialog)
		return this.CreateCompStrucDialog();

	if (!this.oCompStrucDialog.PreShow()) // Dialog opened
		return;

	var
		_this = this,
		CS = this.oCompStrucDialog;

	// Set checkboxes unchecked
	if (CS.arCheckboxes)
	{
		var
			arCh = CS.Cont.getElementsByTagName('INPUT'),
			i, l = arCh.length;

		for (i = 0; i < l; i++)
		{
			if (arCh[i].checked)
				arCh[i].checked = false;
		}
	}

	this.oCompStrucDialog.Show(this.oCompStrucDialog.Resize(true));
}

function BxecCS_SwitchSection(el, div_id, e)
{
	if (e)
	{
		if(e.target)
			e.targetElement = e.target;
		else if(e.srcElement)
			e.targetElement = e.srcElement;

		if (e.targetElement.nodeName.toUpperCase() == 'INPUT') // Checkbox
			return true;
	}

	var bCollapse = (el.className == 'vcsd-arrow-down');
	el.className = (bCollapse? 'vcsd-arrow-right' : 'vcsd-arrow-down');
	BX(div_id).style.display = (bCollapse? 'none' : 'block');
}

function BxecCS_SwitchUser(id, e)
{
	if (e)
	{
		if(e.target)
			e.targetElement = e.target;
		else if(e.srcElement)
			e.targetElement = e.srcElement;

		if (e.targetElement.nodeName.toUpperCase() == 'INPUT') // Checkbox
			return true;
	}
	var ch = BX(id);
	ch.checked = !ch.checked;
}

function BxecCS_CheckGroup(el)
{
	var obj_div = BX(el.id+'_block');
	if(obj_div)
	{
		/*users in this group*/
		var obj = BX.findChild(obj_div, {tagName: 'DIV', className: 'vcsd-user-contact'}, true);
		do
		{
			var chbox = BX.findChild(obj, {tagName: 'INPUT'}, true);
			if(chbox)
				chbox.checked = el.checked;
		}
		while(obj = BX.findNextSibling(obj, {tagName: 'div'}));

		//subgroups
		obj = BX.findChild(obj_div, {tagName: 'div', className: 'vcsd-user-section'}, true);
		if(obj)
		{
			do
			{
				var chbox = BX.findChild(obj, {tagName: 'input'}, true);
				if(chbox)
				{
					chbox.checked = el.checked;
					BxecCS_CheckGroup(chbox);
				}
			}
			while(obj = BX.findNextSibling(obj, {tagName: 'div'}));
		}
	}
}

// More events window
JCEC.prototype.CreateMoreEventsWin = function()
{
	var _this = this;
	var pWin = BX.create('DIV', {
		props: {id: 'bxec_more_event_' + this.id, className : 'bxec-more-event-dialog'},
		style: {display: 'none'},
		events: {mouseover: function(){_this.MoreEventsWin.bOver = true;}, mouseout: function(){_this.MoreEventsWin.bOver = false;}}
	});

	this.MoreEventsWin = {bShow: false, bOver: false, pWin: document.body.appendChild(pWin)};
	window['BXEC_MoreEvWin_OnKeypress_' + this.id] = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.CloseMoreEventsWin();
	};
	window['BXEC_MoreEvWin_OnClick_' + this.id] = function(e)
	{
		if(!e) e = window.event;
		if(!e) return;
		if (!_this.MoreEventsWin.bShow || _this.MoreEventsWin.bOver)
			return;
		_this.CloseMoreEventsWin();
	};
}

JCEC.prototype.ShowMoreEventsWin = function(P)
{
	try{ // For anonymus  users to catch phpVars init errors
	if (!this.MoreEventsWin)
		this.CreateMoreEventsWin();

	if(this.MoreEventsWin.bShow)
	{
		this.CloseMoreEventsWin();
		if (this.MoreEventsWin.curDayId == P.id)
			return;
	}

	this.MoreEventsWin.pWin.style.display = 'block';

	var
		width = 200,
		length = P.Events.length,
		pNewDiv, pOldDiv, newActDiv, oldActDiv,
		i, j, n = 3,
		_this = this;
	this.MoreEventsWin.pWin.style.width = width + "px";
	this.MoreEventsWin.pWin.innerHTML = "";
	this.MoreEventsWin.curDayId = P.id;
	this.MoreEventsWin.bShow = true;
	BX.bind(document, "keypress", window['BXEC_MoreEvWin_OnKeypress_' + this.id]);
	setTimeout(function(){BX.bind(document, "click", window['BXEC_MoreEvWin_OnClick_' + _this.id]);}, 1);

	var pos = BX.pos(P.pDay);
	pos.left += 2;
	if (P.mode == 'day_t') // Day-week view
		pos.top = pos.bottom + 14;
	else // Month view
		pos.top = pos.bottom - 4;
	pos.right = pos.left;
	pos.bottom = pos.top + (length * 18) + 2;

	for (i = 0; i < length; i++)
	{
		pOldDiv = P.Events[i].pDiv;
		pNewDiv = pOldDiv.cloneNode(true);

		BX.addClass(pNewDiv, 'bxec-event-static');
		new BX.CHintSimple({parent: pNewDiv, hint: P.Events[i].oEvent.hintContent});
		pNewDiv.style.width = width - 2 + 'px';
		pNewDiv.onmouseover = pOldDiv.onmouseover;
		pNewDiv.onmouseout = pOldDiv.onmouseout;
		pNewDiv.ondblclick = pOldDiv.ondblclick;
		pNewDiv.style.position = 'static';
		pNewDiv.style.display = 'block';
		// Copy actions panel
		newActDiv = pNewDiv.firstChild.rows[0].cells[1].childNodes[1].firstChild;
		oldActDiv = pOldDiv.firstChild.rows[0].cells[1].childNodes[1].firstChild;
		n = oldActDiv.childNodes.length;
		for (j = 0; j < n; j++)
			newActDiv.childNodes[j].onclick = oldActDiv.childNodes[j].onclick;
		this.MoreEventsWin.pWin.appendChild(pNewDiv);
	}
	jsFloatDiv.Show(this.MoreEventsWin.pWin, pos.left, pos.top, 5, false, false);
	}catch(e){}
}

JCEC.prototype.CloseMoreEventsWin = function()
{
	if (!this.MoreEventsWin)
		return;
	this.MoreEventsWin.bShow = false;
	this.MoreEventsWin.pWin.style.display = 'none';
	jsFloatDiv.Close(this.MoreEventsWin.pWin);
	BX.unbind(document, "keypress", window['BXEC_MoreEvWin_OnKeypress_' + this.id]);
	BX.unbind(document, "click", window['BXEC_MoreEvWin_OnClick_' + this.id]);
}

// # # #  #  #  # Dialogs Common Tools # # #  #  #  #
JCEC.prototype.ResizeDialogWin = function(div, w, h)
{
	if (w !== false)
		div.style.width = parseInt(w) + 'px';
	if (h !== false)
		div.style.height = parseInt(h) + 'px';

	setTimeout(function(){jsFloatDiv.AdjustShadow(div);}, 1);
}

JCEC.prototype.InitDialogCore = function()
{
	// Move div with dialogs to body
	document.body.appendChild(BX(this.id + "_dialogs_cont"));

	this.BXECDialogCore = function(arParams)
	{
		this.pWnd = BX(arParams.id);
		this.bShow = false;
		this.bOver = true;
		var _this = this;
		var obj = arParams.obj;
		var objId = obj.id;

		if (arParams.close_id)
		{
			this.closeBut = BX(arParams.close_id);
			this.closeBut.onclick = function() {_this._Close();};
			this.oTitle = this.closeBut.parentNode.cells[this.closeBut.cellIndex - 1];
		}

		if (arParams.cancel_id)
		{
			this.cancelBut = BX(arParams.cancel_id);
			this.cancelBut.onclick = function() {_this._Close();};
		}
		if (arParams.save_id)
			this.saveBut = BX(arParams.save_id);

		this.onkeypress_k = 'BXEC_' + arParams.name + '_OnKeypress_' + objId;
		window[this.onkeypress_k] = function(e)
		{
			if(!e) e = window.event
			if(!e) return;
			if(e.keyCode == 27)
				_this._Close();
			else if(arParams.onEnter && typeof arParams.onEnter == 'function' && EnterAndNotTextArea(e))
				arParams.onEnter();
		};
		if (arParams.bClickClose)
		{
			this.onclick_k = 'BXEC_' + arParams.name + '_OnClick_' + objId;
			window[this.onclick_k] = function(e)
			{
				if (window._BXEC_EvDynCloseInt_onclick)
					return;
				setTimeout(function(){
					if(!e) e = window.event;
					if(!e) return;
					if (!_this.bShow || _this.bOver || _this.bHold)
						return;
					_this._Close();
				}, 10);
			};
		}

		this.pWnd.onmouseover = function(){_this.bOver = true;};
		this.pWnd.onmouseout = function(){_this.bOver = false;};

		this.PreShow = function()
		{
			if(this.bShow)
			{
				this._Close();
				return false;
			}

			this.pWnd.style.display = 'block';

			BX.bind(document, "keypress", window[this.onkeypress_k]);
			this.bOver = false;
			return true;
		};

		this.Resize = function(bLocate) // Resize & locate
		{
			var w = arParams.width || parseInt(this.pWnd.firstChild.offsetWidth) + 20;
			var h = arParams.height || parseInt(this.pWnd.firstChild.offsetHeight) + 10;
			obj.ResizeDialogWin(this.pWnd, w, h);
			if (bLocate)
				return pos = obj.GetCenterWindowPos(w, h);
		};

		this.Show = function(pos) // Show
		{
			obj.CloseMoreEventsWin();
			this.bShow = true;

			if (!arParams.zIndex)
				arParams.zIndex = 500;

			this.pWnd.style.zIndex = arParams.zIndex;


			jsFloatDiv.Show(this.pWnd, pos.left, pos.top, 5, false, false);
			if (arParams.bClickClose)
				setTimeout(function(){BX.bind(document, "click", window[_this.onclick_k]);}, 200);
			if(!this._shad) // FF2 bug fix
			{
				this._shad = BX(arParams.shadow_id);
				this.pWnd.parentNode.appendChild(this._shad);
				if (arParams.zIndex)
					this._shad.style.zIndex - 5;
			}
		};

		this.Close = function()
		{
			this.bShow = false;
			this.pWnd.style.display = 'none';
			jsFloatDiv.Close(this.pWnd);
			BX.unbind(document, "keypress", window[this.onkeypress_k]);
			if (arParams.bClickClose)
				setTimeout(function(){BX.unbind(document, "click", window[_this.onclick_k]);}, 300);
		};

		this._Close = function()
		{
			if (arParams.fClose)
				obj[arParams.fClose]();
			else
				this.Close();
		};

		this.SetTitle = function(str)
		{
			if (this.oTitle)
				this.oTitle.innerHTML = str;
		}
	};
}


JCEC.prototype.UpdateCalendarSelector = function(oSel)
{
	oSel.options.length = 0;
	var i, l = this.arCalendars.length, opt, el;
	if (l < 1)
	{
		oSel.parentNode.className = 'bxec-cal-sel-cel-empty';
		return;
	}
	oSel.parentNode.className = 'bxec-cal-sel-cel';
	for (i = 0; i < l; i++)
	{
		el = this.arCalendars[i];
		opt = new Option(bxSpChBack(el.NAME), el.ID, (i == 0), (i == 0));
		oSel.options.add(opt);
		opt.style.backgroundColor = el.COLOR;
	}
	if (oSel.options.length > 0)
		oSel.options[0].selected = 'true';

	if(oSel.onchange)
		oSel.onchange();
}

JCEC.prototype.DeActualizeCalendarSelectors = function()
{
	if (this.oEditEventDialog)
		this.oEditEventDialog.bCalendarsActual = false;
	if (this.oAddEventDialog)
		this.oAddEventDialog.bCalendarsActual = false;
}

// # # #  #  #  # User Settings Dialog # # #  #  #  #
JCEC.prototype.CreateUSetDialog = function()
{
	var US = new this.BXECDialogCore({
		obj : this,
		name : 'USER_SET',
		id : 'bxec_uset_' + this.id,
		close_id: this.id + '_uset_close',
		cancel_id: this.id + '_uset_cancel',
		shadow_id: 'bxec_uset_' + this.id + '_shadow',
		bEscClose: true,
		bClickClose: true,
		width: 420,
		height: 155,
		zIndex: 550
	});

	var _this = this;
	US.oCalendSelect = BX(this.id + '_uset_calend_sel');
	US.oBlink = BX(this.id + '_uset_blink');
	US.oUsetClearAll = BX(this.id + '_uset_clear');

	US.oUsetClearAll.onclick = function()
	{
		_this.SaveSettings(true);
		US.oCalendSelect.value = 0;
		US.oBlink.checked = true;
		US.Close();
		window.location = window.location;
	};

	this.oUSetDialog = US;

	// Save button
	BX(this.id + '_uset_save').onclick = function()
	{
		var val = parseInt(US.oCalendSelect.value);
		if (isNaN(val) || val == 0)
			val = false;
		_this.meetingCalendarId = val;
		_this.arConfig.Settings.blink = !!US.oBlink.checked;
		_this.SaveSettings();
		US.Close();
	};
}

JCEC.prototype.ShowUSetDialog = function()
{
	if (!this.oUSetDialog)
		this.CreateUSetDialog();

	if (!this.oUSetDialog.PreShow()) // Dialog opened
		return;

	var US = this.oUSetDialog;

	US.oCalendSelect.options.length = 0;
	var i, l = this.arCalendars.length, opt, el, sel = !this.meetingCalendarId;
	US.oCalendSelect.options.add(new Option(' - ' + EC_MESS.FirstInList + ' - ', 0, sel, sel));

	for (i = 0; i < l; i++)
	{
		el = this.arCalendars[i];
		sel = this.meetingCalendarId == el.ID;
		opt = new Option(bxSpChBack(el.NAME), el.ID, sel, sel);
		US.oCalendSelect.options.add(opt);
		opt.style.backgroundColor = el.COLOR;
	}

	US.oBlink.checked = !!this.arConfig.Settings.blink;
	this.oUSetDialog.Show(this.oUSetDialog.Resize(true));
}

// # # #  #  #  # External Calendars Dialog # # #  #  #  #
JCEC.prototype.CreateExternalDialog = function()
{
	var CD = new this.BXECDialogCore({
		obj : this,
		name : 'EXTERNAL_CAL',
		id : 'bxec_cdav_' + this.id,
		close_id: this.id + '_cdav_close',
		cancel_id: this.id + '_cdav_cancel',
		shadow_id: 'bxec_cdav_' + this.id + '_shadow',
		bEscClose: true,
		width: 500,
		height: 370,
		zIndex: 550
	});

	var _this = this;

	CD.pList = BX(this.id + '_bxec_dav_list');
	CD.pEditConDiv = BX(this.id + '_bxec_dav_new');
	CD.pEditName = BX(this.id + '_bxec_dav_name');
	CD.pEditLink = BX(this.id + '_bxec_dav_link');
	CD.pUserName = BX(this.id + '_bxec_dav_username');
	CD.pPass = BX(this.id + '_bxec_dav_password');

	BX(this.id + '_add_new').onclick = function()
	{
		var i = CD.arConnections.length;
		CD.arConnections.push({bNew: true, name: EC_MESS.NewExCalendar, link: '', user_name: ''});
		_this.ExD_DisplayConnection(CD.arConnections[i], i);
		_this.ExD_EditConnection(i);
	};

	this.oExternalDialog = CD;

	// Save button
	BX(this.id + '_cdav_save').onclick = function()
	{
		if (CD.curEditedConInd !== false && CD.arConnections[CD.curEditedConInd])
			_this.ExD_SaveConnectionData(CD.curEditedConInd);

		_this.arConnections = CD.arConnections;
		_this.SaveConnections(function(res){
			if (res)
			{
				CD.Close();
				window.location = window.location;
			}
			else
			{

			}
		});
	};
}

JCEC.prototype.ShowExternalDialog = function()
{
	//ExternalDialog
	if (!this.oExternalDialog)
		this.CreateExternalDialog();

	if (!this.oExternalDialog.PreShow()) // Dialog opened
		return;

	this.oExternalDialog.curEditedConInd = false;

	var
		CD = this.oExternalDialog,
		_this = this,
		i, l = this.arConnections.length, con, pConDiv;

	BX.cleanNode(CD.pList);
	CD.arConnections = BX.clone(this.arConnections);
	for (i = 0; i < l; i++)
		this.ExD_DisplayConnection(CD.arConnections[i], i);

	if (l == 0) // No connections - open form to add new connection
	{
		i = CD.arConnections.length;
		CD.arConnections.push({bNew: true, name: EC_MESS.NewExCalendar, link: '', user_name: ''});
		this.ExD_DisplayConnection(CD.arConnections[i], i);
		this.ExD_EditConnection(i);
	}

	this.oExternalDialog.Show(this.oExternalDialog.Resize(true));
};

JCEC.prototype.ExD_EditConnection = function(ind)
{
	var
		_this = this,
		CD = this.oExternalDialog,
		con = CD.arConnections[ind];

	if (con.del || CD.curEditedConInd === ind)
		return;

	if (CD.curEditedConInd !== false && CD.arConnections[CD.curEditedConInd])
	{
		this.ExD_SaveConnectionData(CD.curEditedConInd);
		BX.removeClass(CD.arConnections[CD.curEditedConInd].pConDiv, "bxec-dav-item-edited");
	}

	CD.curEditedConInd = ind;

	CD.pEditName.value = con.name;
	CD.pEditLink.value = con.link;
	CD.pUserName.value = con.user_name;

	if (con.id > 0)
		this.ExD_CheckPass();
	else
		CD.pPass.value = '';

	setTimeout(function(){BX.focus(CD.pEditLink);}, 100);

	CD.pEditName.onkeyup = CD.pEditName.onfocus = CD.pEditName.onblur = function()
	{
		if (CD.changeNameTimeout)
			clearTimeout(CD.changeNameTimeout);

		CD.changeNameTimeout = setTimeout(function(){
			if (CD.curEditedConInd !== false && CD.arConnections[CD.curEditedConInd])
			{
				var val = CD.pEditName.value;
				if (val.length > 25)
					val = val.substr(0, 23) + "...";
				CD.arConnections[CD.curEditedConInd].pText.innerHTML = BX.util.htmlspecialchars(val);
				CD.arConnections[CD.curEditedConInd].pText.title = CD.pEditName.value;
			}
		}, 50);
	};

	con.pConDiv.appendChild(CD.pEditConDiv);
	BX.addClass(con.pConDiv, "bxec-dav-item-edited");
};

JCEC.prototype.ExD_DisplayConnection = function(con, ind)
{
	var
		_this = this,
		CD = this.oExternalDialog,
		pConDiv = CD.pList.appendChild(BX.create("DIV", {props: {id: 'bxec_dav_con_' + ind, className: 'bxec-dav-item' + (ind % 2 == 0 ? '' : ' bxec-dav-item-1')}})),
		pTitle = pConDiv.appendChild(BX.create("DIV", {props: {className: 'bxec-dav-item-name'}})),
		pStatus = pTitle.appendChild(BX.create("IMG", {props: {src: "/bitrix/images/1.gif", className: 'bxec-dav-item-status'}})),
		pText = pTitle.appendChild(BX.create("SPAN", {text: con.name})),
		pCount = pTitle.appendChild(BX.create("SPAN", {text: ''})),
		pEdit = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-edit'}, text: EC_MESS.CalDavEdit})),
		pCol = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-col'}, text: EC_MESS.CalDavCollapse})),
		pDel = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-del'}, text: EC_MESS.CalDavDel})),
		pRest = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-rest'}, text: EC_MESS.CalDavRestore})),
		pDelCalendars = pTitle.appendChild(BX.create("DIV", {props: {className: 'bxec-dav-del-cal'}})),
		pDelCalLable = pDelCalendars.appendChild(BX.create("LABEL", {props: {htmlFor: 'bxec_dav_con_del_cal_' + ind}, text: EC_MESS.DelConCalendars})),
		pDelCalCh = pDelCalendars.appendChild(BX.create("INPUT", {props: {type: 'checkbox', id: 'bxec_dav_con_del_cal_' + ind, checked: true}}));

	if (con.id > 0)
	{
		var cn = 'bxec-dav-item-status', title;
		if (con.last_result.indexOf("[200]") >= 0)
		{
			cn += ' bxec-dav-ok';
			title = EC_MESS.SyncOk + '. ' + EC_MESS.SyncDate + ': ' + con.sync_date;
		}
		else
		{
			cn += ' bxec-dav-error';
			title = EC_MESS.SyncError + ': ' + con.last_result + '. '+ EC_MESS.SyncDate + ': ' + con.sync_date;
		}
		pStatus.className = cn;
		pStatus.title = title;

		var i, l = this.arCalendars.length, count = 0;
		for (i = 0; i < l; i++)
		{
			if (this.arCalendars[i] && this.arCalendars[i].CALDAV_CON == con.id)
				count++;
		}

		pCount.innerHTML = " (" + count + ")";
		if (count > 0)
		{

		}
	}

	pConDiv.onmouseover = function(){BX.addClass(this, "bxec-dav-item-over");};
	pConDiv.onmouseout = function(){BX.removeClass(this, "bxec-dav-item-over");};

	pConDiv.onclick = function()
	{
		ind = parseInt(this.id.substr('bxec_dav_con_'.length));
		_this.ExD_EditConnection(ind);
	};

	pCol.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (CD.arConnections[ind])
		{
			_this.ExD_SaveConnectionData(ind);
			BX.removeClass(CD.arConnections[ind].pConDiv, "bxec-dav-item-edited");
			_this.oExternalDialog.curEditedConInd = false;
		}
		return BX.PreventDefault(e);
	}

	pDel.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (CD.arConnections[ind])
		{
			CD.arConnections[ind].del = true;
			BX.removeClass(CD.arConnections[ind].pConDiv, "bxec-dav-item-edited");
			BX.addClass(CD.arConnections[ind].pConDiv, "bxec-dav-item-deleted");
			_this.ExD_SaveConnectionData(ind);
			_this.oExternalDialog.curEditedConInd = false;
		}

		return BX.PreventDefault(e);
	}

	pRest.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (CD.arConnections[ind])
		{
			CD.arConnections[ind].del = false;
			BX.removeClass(CD.arConnections[ind].pConDiv, "bxec-dav-item-deleted");
		}
		return BX.PreventDefault(e);
	}

	con.pConDiv = pConDiv;
	con.pText = pText;
	con.pDelCalendars = pDelCalCh;
}

JCEC.prototype.ExD_SaveConnectionData = function(ind)
{
	var
		CD = this.oExternalDialog,
		con = CD.arConnections[ind];

	con.name = CD.pEditName.value;
	con.link = CD.pEditLink.value;
	con.user_name = CD.pUserName.value;
	con.pass = 'bxec_not_modify_pass';

	if (CD.pPass.type.toLowerCase() == 'password' && CD.pPass.title != EC_MESS.CalDavNoChange)
		con.pass = CD.pPass.value;
}

JCEC.prototype.ExD_CheckPass = function()
{
	var CD = this.oExternalDialog;

	if (!BX.browser.IsIE())
	{
		CD.pPass.type = 'text';
		CD.pPass.value = EC_MESS.CalDavNoChange;
	}
	else
	{
		CD.pPass.value = '';
	}

	CD.pPass.title = EC_MESS.CalDavNoChange;
	CD.pPass.className = 'bxec-dav-no-change';
	CD.pPass.onfocus = CD.pPass.onmousedown = function()
	{
		if (!BX.browser.IsIE())
			this.type = 'password';
		this.value = '';
		this.title = '';
		this.className = '';
		this.onfocus = this.onmousedown = null;
		BX.focus(this);
	};
}


// # # #  #  #  # External Calendars Dialog # # #  #  #  #
JCEC.prototype.CreateMobileHelpDialog = function()
{
	var D = new this.BXECDialogCore({
		obj : this,
		name : 'MOBILE_HELP',
		id : 'bxec_mobile_' + this.id,
		close_id: this.id + '_mobile_close',
		cancel_id: this.id + '_mobile_cancel',
		shadow_id: 'bxec_mobile_' + this.id + '_shadow',
		bEscClose: true,
		width: 550,
		height: 350,
		zIndex: 550
	});

	var _this = this;

	D.iPhoneLink = BX('bxec_mob_link_iphone_' + this.id);
	D.birdLink = BX('bxec_mob_link_bird_' + this.id);

	D.iPhoneAllCont = BX('bxec_mobile_iphone_all' + this.id);
	D.iPhoneOneCont = BX('bxec_mobile_iphone_one' + this.id);
	D.birdAllCont = BX('bxec_mobile_sunbird_all' + this.id);
	D.birdOneCont = BX('bxec_mobile_sunbird_one' + this.id);

	D.iPhoneLink.onclick = function()
	{
		if (D.calendarId == 'all')
		{
			if (D.biPhoneAllOpened)
			{
				D.iPhoneAllCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.iPhoneAllCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.biPhoneAllOpened = !D.biPhoneAllOpened;
		}
		else
		{
			if (D.biPhoneOneOpened)
			{
				D.iPhoneOneCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.iPhoneOneCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.biPhoneOneOpened = !D.biPhoneOneOpened;
		}
	};

	D.birdLink.onclick = function()
	{
		if (D.calendarId == 'all')
		{
			if (D.bbirdAllOpened)
			{
				D.birdAllCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.birdAllCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.bbirdAllOpened = !D.bbirdAllOpened;
		}
		else
		{
			if (D.bbirdOneOpened)
			{
				D.birdOneCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.birdOneCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.bbirdOneOpened = !D.bbirdOneOpened;
		}
	};

	this.oMobileDialog = D;
}

JCEC.prototype.ShowMobileHelpDialog = function(calendarId)
{
	//ExternalDialog
	if (!this.oMobileDialog)
		this.CreateMobileHelpDialog();

	if (!this.oMobileDialog.PreShow()) // Dialog opened
		return;

	var D = this.oMobileDialog;
	D.calendarId = calendarId;
	D.iPhoneAllCont.style.display = "none";
	D.iPhoneOneCont.style.display = "none";
	D.birdAllCont.style.display = "none";
	D.birdOneCont.style.display = "none";

	BX.addClass(D.birdLink, 'bxec-link-hidden');
	BX.addClass(D.iPhoneLink, 'bxec-link-hidden');

	var arLinks = [];
	if (calendarId == 'all')
	{
		arLinks = arLinks.concat(BX.findChildren(D.iPhoneAllCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		arLinks = arLinks.concat(BX.findChildren(D.birdAllCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		for (var i = 0; i < arLinks.length; i++)
			if (arLinks[i] && arLinks[i].nodeName)
				arLinks[i].innerHTML = this.arConfig.caldav_link_all;
	}
	else
	{
		arLinks = arLinks.concat(BX.findChildren(D.iPhoneOneCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		arLinks = arLinks.concat(BX.findChildren(D.birdOneCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		for (var i = 0; i < arLinks.length; i++)
			if (arLinks[i] && arLinks[i].nodeName)
				arLinks[i].innerHTML = this.arConfig.caldav_link_one.replace('#CALENDAR_ID#', calendarId);
	}

	this.oMobileDialog.Show(this.oMobileDialog.Resize(true));
};
