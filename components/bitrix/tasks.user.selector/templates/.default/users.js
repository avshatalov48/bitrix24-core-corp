function TasksUsers(name, multiple, bSubordinateOnly) {
	this.name = name;
	this.multiple = multiple;
	this.arSelected = [];
	this.bSubordinateOnly = bSubordinateOnly;
	this.ajaxUrl = '';
}

TasksUsers.arEmployees = {};
TasksUsers.arEmployeesData = {};

TasksUsers.prototype.load = function(sectionID, bShowOnly, bScrollToSection)
{
	function __onLoadEmployees(data)
	{
		TasksUsers.arEmployees[sectionID] = data;
		this.show(sectionID);
	}

	if (null == bShowOnly) bShowOnly = false;
	if (null == bScrollToSection) bScrollToSection = false;

	if (sectionID != 'extranet') sectionID = parseInt(sectionID);

	var obSection = BX(this.name + '_employee_section_' + sectionID);
	if (!obSection.BX_LOADED)
	{
		if (TasksUsers.arEmployees[sectionID] != null)
		{
			this.show(sectionID);
		}
		else
		{
			var url = this.ajaxUrl + '&MODE=EMPLOYEES&SECTION_ID=' + sectionID;
			BX.ajax.loadJSON(url,  BX.proxy(__onLoadEmployees, this));
		}
	}

	if (bScrollToSection)
	{
		BX(this.name + '_employee_search_layout').scrollTop = obSection.offsetTop - 40;
	}

	BX.toggleClass(obSection, "company-department-opened");

	BX.toggleClass(BX(this.name + '_children_' + sectionID), "company-department-children-opened");
};

TasksUsers.prototype.show = function (sectionID)
{
	var obSection = BX(this.name + '_employee_section_' + sectionID);
	var arEmployees = TasksUsers.arEmployees[sectionID];

	obSection.BX_LOADED = true;

	var obSectionDiv = BX(this.name + '_employees_' + sectionID);
	if (obSectionDiv)
	{
		obSectionDiv.innerHTML = '';

		for (var i = 0; i < arEmployees.length; i++)
		{

			var obUserRow;
			var bSelected = false;

			TasksUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO
			};

			var obInput = BX.create("input", {
				props : {
					className : "tasks-hidden-input"
				}
			});

			if (this.multiple)
			{
				obInput.name = this.name + "[]";
				obInput.type = "checkbox";
			}
			else
			{
				obInput.name = this.name;
				obInput.type = "radio";
			}

			var arInputs = document.getElementsByName(obInput.name);
			var j = 0;
			while(!bSelected && j < arInputs.length)
			{
				if (arInputs[j].value == arEmployees[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}

			obInput.value = arEmployees[i].ID;

			obUserRow = BX.create("div", {
				props : {
					className : "company-department-employee" + (bSelected ? " company-department-employee-selected" : "")
				},
				events : {
					click : BX.proxy(this.select, this)
				},
				children : [
					obInput,
					BX.create("div", {
						props : {
							className : "company-department-employee-avatar"
						},
						style : {
							background : arEmployees[i].PHOTO ? "url('" + encodeURI(arEmployees[i].PHOTO) + "') no-repeat center center" : ""
						}
					}),
					BX.create("div", {
						props : {
							className : "company-department-employee-icon"
						}
					}),
					BX.create("div", {
						props : {
							className : "company-department-employee-info"
						},
						children : [
							BX.create("div", {
								props : {
									className : "company-department-employee-name"
								},
								text : BX.util.htmlspecialcharsback(arEmployees[i].NAME)
							}),
							BX.create("div", {
								props : {
									className : "company-department-employee-position"
								},
								html : !arEmployees[i].HEAD && !arEmployees[i].WORK_POSITION ? "&nbsp;" : (BX.util.htmlspecialchars(arEmployees[i].WORK_POSITION) + (arEmployees[i].HEAD && arEmployees[i].WORK_POSITION ? ', ' : '') + (arEmployees[i].HEAD ? BX.message('TASKS_EMP_HEAD') : ''))
							})
						]
					})
				]
			});

			obSectionDiv.appendChild(obUserRow);
		}
	}
};

TasksUsers.prototype.select = function(e)
{
	var obCurrentTarget;
	var i = 0;

	var target = e.target || e.srcElement;

	if (e.currentTarget)
	{
		obCurrentTarget = e.currentTarget;
	}
	else // because IE does not support currentTarget
	{
		obCurrentTarget = target;

		while(!BX.hasClass(obCurrentTarget, "finder-box-item") && !BX.hasClass(obCurrentTarget, "company-department-employee"))
		{
			obCurrentTarget = obCurrentTarget.parentNode;
		}
	}

	var obInput = BX.findChild(obCurrentTarget, {tag: "input"});

	if (!this.multiple)
	{
		var arInputs = document.getElementsByName(this.name);
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value != obInput.value)
			{
				BX.removeClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
			}
			else
			{
				BX.addClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
			}
		}
		obInput.checked = true;
		BX.addClass(obCurrentTarget, BX.hasClass(obCurrentTarget, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");

		var obNameDiv = BX.findChild(obCurrentTarget, {tag: "DIV", className: "finder-box-item-text"}, true) || BX.findChild(obCurrentTarget, {tag: "DIV", className: "company-department-employee-name"}, true);
		var userName = BX.util.htmlspecialcharsback(obNameDiv.innerHTML)
		this.searchInput.value = userName;

		this.arSelected = [];
		this.arSelected[obInput.value] = {
			id : obInput.value,
			name : userName,
			sub : TasksUsers.arEmployeesData[obInput.value].sub,
			sup : TasksUsers.arEmployeesData[obInput.value].sup,
			position : TasksUsers.arEmployeesData[obInput.value].position,
			photo : TasksUsers.arEmployeesData[obInput.value].photo
		};
	}
	else
	{
		var arInputs = document.getElementsByName(this.name + "[]");
		if (!BX.util.in_array(obInput, arInputs)) { // IE7
			obInput.checked = false;
			BX.toggleClass(obInput.parentNode, BX.hasClass(obInput.parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
		}
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value == obInput.value)
			{
				arInputs[i].checked = false;
				BX.toggleClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
			}
		}

		if (BX.hasClass(obInput.parentNode, "finder-box-item-selected") || BX.hasClass(obInput.parentNode, "company-department-employee-selected"))
		{
			obInput.checked = true;
		}

		if (obInput.checked)
		{

			var obSelected = BX.findChild(BX(this.name + "_selected_users"), {className: "finder-box-selected-items"});

			if (!BX(this.name + "_employee_selected_" + obInput.value))
			{
				var obUserRow = BX.create('DIV');
				obUserRow.id = this.name + '_employee_selected_' + obInput.value;
				obUserRow.className = 'finder-box-selected-item';

				var obNameDiv = BX.findChild(obCurrentTarget, {tag: "DIV", className: "finder-box-item-text"}, true) || BX.findChild(obCurrentTarget, {tag: "DIV", className: "company-department-employee-name"}, true);

				obUserRow.innerHTML =  "<div class=\"finder-box-selected-item-icon\" onclick=\"O_" + this.name + ".unselect(" + obInput.value + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + obNameDiv.innerHTML + "</span>";
				obSelected.appendChild(obUserRow);

				var countSpan = BX(this.name + "_current_count");
				countSpan.innerHTML = parseInt(countSpan.innerHTML) + 1;

				this.arSelected[obInput.value] = {
					id : obInput.value,
					name : BX.util.htmlspecialcharsback(obNameDiv.innerHTML),
					sub : TasksUsers.arEmployeesData[obInput.value].sub,
					sup : TasksUsers.arEmployeesData[obInput.value].sup,
					position : TasksUsers.arEmployeesData[obInput.value].position,
					photo : TasksUsers.arEmployeesData[obInput.value].photo
				};
			}
		}
		else
		{
			BX.remove(BX(this.name + '_employee_selected_' + obInput.value));

			var countSpan = BX(this.name + "_current_count");
			countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;

			this.arSelected[obInput.value] = null;
		}
	}

	if (!BX.util.in_array(obInput.value, TasksUsers.lastUsers))
	{
		TasksUsers.lastUsers.unshift(obInput.value);
		BX.userOptions.save('tasks', 'user_search', 'last_selected', TasksUsers.lastUsers.slice(0, 10));
	}

	if (this.onSelect)
	{
		var emp = this.arSelected.pop();
		this.arSelected.push(emp);
		this.onSelect(emp);
	}

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

TasksUsers.prototype.unselect = function(employeeID, link)
{
	var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
	for(var i = 0; i < arInputs.length; i++)
	{
		if (arInputs[i].value == employeeID)
		{
			arInputs[i].checked = false;
			BX.removeClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
		}
	}
	if (this.multiple)
	{
		if (link)
		{
			BX.remove(link.parentNode);
		}
		var countSpan = BX(this.name + "_current_count");
		countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;
	}

	this.arSelected[employeeID] = null;

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
};

TasksUsers.prototype.search = function(e)
{
	if(!e) e = window.event;

	function __onLoadEmployees(data)
	{
		this.showResults(data);
	}

	if (this.searchInput.value.length > 0)
	{
		this.displayTab("search");

		var url = this.ajaxUrl + '&MODE=SEARCH&SEARCH_STRING=' + encodeURIComponent(this.searchInput.value);
		if (this.bSubordinateOnly)
		{
			url += "&S_ONLY=Y";
		}
		BX.ajax.loadJSON(url, BX.proxy(__onLoadEmployees, this));
	}
};

TasksUsers.prototype.showResults = function(data)
{
	var arEmployees = data;
	var obSectionDiv = BX(this.name + '_search');

	var arInputs = obSectionDiv.getElementsByTagName("input");
	for(var i = 0, count = arInputs.length; i < count; i++)
	{
		if (arInputs[i].checked)
		{
			BX(this.name + '_last').appendChild(arInputs[i]);
		}
	}

	if (obSectionDiv)
	{
		obSectionDiv.innerHTML = '';

		var table = BX.create("table", {
			props : {
				className : "finder-box-tab-columns",
				cellspacing : "0"
			},
			children : [
				 BX.create("tbody")
			]
		});

		var tr = BX.create("tr");
		table.firstChild.appendChild(tr);

		var td = BX.create("td");
		tr.appendChild(td);

		obSectionDiv.appendChild(table);

		for (var i = 0; i < arEmployees.length; i++)
		{
			var obUserRow;
			var bSelected = false;
			TasksUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO
			}

			var obInput = BX.create("input", {
				props : {
					className : "tasks-hidden-input"
				}
			});

			if (this.multiple)
			{
				obInput.name = this.name + "[]";
				obInput.type = "checkbox";
			}
			else
			{
				obInput.name = this.name;
				obInput.type = "radio";
			}

			var arInputs = document.getElementsByName(obInput.name);
			var j = 0;
			while(!bSelected && j < arInputs.length)
			{
				if (arInputs[j].value == arEmployees[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}

			obInput.value = arEmployees[i].ID;

			var text = arEmployees[i].NAME;
			/*
			TODO: good look and feel
			if (arEmployees[i].WORK_POSITION.length > 0)
				text = text + ', ' + arEmployees[i].WORK_POSITION;*/

			var anchor_user_id = "finded_anchor_user_id_" + arEmployees[i].ID;

			obUserRow = BX.create("div", {
				props : {
					className : "finder-box-item" + (bSelected ? " finder-box-item-selected" : ""),
					id: anchor_user_id
				},
				attrs: {
					'bx-tooltip-user-id': arEmployees[i].ID
				},
				events : {
					click : BX.proxy(this.select, this)
				},
				children : [
					obInput,
					BX.create("div", {
						props : {
							className : "finder-box-item-text"
						},
						text : text
					}),
					BX.create("div", {
						props : {
							className : "finder-box-item-icon"
						}
					})
				]
			});

			td.appendChild(obUserRow);

			if (i == Math.ceil(arEmployees.length / 2) - 1)
			{
				td = BX.create("td");
				table.firstChild.appendChild(td);
			}
		}
	}
};

TasksUsers.prototype.displayTab = function(tab)
{
	BX.removeClass(BX(this.name + "_last"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_search"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_structure"), "finder-box-tab-content-selected");
	BX.addClass(BX(this.name + "_" + tab), "finder-box-tab-content-selected");

	BX.removeClass(BX(this.name + "_tab_last"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_search"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_structure"), "finder-box-tab-selected");
	BX.addClass(BX(this.name + "_tab_" + tab), "finder-box-tab-selected");
};

TasksUsers.prototype._onFocus = function()
{
	this.searchInput.value = "";
};