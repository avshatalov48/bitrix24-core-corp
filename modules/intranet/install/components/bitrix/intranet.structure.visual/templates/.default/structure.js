;(function(){

if (BX.IntranetVS)
	return;

/******************************** structure class *****************************/

var struct = null,
	sections_stack = [],
	registered_blocks = {},
	_onresize = function()
	{
		this.style.width = '0px';
		this.offsetWidth;

		var windowSize = BX.GetWindowScrollSize();
		this.style.width = windowSize.scrollWidth + "px";
	};


BX.IntranetVS = function(node, config)
{
	if (!!struct)
		struct.Clear();

	struct = this;

	this.node = node;
	this.config = config;

	if (!!this.config.EDIT)
		jsDD.Reset();

	this.mirror = null;
	this.lastDragPos = [0,0];

	this.reloadCallback = BX.proxy(this._reloadCallback, this);

	BX.ready(BX.proxy(this.Init, this));
};

BX.IntranetVS.get = function()
{
	return struct;
};

BX.IntranetVS.prototype.Init = function()
{
	this.node = BX(this.node);

	if (!!this.config.EDIT)
		jsDD.setScrollWindow(this.node);

	if (!!this.config.UNDO)
		this.setUndo();
};

BX.IntranetVS.prototype._reloadCallback = function(data)
{
	BX.closeWait();
	jsDD.Enable();

	if (BX.util.trim(data).substring(0, 1) == '{')
	{
		data = BX.parseJSON(data);
		if (data.error)
		{
			alert(data.error);
		}
	}
	else
	{
		var opened_sections_ids_stack = [];
		while (sections_stack.length > 0)
		{
			current_sect = sections_stack.pop();
			opened_sections_ids_stack.push(current_sect.section_id);
			current_sect.closeNext(true);
		}

		this.node.innerHTML = data;


		if (opened_sections_ids_stack.length > 0)
		{
			var f = function() {
				var sect_id = opened_sections_ids_stack.pop();
				if (sect_id > 0 && registered_blocks[sect_id])
				{
					setTimeout(function(){
						registered_blocks[sect_id].showNext(f);
					}, 50);
				}
			};

			f();
		}
	}
};

BX.IntranetVS.prototype.setUndo = function()
{
	this.config.UNDO.CONT = BX(this.config.UNDO.CONT);
	this.config.UNDO.CONT_POS = BX.pos(this.config.UNDO.CONT);
	if (this.config.UNDO.CONT)
	{
		this._undoCheckScroll();
		BX.bind(window, 'scroll', BX.proxy(this._undoCheckScroll, this));
	}
};

BX.IntranetVS.prototype._undoCheckScroll = function()
{
	var scrollPos = BX.GetWindowScrollPos(),
		nodePos = BX.pos(this.node);

	if (scrollPos.scrollTop > nodePos.top - 10)
		BX.addClass(this.config.UNDO.CONT, 'structure-undo-scroll');
	else
		BX.removeClass(this.config.UNDO.CONT, 'structure-undo-scroll');
};

BX.IntranetVS.prototype.ondragstart = function(new_mirror)
{
	if (this.mirror && this.mirror.parentNode)
		this.mirror.parentNode.removeChild(this.mirror);

	if (this.employeesPopup)
		this.employeesPopup.close();

	this.mirror = new_mirror;
};

BX.IntranetVS.prototype.ondrag = function(x, y)
{
	this.lastDragPos = [x,y];
};

BX.IntranetVS.prototype.Undo = function(undo_id)
{
	jsDD.Disable();
	BX.showWait(this.config.UNDO.CONT);
	BX.ajax.get(this.config.URL, {
		sessid: BX.bitrix_sessid(),
		action: 'undo',
		undo: undo_id
	}, this.reloadCallback);
};

BX.IntranetVS.prototype.CloseUndo = function(undo_id)
{
	if (this.config.UNDO && this.config.UNDO.CONT)
	{
		BX.cleanNode(this.config.UNDO.CONT, true);
	}

	BX.unbind(window, 'scroll', BX.proxy(this._undoCheckScroll, this));
};

BX.IntranetVS.prototype.Reload = function()
{
	jsDD.Disable();
	BX.showWait(this.node);
	BX.ajax.get(this.config.URL, {
		sessid: BX.bitrix_sessid(),
		mode: 'reload'
	}, this.reloadCallback);
};

BX.IntranetVS.prototype.Clear = function()
{
	this.CloseUndo();
};

/******************************** structure block class *****************************/


BX.IntranetVSBlock = function(params)
{
	this.bEdit = !!params.bEdit;

	this.section_id = params.section_id;
	this.section_level = params.section_level;
	this.section_parent = params.section_parent;

	this.node = params.node;
	this.head = params.head;
	this.employees = params.employees;

	this.hasChildren = params.hasChildren;
	this.disableDrag = params.disableDrag;
	this.disableDragDest = params.disableDragDest;

	this.baseZindex = 1000;

	this.tplParts = {};
	this.employees_index = {};

	for (var i = 0; i < this.employees.length; i++)
	{
		this.employees_index[this.employees[i].ID] = this.employees[i];
	}

	registered_blocks[this.section_id] = this;

	BX.template(this.node, BX.proxy(this.Init, this), false);
};

BX.IntranetVSBlock.prototype.Init = function(tplParts)
{
	var emp;

	this.node = BX.proxy_context;
	this.tplParts = tplParts;

	this.node.__bxdepartment = this;

	this.config = BX.IntranetVS.get().config;
	this.bEdit = !!this.config.EDIT;

	if (this.bEdit)
	{
		this.DDRegister();
	}

	if (tplParts.department_employee_images)
	{
		emp = tplParts.department_employee_images.firstChild;
		while (!!emp)
		{
			if (BX.type.isElementNode(emp))
			{
				if (this.bEdit)
				{
					BX.IntranetVSUser.register(emp, this.employees_index[emp.getAttribute('data-user')]);
				}
			}

			emp = emp.nextSibling;
		}
	}

	if (!!tplParts.department_head && !!this.head)
	{
		if (this.bEdit)
		{
			BX.IntranetVSUser.register(tplParts.department_head, this.employees_index[this.head]);
		}
	}

	if (this.bEdit)
	{
		if (tplParts.department_edit)
			BX.bind(tplParts.department_edit, 'click', BX.delegate(this.editDepartment, this));
		if (tplParts.department_delete)
			BX.bind(tplParts.department_delete, 'click', BX.delegate(this.deleteDepartment, this));
		if (tplParts.department_add)
			BX.bind(tplParts.department_add, 'click', BX.delegate(this.addDepartment, this));
	}

	if (this.hasChildren && this.tplParts.department_next_link)
	{
		BX.bind(this.tplParts.department_next_link, 'click', BX.proxy(this.showNext, this));
		BX.hoverEvents(this.tplParts.department_next_link);
	}

	if (this.tplParts.department_employee_count)
	{
		BX.bind(this.tplParts.department_employee_count, 'click', BX.proxy(this.showEmployees, this));
	}
};

BX.IntranetVSBlock.prototype.showEmployees = function()
{
	if (!this.employeesPopup)
	{
		var data = BX.create('DIV', {props: {className: 'structure-dept-emp-popup'}});

		for (var i=0; i < this.employees.length; i++)
		{
			var obEmployee = BX.create('DIV', {
				props: {className: 'structure-boss-block'},
				attrs: {
					'title': BX.util.htmlspecialcharsback(this.employees[i].NAME),
					'data-user': this.employees[i].ID,
					'data-dpt': this.section_id,
					'bx-tooltip-user-id': (!!this.config.USE_USER_LINK ? this.employees[i].ID : ''),
					'bx-tooltip-classname': 'intrantet-user-selector-tooltip'
				},

				html: '<a class="ui-icon ui-icon-common-user structure-avatar" href="'+this.employees[i].PROFILE+'"><i ' + (this.employees[i].PHOTO ? ' style="background: url(\''+this.employees[i].PHOTO+'\') no-repeat scroll center center transparent; background-size: cover;"' : '') + '></i></a><div class="structure-employee-name"><a href="'+this.employees[i].PROFILE+'">' + this.employees[i].NAME + '</a></div>' + (this.employees[i].POSITION ? '<div class="structure-employee-post">'+BX.util.htmlspecialchars(this.employees[i].POSITION)+'</div>' : '')
			});

			if (this.bEdit)
			{
				BX.IntranetVSUser.register(obEmployee, this.employees[i]);
			}

			if (this.employees[i].ID == this.head)
			{
				obEmployee.className += ' bx-popup-head';
				if (data.firstChild)
				{
					data.insertBefore(obEmployee, data.firstChild);
					continue;
				}
			}

			data.appendChild(obEmployee);
		}

		this.employeesPopup = new BX.PopupWindow('vis_emp_' + Math.random(), BX.proxy_context, {
			closeByEsc: true,
			autoHide: true,
			lightShadow: true,
			zIndex: this.baseZindex,
			content: data,
			offsetLeft: 50,
			angle : true
		});
	}

	BX.IntranetVS.get().employeesPopup = this.employeesPopup;
	this.employeesPopup.show();
};

BX.IntranetVSBlock.prototype.editDepartment = function(e)
{
	BX.IntranetStructure.ShowForm({UF_DEPARTMENT_ID:this.section_id});
	return e.preventDefault();
};

BX.IntranetVSBlock.prototype.addDepartment = function(e)
{
	BX.IntranetStructure.ShowForm({IBLOCK_SECTION_ID:this.section_id});
	return e.preventDefault();
};

BX.IntranetVSBlock.prototype.deleteDepartment = function(e)
{
	if (confirm(BX.message('confirm_delete_department')))
	{
		jsDD.Disable();
		BX.showWait(this.node);
		BX.ajax.get(this.config.URL, {
			sessid: BX.bitrix_sessid(),
			action: 'delete_department',
			dpt_id: this.section_id
		}, BX.IntranetVS.get().reloadCallback);
	}
	return e.preventDefault();
};

/********************** open subdepartments **************************/


BX.IntranetVSBlock.prototype.showNext = function(cb)
{
	if (!!this.tplParts.department_next_link)
	{
		var url_params = {
			mode: 'subtree',
			section: this.section_id,
			level: this.section_level
		};

		if (this.config.HAS_MULTIPLE_ROOTS)
			url_params.mr = 'Y';

		BX.showWait(this.node);

		if (BX.type.isFunction(cb))
			this._showNextCallback = cb;

		BX.ajax.get(this.config.URL, url_params, BX.proxy(this._showNext, this));
	}
};

BX.IntranetVSBlock.prototype._showNext = function (data) {
		BX.closeWait(this.node);
		var obPos = BX.pos(this.node);

		if (this.clone || obPos.left == 0)
			return;

		this.clone = BX.create('DIV', {
			style: {
				height: obPos.height + 'px',
				width: obPos.width + 'px'
			}
		});

		this.node.parentNode.replaceChild(this.clone, this.node);

		BX.addClass(this.node, 'bx-current');
		BX.removeClass(this.node, 'structure-dept-nesting');

		this.node.style.zIndex = this.baseZindex + 30 * this.section_level;

		this.node.style.top = obPos.top + 'px';
		this.node.style.left = obPos.left + 'px';

		document.body.appendChild(this.node);

		var windowSize = BX.GetWindowScrollSize();

		this.overlay = document.body.appendChild(BX.create('DIV', {
			style: {
				position: 'absolute',
				top: '0px',
				left: '0px',
				width: windowSize.scrollWidth + "px",
				height: windowSize.scrollHeight + "px",
				zIndex: this.baseZindex + 25 * this.section_level
			}
		}));
		BX.bind(window, "resize", BX.proxy(_onresize, this.overlay));

		this.shadow = document.body.appendChild(BX.create('DIV', {
			props: {className: 'bx-str-result'},
			style: {
				position: 'absolute',
				top: (obPos.top - 20) + 'px',
				zIndex: this.baseZindex + 28 * this.section_level,
				padding: (obPos.height + 30) + "px 100px 20px 20px"
			},
			html: data
		}));

		setTimeout(BX.proxy(_onresize, this.overlay), 0);

		var obPosSelf = BX.pos(this.shadow);
		var left = parseInt(obPos.left + (obPos.right-obPos.left)/2 - (obPosSelf.right-obPosSelf.left)/2);
		if (left < 0) left = 20;
		if (left > obPos.left)
			left = obPos.left-30;

		this.shadow.style.left = left + 'px';

		this.stick = document.body.appendChild(BX.create('DIV', {
			props: {className: 'bx-str-stick'},
			style: {
				zIndex: this.baseZindex + 29 * this.section_level,
				top: (obPos.bottom - 3) + 'px',
				left: parseInt((obPos.right+obPos.left)/2) + 'px'
			}
		}));


		obPosSelf = BX.pos(this.shadow);

		if (obPosSelf.right < obPos.right + 10)
		{
			this.shadow.style.width = (obPos.right + 10 - left) + 'px';
		}

		this.shadow.innerHTML += '<div class="bx-dark"><div class="bx-dark-close"></div></div>';

		sections_stack.push(this);

		this.shadow.lastChild.onclick = /*this.clone.onclick = */this.overlay.onclick = BX.proxy(this.closeNext,this);

		BX.bind(this.overlay, 'mouseover', BX.delegate(function(e)
		{
			if (this.bEdit && jsDD.bStarted)
			{
				this.HIDE_TIMEOUT = setTimeout(BX.proxy(this.closeNext, this), 500);
			}
			return BX.PreventDefault(e);
		}, this));

		BX.bind(this.shadow, 'mouseover', BX.delegate(function(e)
		{
			if (this.HIDE_TIMEOUT)
			{
				clearTimeout(this.HIDE_TIMEOUT);
				this.HIDE_TIMEOUT = null;
			}
			return BX.PreventDefault(e);
		}, this));

		if (BX.type.isFunction(this._showNextCallback))
			this._showNextCallback.apply(this);
};

BX.IntranetVSBlock.prototype.closeNext = function(bSkipPop)
{
	if (bSkipPop !== true)
		sections_stack.pop();

	BX.removeClass(this.node, 'bx-current');
	BX.addClass(this.node, 'structure-dept-nesting');
	this.node.style.zIndex = '';
	this.node.style.top = '';
	this.node.style.left = '';

	if (this.clone && this.clone.parentNode)
		this.clone.parentNode.replaceChild(this.node, this.clone);

	BX.cleanNode(this.shadow, true);
	BX.cleanNode(this.clone, true);

	if (null != this.overlay)
	{
		BX.unbind(window, "resize", BX.proxy(_onresize, this.overlay));
		BX.cleanNode(this.overlay, true);
	}

	if (null != this.stick)
		BX.cleanNode(this.stick, true);

	this.clone = null;

	if (this.bEdit)
		setTimeout(function() {jsDD.refreshDestArea()}, 100);
};

/********************************* drag'n'drop handlers ******************************/



BX.IntranetVSBlock.prototype.DDRegister = function()
{
	if (this.bEdit && this.node)
	{
		jsDD.registerDest(this.node, 1000 - this.section_level * 30);

		this.node.onbxdestdragfinish = BX.proxy(this.ddAction, this);
		this.node.onbxdestdraghover = BX.proxy(this.ddHover, this);
		this.node.onbxdestdraghout = BX.proxy(this.ddHout, this);

		if (!this.disableDrag)
		{
			jsDD.registerObject(this.node);
			this.node.onbxdrag = BX.delegate(this.ddDrag, this);
			this.node.onbxdragstart = BX.delegate(this.ddStart, this);
			this.node.onbxdragstop = BX.delegate(this.ddFinish, this);
		}
	}
};

BX.IntranetVSBlock.prototype.ddAction = function(node, x, y, e)
{
	e = e || window.event;
	BX.proxy_context.onbxdestdraghout();

	if (!!node.__bxemployee)
		BX.proxy(this._ddActionEmployee, this).apply(BX.proxy_context, arguments);
	else
		BX.proxy(this._ddActionDepartment, this).apply(BX.proxy_context, arguments);

	return true;
};

BX.IntranetVSBlock.prototype._ddActionEmployee = function(node, x, y, e)
{
	if (!this.bEdit)
		return false;

	var employee_id = node.getAttribute('data-user'),
		orig_department = node.getAttribute('data-dpt');

	if (this.section_id != orig_department || this.bxheadareaover || employee_id == this.head)
	{
		var type = (e||window.event).shiftKey ? 1 : 0;

		if (this.bxheadareaover)
		{
			if (!!this.config.SKIP_CONFIRM || confirm(
				BX.message('confirm_set_head').replace('#EMP_NAME#', node.__bxemployee.NAME).replace('#DPT_NAME#', BX.util.trim(this.tplParts.department_name.innerText||this.tplParts.department_name.textContent))
			))
			{
				jsDD.Disable();
				BX.showWait(this.node);
				BX.ajax.get(this.config.URL, {
					sessid: BX.bitrix_sessid(),
					action: 'set_department_head',
					user_id: employee_id,
					dpt_id: this.section_id,
					dpt_from: orig_department,
					type: type
				}, BX.IntranetVS.get().reloadCallback);
			}
		}
		else
		{
			if (this.config.SKIP_CONFIRM || confirm(
					BX.message('confirm_change_department_' + type)
						.replace('#EMP_NAME#', BX.util.trim(node.__bxemployee.NAME))
						.replace('#DPT_NAME#', BX.util.trim(this.tplParts.department_name.innerText||this.tplParts.department_name.textContent))
				))
			{
				jsDD.Disable();
				BX.showWait(this.node);
				BX.ajax.get(this.config.URL, {
					sessid: BX.bitrix_sessid(),
					action: 'change_department',
					user_id: employee_id,
					dpt_id: this.section_id,
					dpt_from: orig_department,
					type: type
				}, BX.IntranetVS.get().reloadCallback);
			}
		}
	}
};

BX.IntranetVSBlock.prototype._ddActionDepartment = function(node, x, y, e)
{
	if (!this.bEdit || this.disableDragDest)
		return false;

	var dpt = node.__bxdepartment,
		titleNode = dpt.tplParts.department_name,
		titleNodeTo = this.tplParts.department_name;

	if (dpt != this)
	{
		if (!!this.config.SKIP_CONFIRM || confirm(
			BX.message('confirm_move_department').replace('#DPT_NAME#', BX.util.trim(titleNode.innerText||titleNode.textContent)).replace('#DPT_NAME_TO#', BX.util.trim(titleNodeTo.innerText||titleNodeTo.textContent))
		))
		{
			jsDD.Disable();
			BX.showWait(this.node);
			BX.ajax.get(this.config.URL, {
				sessid: BX.bitrix_sessid(),
				action: 'move_department',
				dpt_id: dpt.section_id,
				dpt_to: this.section_id
			}, BX.IntranetVS.get().reloadCallback);
		}
	}
};

BX.IntranetVSBlock.prototype.ddHover = function(el)
{
	if (!!el.__bxemployee)
	{
		if (el.getAttribute('data-dpt') != this.section_id || el.getAttribute('data-user') == this.head)
		{
			BX.addClass(this.node, 'structure-add-employee');
		}
	}
	else if (el != this.node)
	{
		BX.addClass(this.node, 'structure-add-dept');
	}

	if (BX.browser.IsIE() || BX.browser.IsIE11())
	{
		this.nextLinkPos = null;
		this.headAreaPos = null;

		if (!!this.tplParts.department_next_link)
		{
			this.nextLinkPos = BX.pos(this.tplParts.department_next_link);
		}

		if (!!this.tplParts.department_head && !!el.__bxemployee && el.getAttribute('data-user') != this.head)
		{
			this.headAreaPos = BX.pos(this.tplParts.department_head);
		}

		if (this.headAreaPos || this.nextLinkPos)
		{
			BX.bind(document, 'mousemove', BX.proxy(this.__ie_mouseover_check, this));
		}
	}
	else
	{
		if (!!this.tplParts.department_next_link)
		{
			BX.bind(this.tplParts.department_next_link, 'mouseout', BX.proxy(this.__nextlink_hout, this));
			BX.bind(this.tplParts.department_next_link, 'mouseover', BX.proxy(this.__nextlink_hover, this));
		}

		if (!!this.tplParts.department_head && !!el.__bxemployee && el.getAttribute('data-user') != this.head)
		{
			BX.bind(this.tplParts.department_head, 'mouseover', BX.proxy(this.__headarea_hover, this));
			BX.bind(this.tplParts.department_head, 'mouseout',BX.proxy(this.__headarea_hout, this));
		}
	}
};

BX.IntranetVSBlock.prototype.ddHout = function()
{
	BX.removeClass(this.node, 'structure-add-employee');
	BX.removeClass(this.node, 'structure-add-dept');
	BX.removeClass(this.node, 'structure-designate-boss');

	if (!!this.tplParts.department_next_link)
	{
		setTimeout(BX.delegate(function() {
			BX.unbind(this.tplParts.department_next_link, 'mouseover', BX.proxy(this.__nextlink_hover, this));
			BX.unbind(this.tplParts.department_next_link, 'mouseout', BX.proxy(this.__nextlink_hout, this));
		}, this), 10);
	}

	if (!!this.tplParts.department_head)
	{
		BX.unbind(this.tplParts.department_head, 'mouseover', BX.proxy(this.__headarea_hover, this));
		BX.unbind(this.tplParts.department_head, 'mouseout',BX.proxy(this.__headarea_hout, this));
	}

	BX.unbind(document, 'mousemove', BX.proxy(this.__ie_mouseover_check, this));
};

BX.IntranetVSBlock.prototype.ddStart = function()
{
	BX.UI.Tooltip && BX.UI.Tooltip.disable();

	var pos = BX.pos(this.node);

	BX.addClass(this.node, 'structure-move-dept');

	this.mirror = document.body.appendChild(BX.clone(this.node));
	BX.adjust(this.mirror, {
		style: {
			position: 'absolute',
			top: pos.top + 'px',
			left: pos.left + 'px',
			zIndex: 2500
		}
	});

	BX.IntranetVS.get().ondragstart(this.mirror);
	if (this.employeesPopup)
		this.employeesPopup.close();
};

BX.IntranetVSBlock.prototype.ddDrag = function(x, y)
{
	if (!!this.mirror)
	{
		this.mirror.style.left = Math.min(x + 5, jsDD.wndSize.scrollWidth - this.mirror.offsetWidth - 5) + 'px';
		this.mirror.style.top = Math.min(y + 5, jsDD.wndSize.scrollHeight - this.mirror.offsetHeight - 5) + 'px';
	}
};

BX.IntranetVSBlock.prototype.ddFinish = function()
{
	if (!!this.mirror && !!this.mirror.parentNode)
		this.mirror.parentNode.removeChild(this.mirror);

	this.mirror = null;

	BX.removeClass(this.node, 'structure-move-dept');

	BX.UI.Tooltip && BX.UI.Tooltip.enable();
};

BX.IntranetVSBlock.prototype.__nextlink_hover = function()
{
	this.nextlinkover = true;
	if (this.BXHOVERTIMER)
		clearTimeout(this.BXHOVERTIMER);

	this.BXHOVERTIMER = setTimeout(BX.proxy(this.showNext, this), 500);
};

BX.IntranetVSBlock.prototype.__nextlink_hout = function()
{
	this.nextlinkover = false;
	if (this.BXHOVERTIMER)
	{
		clearTimeout(this.BXHOVERTIMER);
		this.BXHOVERTIMER = null;
	}
};

BX.IntranetVSBlock.prototype.__headarea_hover = function()
{
	this.bxheadareaover = true;
	BX.addClass(this.node, 'structure-designate-boss');
	BX.removeClass(this.node, 'structure-add-employee');
};

BX.IntranetVSBlock.prototype.__headarea_hout = function()
{
	this.bxheadareaover = false;
	BX.removeClass(this.node, 'structure-designate-boss');

	if (jsDD.current_node.getAttribute('data-dpt') != this.section_id)
	{
		BX.addClass(this.node, 'structure-add-employee');
	}
};

BX.IntranetVSBlock.prototype.__pos_check = function(pos)
{
	return (
		BX.IntranetVS.get().lastDragPos[0] >= pos.left && BX.IntranetVS.get().lastDragPos[0] <= pos.right
		&& BX.IntranetVS.get().lastDragPos[1] >= pos.top && BX.IntranetVS.get().lastDragPos[1] <= pos.bottom
	);
};

BX.IntranetVSBlock.prototype.__ie_mouseover_check = function()
{
	if (!!this.headAreaPos && this.__pos_check(this.headAreaPos))
	{
		if (!this.bxheadareaover)
			this.__headarea_hover();
	}
	else
	{
		if (this.bxheadareaover)
			this.__headarea_hout();
	}

	if (!!this.nextLinkPos && this.__pos_check(this.nextLinkPos))
	{
		if (!this.nextlinkover)
			this.__nextlink_hover();
	}
	else
	{
		if (this.nextlinkover)
			this.__nextlink_hout();
	}
};

/*********************** sorter class **********************/

BX.IntranetVSSorter = function(params)
{
	this.node = params.node;
	this.params = params;
	this.config = BX.IntranetVS.get().config;

	this.action = false;

	BX.ready(BX.delegate(this.Init, this));
};

BX.IntranetVSSorter.prototype.Init = function()
{
	this.node = BX(this.node);

	if (this.node)
	{
		this.node.onbxdestdraghover = BX.delegate(this.onbxdestdraghover, this);
		this.node.onbxdestdraghout = BX.delegate(this.onbxdestdraghout, this);
		this.node.onbxdestdragfinish = BX.delegate(this.Action, this);

		jsDD.registerDest(this.node);
	}
};

BX.IntranetVSSorter.prototype.Action = function(node, x, y, e)
{
	if (this.action)
	{
		if (this.params.parentSection != node.__bxdepartment.section_parent)
		{
			jsDD.Disable();
			BX.showWait(this.node);
			BX.ajax.get(this.config.URL, {
				sessid: BX.bitrix_sessid(),
				action: 'move_department',
				dpt_id: node.__bxdepartment.section_id,
				dpt_to: this.params.parentSection,
				dpt_before: this.params.beforeId,
				dpt_after: this.params.afterId,
				dpt_parent: this.params.parentSection
			}, BX.IntranetVS.get().reloadCallback);
		}
		else
		{
			jsDD.Disable();
			BX.showWait(this.node);
			BX.ajax.get(this.config.URL, {
				sessid: BX.bitrix_sessid(),
				action: 'sort_department',
				dpt_id: node.__bxdepartment.section_id,
				dpt_before: this.params.beforeId,
				dpt_after: this.params.afterId,
				dpt_parent: this.params.parentSection
			}, BX.IntranetVS.get().reloadCallback);
		}
	}

	this.onbxdestdraghout();
};

BX.IntranetVSSorter.prototype.onbxdestdraghover = function(el)
{
	if (
		!!el.__bxdepartment
		//&& el.__bxdepartment.section_level == this.params.depthLevel
		&& el.__bxdepartment.section_id != this.params.beforeId
		&& el.__bxdepartment.section_id != this.params.afterId
	)
	{
		this.action = true;
		BX.addClass(this.node, 'structure-sorter-visible');

		if (this.params.parentSection != el.__bxdepartment.section_parent)
		{
			var section_block = this.section_block || BX('bx_str_' + this.params.parentSection);
			if (section_block)
			{
				this.section_block = section_block;
				this.section_block.onbxdestdraghover.apply(this.section_block, arguments);
			}

		}
	}
};

BX.IntranetVSSorter.prototype.onbxdestdraghout = function(el)
{
	BX.removeClass(this.node, 'structure-sorter-visible');

	if (this.section_block)
		this.section_block.onbxdestdraghout.apply(this.section_block, arguments);

	this.action = false;
};

/*********************** employee functions namespace **********************/

BX.IntranetVSUser = {
	mirror: null,

	register: function(node, data)
	{
		if (!!node)
		{
			node.onbxdragstart = BX.IntranetVSUser.onbxdragstart;
			node.onbxdrag = BX.IntranetVSUser.onbxdrag;
			node.onbxdragstop = BX.IntranetVSUser.onbxdragfinish;

			node.__bxemployee = data;

			jsDD.registerObject(node);
		}
	},

	onbxdragstart: function()
	{
		if (!this.__bxemployee)
			return;

		BX.UI.Tooltip && BX.UI.Tooltip.disable();

		var pos = BX.pos(this);

		BX.IntranetVSUser.mirror = document.body.appendChild(BX.create('DIV', {
			props: {
				className: 'structure-move-employee'
			},
			style: {
				top: pos.top + 'px',
				left: pos.left + 'px',
				zIndex: 2500
			},
			html: '<span' + (this.__bxemployee.PHOTO ? ' style="background: url(\''+this.__bxemployee.PHOTO+'\') no-repeat scroll center center transparent; background-size: cover;"' : '') + ' class="structure-avatar"></span><div class="structure-employee-name">'+this.__bxemployee.NAME +'</div><div class="structure-employee-post">'+BX.util.htmlspecialchars(this.__bxemployee.POSITION)+'</div>'
		}));

		BX.IntranetVS.get().ondragstart(BX.IntranetVSUser.mirror);
	},

	onbxdrag: function (x, y)
	{
		BX.IntranetVS.get().ondrag(x, y);
		if (BX.IntranetVSUser.mirror)
		{
			BX.IntranetVSUser.mirror.style.left = Math.min(x + 5, jsDD.wndSize.scrollWidth
				- BX.IntranetVSUser.mirror.offsetWidth - 5) + 'px';
			BX.IntranetVSUser.mirror.style.top = Math.min(y + 5, jsDD.wndSize.scrollHeight
				- BX.IntranetVSUser.mirror.offsetHeight - 5) + 'px';
		}
	},

	onbxdragfinish: function ()
	{
		if (BX.IntranetVSUser.mirror && BX.IntranetVSUser.mirror.parentNode)
			BX.IntranetVSUser.mirror.parentNode.removeChild(BX.IntranetVSUser.mirror);

		BX.IntranetVSUser.mirror = null;

		BX.UI.Tooltip && BX.UI.Tooltip.enable();
	}
}

})();
