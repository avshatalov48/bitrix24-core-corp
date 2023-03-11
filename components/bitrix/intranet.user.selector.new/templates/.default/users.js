;(function(){

if(window.IntranetUsers)
	return;

window.IntranetUsers = function(name, multiple, bSubordinateOnly) {
	this.name = name;
	this.multiple = multiple;
	this.arSelected = [];
	this.arFixed = [];
	this.bSubordinateOnly = bSubordinateOnly;
	this.ajaxUrl = '';
	this.lastSearchTime = 0;
}

IntranetUsers.arStructure = {};
IntranetUsers.bSectionsOnly = false;
IntranetUsers.arEmployees = { 'group' : {} };
IntranetUsers.arEmployeesData = {};
IntranetUsers.ajaxUrl = '';

IntranetUsers.prototype.loadGroup = function(groupId)
{
	var obSection = BX(this.name + '_group_section_' + groupId);
	function __onLoadEmployees(data)
	{
		IntranetUsers.arEmployees['group'][groupId] = data;
		this.show(groupId, data, 'g');
	}

	groupId = parseInt(groupId);
	if (IntranetUsers.arEmployees['group'][groupId] != null)
	{
		this.show(groupId, IntranetUsers.arEmployees['group'][groupId], 'g');
	}
	else
	{
		var url = this.getAjaxUrl() + '&MODE=EMPLOYEES&GROUP_ID=' + groupId;
		BX.ajax.loadJSON(url, BX.proxy(__onLoadEmployees, this));
	}

	BX.toggleClass(obSection, "company-department-opened");
	BX.toggleClass(BX(this.name + '_gchildren_' + groupId), "company-department-children-opened");
};

IntranetUsers.prototype.load = function(sectionID, bShowOnly, bScrollToSection, bSectionsOnly)
{
	this.bSectionsOnly = bSectionsOnly;

	function __onLoadEmployees(data)
	{
		IntranetUsers.arStructure[sectionID] = data.STRUCTURE;
		IntranetUsers.arEmployees[sectionID] = data.USERS;
		this.show(sectionID, false, '', this.bSectionsOnly);
	}

	if (null == bShowOnly) bShowOnly = false;
	if (null == bScrollToSection) bScrollToSection = false;
	if (null == bSectionsOnly) bSectionsOnly = false;

	if (sectionID != 'extranet') sectionID = parseInt(sectionID);

	var obSection = BX(this.name + '_employee_section_' + sectionID);
	if (!obSection.BX_LOADED)
	{
		if (IntranetUsers.arEmployees[sectionID] != null)
		{
			this.show(sectionID, false, '', this.bSectionsOnly);
		}
		else
		{
			var url = this.getAjaxUrl() + '&MODE=EMPLOYEES&SECTION_ID=' + sectionID;
			BX.ajax.loadJSON(url,  BX.proxy(__onLoadEmployees, this));
		}
	}

	if (bScrollToSection)
	{
		BX(this.name + '_employee_search_layout').scrollTop = obSection.offsetTop - 40;
	}

	BX.toggleClass(obSection, "company-department-opened");
	BX.toggleClass(BX(this.name + '_children_' + sectionID), "company-department-children-opened");
}

IntranetUsers.prototype.show = function (sectionID, usersData, sectionPrefixName, bSelectSection)
{
	bSelectSection = !!bSelectSection;
	sectionPrefixName = sectionPrefixName || '';
	var obSection = BX(this.name + '_' + sectionPrefixName + 'employee_section_' + sectionID);
	var arEmployees = usersData || IntranetUsers.arEmployees[sectionID];

	if(obSection !== null)
	{
		obSection.BX_LOADED = true;
	}

	var obSectionDiv = BX(this.name + '_' + sectionPrefixName + 'employees_' + sectionID);
	if (obSectionDiv)
	{
		if (IntranetUsers.arStructure[sectionID] != null && !sectionPrefixName)
		{
			var arStructure = IntranetUsers.arStructure[sectionID];

			var obSectionCh = BX(this.name + '_' + sectionPrefixName + 'children_' + sectionID);
			if (obSectionCh)
			{
				for (var i = 0; i < arStructure.length; i++)
				{
					obSectionRow1 = BX.create('div', {
						props: {className: 'company-department'},
						children: [
							(bSelectSection
								? BX.create('span', {
									props: {
										className: 'company-department-inner',
										id: this.name+'_employee_section_'+arStructure[i].ID
									},
									children: [
										BX.create('div', {
											props: {className: 'company-department-arrow'},
											attrs: {
												onclick: 'O_'+this.name+'.load('+arStructure[i].ID+', false, false, true)'
											}
										}),
										BX.create('div', {
											props: {className: 'company-department-text'},
											attrs: {
												'data-section-id' : arStructure[i].ID,
												onclick: 'O_'+this.name+'.selectSection('+this.name+'_employee_section_'+arStructure[i].ID+')'
											},
											text: arStructure[i].NAME
										})
									]
								})
								: BX.create('span', {
									props: {
										className: 'company-department-inner',
										id: this.name+'_employee_section_'+arStructure[i].ID
									},
									attrs: {
										onclick: 'O_'+this.name+'.load('+arStructure[i].ID+')'
									},
									children: [
										BX.create('div', {props: {className: 'company-department-arrow'}}),
										BX.create('div', {
											props: {className: 'company-department-text'},
											text: arStructure[i].NAME
										})
									]
								})
							)
						]
					});

					obSectionRow2 = BX.create('div', {
						props: {
							className: 'company-department-children',
							id: this.name+'_children_'+arStructure[i].ID
						},
						children: [
							BX.create('div', {
								props: {
									className: 'company-department-employees',
									id: this.name+'_employees_'+arStructure[i].ID
								},
								children: [
									BX.create('span', {
										props: {className: 'company-department-employees-loading'},
										text: BX.message('INTRANET_EMP_WAIT')
									})
								]
							})
						]
					});

					obSectionCh.appendChild(obSectionRow1);
					obSectionCh.appendChild(obSectionRow2);
				}

				obSectionCh.appendChild(obSectionDiv);
			}
		}

		obSectionDiv.innerHTML = '';

		for (var i = 0; i < arEmployees.length; i++)
		{

			var obUserRow;
			var bSelected = false;

			IntranetUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO,
				url: arEmployees[i].USER_PROFILE_URL
			};

			var obInput = BX.create("input", {
				props : {
					className : "intranet-hidden-input"
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
							background : arEmployees[i].PHOTO ? "url('" + encodeURI(arEmployees[i].PHOTO) + "') no-repeat center center" : "",
							backgroundSize: arEmployees[i].PHOTO ? "cover" : ""
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
								text : arEmployees[i].NAME
							}),
							BX.create("div", {
								props : {
									className : "company-department-employee-position"
								},
								html : !arEmployees[i].HEAD && !arEmployees[i].WORK_POSITION ? "&nbsp;" : (BX.util.htmlspecialchars(arEmployees[i].WORK_POSITION) + (arEmployees[i].HEAD && arEmployees[i].WORK_POSITION ? ', ' : '') + (arEmployees[i].HEAD ? BX.message('INTRANET_EMP_HEAD') : ''))
							})
						]
					})
				]
			})

			obSectionDiv.appendChild(obUserRow);
		}
	}
}

IntranetUsers.prototype.select = function(e)
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

		this.searchInput.value = IntranetUsers.arEmployeesData[obInput.value].name;

		this.arSelected = [];
		this.arSelected[obInput.value] = {
			id : obInput.value,
			name : IntranetUsers.arEmployeesData[obInput.value].name,
			sub : IntranetUsers.arEmployeesData[obInput.value].sub,
			sup : IntranetUsers.arEmployeesData[obInput.value].sup,
			position : IntranetUsers.arEmployeesData[obInput.value].position,
			photo : IntranetUsers.arEmployeesData[obInput.value].photo,
			url: IntranetUsers.arEmployeesData[obInput.value].url
		};
	}
	else
	{
		var arInputs = document.getElementsByName(this.name + "[]");
		if (!BX.util.in_array(obInput, arInputs) && !BX.util.in_array(obInput.value, this.arFixed)) { // IE7
			obInput.checked = false;
			BX.toggleClass(obInput.parentNode, BX.hasClass(obInput.parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
		}
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value == obInput.value && !BX.util.in_array(obInput.value, this.arFixed))
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

				obUserRow.innerHTML =  "<div class=\"finder-box-selected-item-icon\" id=\"" + this.name + "-user-selector-unselect-" + obInput.value + "\" onclick=\"O_" + this.name + ".unselect(" + obInput.value + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + obNameDiv.innerHTML + "</span>";
				obSelected.appendChild(obUserRow);

				var countSpan = BX(this.name + "_current_count");
				countSpan.innerHTML = parseInt(countSpan.innerHTML) + 1;

				this.arSelected[obInput.value] = {
					id : obInput.value,
					name : IntranetUsers.arEmployeesData[obInput.value].name,
					sub : IntranetUsers.arEmployeesData[obInput.value].sub,
					sup : IntranetUsers.arEmployeesData[obInput.value].sup,
					position : IntranetUsers.arEmployeesData[obInput.value].position,
					photo : IntranetUsers.arEmployeesData[obInput.value].photo,
					url : IntranetUsers.arEmployeesData[obInput.value].url
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

	var posInLast = BX.util.array_search(obInput.value, IntranetUsers.lastUsers);
	if (posInLast >= 0)
		IntranetUsers.lastUsers.splice(posInLast, 1);

	IntranetUsers.lastUsers.unshift(obInput.value);
	BX.userOptions.save('intranet', 'user_search', 'last_selected', IntranetUsers.lastUsers.slice(0, 10));

	if (this.onSelect)
	{
		var emp = this.arSelected.pop();
		this.arSelected.push(emp);
		this.onSelect(emp);
	}

	BX.onCustomEvent(this, 'on-change', [this.toObject(this.arSelected)]);

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

IntranetUsers.prototype.toObject = function(brokenArray)
{
	var result = {};

	for(var k in brokenArray)
	{
		k = parseInt(k);

		if(typeof k == 'number' && brokenArray[k] !== null)
		{
			result[k] = BX.clone(brokenArray[k]);
		}
	}

	return result;
}

IntranetUsers.prototype.selectSection = function(block_id)
{
	var obSectionBlock = BX(block_id);
	if (!obSectionBlock)
	{
		return false;
	}
	else
	{
		var obSectionTitleBlock = BX.findChild(obSectionBlock, {tag: "div", className: "company-department-text"});
		if (obSectionTitleBlock)
		{
			if (this.onSectionSelect)
			{
				this.onSectionSelect({
					id : obSectionTitleBlock.getAttribute('data-section-id'),
					name : obSectionTitleBlock.innerHTML
				});
			}
		}
	}
}

IntranetUsers.prototype.unselect = function(employeeID)
{
	var link = BX(this.name + "-user-selector-unselect-" + employeeID);
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

	BX.onCustomEvent(this, 'on-change', [this.toObject(this.arSelected)]);

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

IntranetUsers.prototype.getSelected = function()
{
	return this.arSelected;
}

IntranetUsers.prototype.setSelected = function(arEmployees)
{
	for(var i = 0, count = this.arSelected.length; i < count; i++)
	{
		if (this.arSelected[i] && this.arSelected[i].id)
			this.unselect(this.arSelected[i].id);
	}

	if (!this.multiple)
	{
		arEmployees = [arEmployees[0]];
	}
	this.arSelected = [];
	for(var i = 0, count = arEmployees.length; i < count; i++)
	{
		this.arSelected[arEmployees[i].id] = arEmployees[i];

		var hiddenInput = BX.create("input", {
			props: {
				className: "intranet-hidden-input",
				value: arEmployees[i].id,
				checked: "checked",
				name: this.name + (this.multiple ? "[]" : "")
			}
		});

		BX(this.name + "_last").appendChild(hiddenInput);

		if (this.multiple)
		{
			var obSelected = BX.findChild(BX(this.name + "_selected_users"), {className: "finder-box-selected-items"});
			var obUserRow = BX.create("div", {
				props: {
					className: "finder-box-selected-item",
					id: this.name + '_employee_selected_' + arEmployees[i].id
				},
				html: "<div class=\"finder-box-selected-item-icon\" id=\"" + this.name + "-user-selector-unselect-" + arEmployees[i].id + "\" onclick=\"O_" + this.name + ".unselect(" + arEmployees[i].id + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + BX.util.htmlspecialchars(arEmployees[i].name) + "</span>"
			});
			obSelected.appendChild(obUserRow);
		}

		var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
		for(var j = 0; j < arInputs.length; j++)
		{
			if (arInputs[j].value == arEmployees[i].id)
			{
				BX.toggleClass(arInputs[j].parentNode, BX.hasClass(arInputs[j].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
			}
		}
	}

	if (this.multiple)
	{
		BX.adjust(BX(this.name + "_current_count"), {text: arEmployees.length});
	}
}

IntranetUsers.prototype.setFixed = function(arEmployees)
{
	if (typeof arEmployees != 'object')
		arEmployees = [];

	this.arFixed = arEmployees;

	var obSelected = BX.findChildren(BX(this.name + '_selected_users'), {className: 'finder-box-selected-item-icon'}, true);

	for (i = 0; i < obSelected.length; i++)
	{
		var userId = obSelected[i].id.replace(this.name + '-user-selector-unselect-', '');

		BX.adjust(obSelected[i], {style: {
			visibility: BX.util.in_array(userId, this.arFixed) ? 'hidden' : 'visible'
		}});
	}
}

IntranetUsers.prototype.search = function(e)
{
	this.searchRqstTmt = clearTimeout(this.searchRqstTmt);
	if (typeof this.searchRqst == 'object')
	{
		this.searchRqst.abort();
		this.searchRqst = false;
	}

	if (!e) e = window.event;

	if (this.searchInput.value.length > 0)
	{
		this.displayTab("search");

		var url = this.getAjaxUrl() + '&MODE=SEARCH&SEARCH_STRING=' + encodeURIComponent(this.searchInput.value);
		if (this.bSubordinateOnly)
			url += "&S_ONLY=Y";
		var _this = this;
		this.searchRqstTmt = setTimeout(function() {
			var startTime = (new Date()).getTime();
			_this.lastSearchTime = startTime;
			_this.searchRqst = BX.ajax.loadJSON(url, BX.proxy(function(data) {
				if (_this.lastSearchTime == startTime)
					_this.showResults(data);
			}, _this));
		}, 400);
	}
}

IntranetUsers.prototype.showResults = function(data)
{
	var arEmployees = data;
	var obSectionDiv = BX(this.name + '_search');
	var arInputs = obSectionDiv.getElementsByTagName("input");
	var i = null;

	for(i = 0, count = arInputs.length; i < count; i++)
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

		for (i = 0; i < arEmployees.length; i++)
		{
			var obUserRow;
			var bSelected = false;
			IntranetUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO,
				url: arEmployees[i].USER_PROFILE_URL
			};

			var obInput = BX.create("input", {
				props : {
					className : "intranet-hidden-input"
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

			arInputs = document.getElementsByName(obInput.name);
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

			obUserRow = BX.create("div", {
				props : {
					className : "finder-box-item" + (bSelected ? " finder-box-item-selected" : "")
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
						attrs: {
							"bx-tooltip-user-id": arEmployees[i].ID,
							"bx-tooltip-classname": "intrantet-user-selector-tooltip"
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

IntranetUsers.prototype.displayTab = function(tab)
{
	BX.removeClass(BX(this.name + "_last"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_search"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_structure"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_groups"), "finder-box-tab-content-selected");
	BX.addClass(BX(this.name + "_" + tab), "finder-box-tab-content-selected");

	BX.removeClass(BX(this.name + "_tab_last"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_search"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_structure"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_groups"), "finder-box-tab-selected");
	BX.addClass(BX(this.name + "_tab_" + tab), "finder-box-tab-selected");
}

IntranetUsers.prototype._onFocus = function()
{
	this.searchInput.value = "";
}

IntranetUsers.prototype.getAjaxUrl = function()
{
    return this.ajaxUrl || IntranetUsers.ajaxUrl;
}


})();