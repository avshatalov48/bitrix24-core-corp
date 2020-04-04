var filterResponsiblePopup;

function clearUser(e) {
	if(!e) e = window.event;

	BX.findPreviousSibling(this, {tagName : "input"}).value = "";
	var parent = this.parentNode.parentNode;
	var input = BX.findNextSibling(parent, {tagName : "input"})
	window[input.name.replace("F_", "O_FILTER_")].unselect(input.value);
	input.value = "0";
	BX.addClass(parent, "webform-field-textbox-empty");

	BX.PreventDefault(e);
}

var tasksReportDefaultTemplateInit = function() {

	BX.bind(BX("filter-date-interval-calendar-from"), "click", function(e) {
		if (!e) e = window.event;

		var curDate = new Date();
		var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
		var nodeId = this;
		//jsCal endar. Show(this, "F_DATE_FROM", "F_DATE_FROM", "task-filter-form", true, curTimestamp, '', false);
		BX.calendar({
			node: nodeId, 
			field: "F_DATE_FROM", 
			form: "task-filter-form", 
			bTime: true, 
			currentTime: curTimestamp, 
			bHideTimebar: false,
			callback: function() {
				BX.removeClass(nodeId.parentNode.parentNode, "webform-field-textbox-empty");
			}
		});

		BX.PreventDefault(e);
	});

	BX.bind(BX("filter-date-interval-calendar-to"), "click", function(e) {
		if (!e) e = window.event;

		var curDate = new Date();
		var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
		var nodeId = this;
		//jsCal endar. Show(this, "F_DATE_TO", "F_DATE_TO", "task-filter-form", true, curTimestamp, '', false);
		BX.calendar({
			node: nodeId, 
			field: "F_DATE_TO", 
			form: "task-filter-form", 
			bTime: true, 
			currentTime: curTimestamp, 
			bHideTimebar: false,
			callback: function() {
				BX.removeClass(nodeId.parentNode.parentNode, "webform-field-textbox-empty");
			}
		});

		BX.PreventDefault(e);
	});

	BX.bind(BX("filter-field-employee"), "click", function(e) {

		if(!e) e = window.event;

		filterResponsiblePopup = BX.PopupWindowManager.create("filter-responsible-employee-popup", this.parentNode, {
			offsetTop : 1,
			autoHide : true,
			content : BX("FILTER_RESPONSIBLE_ID_selector_content")
		});

		filterResponsiblePopup.show();

		BX.addCustomEvent(filterResponsiblePopup, "onPopupClose", onFilterResponsibleClose);

		this.value = "";
		BX.focus(this);

		BX.PreventDefault(e);
	});
	BX.bind(BX.findNextSibling(BX("filter-field-employee"), {tagName : "a"}), "click", clearUser);

	BX.bind(BX("filter-field-group"), "click", function(e) {

		if(!e) e = window.event;

		groupsPopup.show();

		BX.PreventDefault(e);
	});
	BX.bind(BX.findNextSibling(BX("filter-field-group"), {tagName : "a"}), "click", function(e){
		if(!e) e = window.event;

		var parent = this.parentNode.parentNode;
		var input = BX.findNextSibling(parent, {tagName : "input"})
		groupsPopup.deselect(input.value);
		input.value = "0";
		BX.addClass(parent, "webform-field-textbox-empty");

		BX.PreventDefault(e);
	});
};

function SortTable(url, e)
{
	if(!e) e = window.event;
	window.location = url;
	BX.PreventDefault(e);
}

function onFilterResponsibleSelect(arUser)
{
	document.forms["task-filter-form"]["F_RESPONSIBLE_ID"].value = arUser.id;

	BX.removeClass(BX("filter-field-employee").parentNode.parentNode, "webform-field-textbox-empty");

	filterResponsiblePopup.close();
}

function onFilterGroupSelect(arGroups)
{
	if (arGroups[0])
	{
		document.forms["task-filter-form"]["F_GROUP_ID"].value = arGroups[0].id;

		BX.removeClass(BX("filter-field-group").parentNode.parentNode, "webform-field-textbox-empty");
	}
}

function onFilterResponsibleClose()
{
	var emp = O_FILTER_RESPONSIBLE_ID.arSelected.pop();
	if (emp)
	{
		O_FILTER_RESPONSIBLE_ID.arSelected.push(emp);
		O_FILTER_RESPONSIBLE_ID.searchInput.value = emp.name;
	}
}