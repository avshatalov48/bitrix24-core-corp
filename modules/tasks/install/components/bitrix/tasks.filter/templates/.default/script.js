var filterResponsiblePopup, filterCreatedByPopup, filterAccomplicePopup, filterAuditorPopup;

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
var tasksFilterDefaultTemplateInit = function() {

	if (BX("filter-field-responsible"))
	{
		BX.bind(BX("filter-field-responsible"), "click", function(e) {

			if(!e) e = window.event;

			filterResponsiblePopup = BX.PopupWindowManager.create("filter-responsible-employee-popup", this.parentNode, {
				offsetTop : 1,
				autoHide : true,
				closeByEsc : true,
				content : BX("FILTER_RESPONSIBLE_selector_content")
			});

			filterResponsiblePopup.show();
			
			BX.addCustomEvent(filterResponsiblePopup, "onPopupClose", onFilterResponsibleClose);
			
			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});
		BX.bind(BX.findNextSibling(BX("filter-field-responsible"), {tagName : "a"}), "click", clearUser);
		BX.bind(BX("filter-field-director"), "click", function(e) {

			if(!e) e = window.event;

			filterCreatedByPopup = BX.PopupWindowManager.create("filter-director-employee-popup", this.parentNode, {
				offsetTop : 1,
				autoHide : true,
				closeByEsc : true,
				content : BX("FILTER_CREATED_BY_selector_content")
			});

			filterCreatedByPopup.show();
			
			BX.addCustomEvent(filterCreatedByPopup, "onPopupClose", onFilterCreatedByClose);
			
			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});
		BX.bind(BX.findNextSibling(BX("filter-field-director"), {tagName : "a"}), "click", clearUser);
		BX.bind(BX("filter-field-assistant"), "click", function(e) {

			if(!e) e = window.event;

			filterAccomplicePopup = BX.PopupWindowManager.create("filter-assistant-employee-popup", this.parentNode, {
				offsetTop : 1,
				autoHide : true,
				closeByEsc : true,
				content : BX("FILTER_ACCOMPLICE_selector_content")
			});

			filterAccomplicePopup.show();
			
			BX.addCustomEvent(filterAccomplicePopup, "onPopupClose", onFilterAccompliceClose);
			
			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});
		BX.bind(BX.findNextSibling(BX("filter-field-assistant"), {tagName : "a"}), "click", clearUser);
		BX.bind(BX("filter-field-auditor"), "click", function(e) {

			if(!e) e = window.event;

			filterAuditorPopup = BX.PopupWindowManager.create("filter-auditor-employee-popup", this.parentNode, {
				offsetTop : 1,
				autoHide : true,
				closeByEsc : true,
				content : BX("FILTER_AUDITOR_selector_content")
			});

			filterAuditorPopup.show();
			
			BX.addCustomEvent(filterAuditorPopup, "onPopupClose", onFilterAuditorClose);
			
			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});
		BX.bind(BX.findNextSibling(BX("filter-field-auditor"), {tagName : "a"}), "click", clearUser);
	
		BX.bind(BX("filter-field-group"), "click", function(e) {

			if(!e) e = window.event;

			filterGroupsPopup.show();

			BX.PreventDefault(e);
		});
		BX.bind(BX.findNextSibling(BX("filter-field-group"), {tagName : "a"}), "click", function(e){
			if(!e) e = window.event;

			var parent = this.parentNode.parentNode;
			var input = BX.findNextSibling(parent, {tagName : "input"})
			filterGroupsPopup.deselect(input.value);
			input.value = "0";
			BX.addClass(parent, "webform-field-textbox-empty");

			BX.PreventDefault(e);
		});

		BX.bind(BX("filter-date-interval-calendar-from"), "click", function(e) {
			if (!e) e = window.event;
			
			var curDate = new Date();
			var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
			//jsCal endar. Show(this, "F_DATE_FROM", "F_DATE_FROM", "task-filter-advanced-form", true, curTimestamp, '', false);
			BX.calendar({
				node: this, 
				field: "F_DATE_FROM", 
				form: "task-filter-advanced-form", 
				bTime: true, 
				currentTime: curTimestamp, 
				bHideTimebar: false
			});
	
			BX.PreventDefault(e);
		});
	
		BX.bind(BX("filter-date-interval-calendar-to"), "click", function(e) {
			if (!e) e = window.event;
			
			var curDate = new Date();
			var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
			//jsCal endar. Show(this, "F_DATE_TO", "F_DATE_TO", "task-filter-advanced-form", true, curTimestamp, '', false);
			BX.calendar({
				node: this, 
				field: "F_DATE_TO", 
				form: "task-filter-advanced-form", 
				bTime: true, 
				currentTime: curTimestamp, 
				bHideTimebar: false
			});

			BX.PreventDefault(e);
		});
		
		BX.bind(BX("filter-closed-interval-calendar-from"), "click", function(e) {
			if (!e) e = window.event;
			
			var curDate = new Date();
			var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
			//jsCal endar. Show(this, "F_CLOSED_FROM", "F_CLOSED_FROM", "task-filter-advanced-form", true, curTimestamp, '', false);
			BX.calendar({
				node: this, 
				field: "F_CLOSED_FROM", 
				form: "task-filter-advanced-form", 
				bTime: true, 
				currentTime: curTimestamp, 
				bHideTimebar: false
			});
	
			BX.PreventDefault(e);
		});
	
		BX.bind(BX("filter-active-interval-calendar-to"), "click", function(e) {
			if (!e) e = window.event;
			
			var curDate = new Date();
			var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
			//jsCal endar. Show(this, "F_ACTIVE_TO", "F_ACTIVE_TO", "task-filter-advanced-form", true, curTimestamp, '', false);
				BX.calendar({
				node: this, 
				field: "F_ACTIVE_TO", 
				form: "task-filter-advanced-form", 
				bTime: true, 
				currentTime: curTimestamp, 
				bHideTimebar: false
			});

			BX.PreventDefault(e);
		});
		
		BX.bind(BX("filter-active-interval-calendar-from"), "click", function(e) {
			if (!e) e = window.event;
			
			var curDate = new Date();
			var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
			//jsCal endar. Show(this, "F_ACTIVE_FROM", "F_ACTIVE_FROM", "task-filter-advanced-form", true, curTimestamp, '', false);
				BX.calendar({
				node: this, 
				field: "F_ACTIVE_FROM", 
				form: "task-filter-advanced-form", 
				bTime: true, 
				currentTime: curTimestamp, 
				bHideTimebar: false
			});

			BX.PreventDefault(e);
		});
	
		BX.bind(BX("filter-closed-interval-calendar-to"), "click", function(e) {
			if (!e) e = window.event;
			
			var curDate = new Date();
			var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;
			//jsCal endar. Show(this, "F_CLOSED_TO", "F_CLOSED_TO", "task-filter-advanced-form", true, curTimestamp, '', false);
			BX.calendar({
				node: this, 
				field: "F_CLOSED_TO", 
				form: "task-filter-advanced-form", 
				bTime: true, 
				currentTime: curTimestamp, 
				bHideTimebar: false
			});
	
			BX.PreventDefault(e);
		});
		
		BX.bind(BX("filter-field-tags-link"), "click", TasksShowTagsPopup);
		
		BX.addCustomEvent(tasksTagsPopUp, "onUpdateTagLine", function (e) {
			var tags = this.windowArea.getSelectedTags();
			var tagsString = "";
			for (var i = 0, length = tags.length; i < length; i++)
			{
				if (i > 0)
					tagsString += ", ";
				tagsString += tags[i].name
			}
			var tagLine = BX("task-filter-tags-line");
			BX.cleanNode(tagLine);
			BX.adjust(tagLine, {text : tagsString} );
			
			document.forms["task-filter-advanced-form"]["F_TAGS"].value = tagsString;
		});
	
	}
}

/*=====================Filter Popups===============================*/

function onFilterResponsibleSelect(arUser)
{
	document.forms["task-filter-advanced-form"]["F_RESPONSIBLE"].value = arUser.id;
	
	BX.removeClass(BX("filter-field-responsible").parentNode.parentNode, "webform-field-textbox-empty");
	
	filterResponsiblePopup.close();
}

function onFilterResponsibleClose()
{
	var emp = O_FILTER_RESPONSIBLE.arSelected.pop();
	if (emp)
	{
		O_FILTER_RESPONSIBLE.arSelected.push(emp);
		O_FILTER_RESPONSIBLE.searchInput.value = emp.name;
	}
}

function onFilterCreatedBySelect(arUser)
{
	document.forms["task-filter-advanced-form"]["F_CREATED_BY"].value = arUser.id;
	
	BX.removeClass(BX("filter-field-director").parentNode.parentNode, "webform-field-textbox-empty");
	
	filterCreatedByPopup.close();
}

function onFilterCreatedByClose()
{
	var emp = O_FILTER_CREATED_BY.arSelected.pop();
	if (emp)
	{
		O_FILTER_CREATED_BY.arSelected.push(emp);
		O_FILTER_CREATED_BY.searchInput.value = emp.name;
	}
}

function onFilterAccompliceSelect(arUser)
{
	document.forms["task-filter-advanced-form"]["F_ACCOMPLICE"].value = arUser.id;
	
	BX.removeClass(BX("filter-field-assistant").parentNode.parentNode, "webform-field-textbox-empty");
	
	filterAccomplicePopup.close();
}

function onFilterAccompliceClose()
{
	var emp = O_FILTER_ACCOMPLICE.arSelected.pop();
	if (emp)
	{
		O_FILTER_ACCOMPLICE.arSelected.push(emp);
		O_FILTER_ACCOMPLICE.searchInput.value = emp.name;
	}
}

function onFilterAuditorSelect(arUser)
{
	document.forms["task-filter-advanced-form"]["F_AUDITOR"].value = arUser.id;
	
	BX.removeClass(BX("filter-field-auditor").parentNode.parentNode, "webform-field-textbox-empty");
	
	filterAuditorPopup.close();
}

function onFilterAuditorClose()
{
	var emp = O_FILTER_AUDITOR.arSelected.pop();
	if (emp)
	{
		O_FILTER_AUDITOR.arSelected.push(emp);
		O_FILTER_AUDITOR.searchInput.value = emp.name;
	}
}

function onFilterGroupSelect(arGroups)
{
	if (arGroups[0])
	{
		document.forms["task-filter-advanced-form"]["F_GROUP_ID"].value = arGroups[0].id;

		BX.removeClass(BX("filter-field-group").parentNode.parentNode, "webform-field-textbox-empty");
	}
}

function onUpdateTagLine(e) {
	var tags = this.windowArea.getSelectedTags();
	var tagsString = "";
	for (var i = 0, length = tags.length; i < length; i++)
	{
		if (i > 0)
			tagsString += ", ";
		tagsString += tags[i].name
	}
	var tagLine = BX("task-filter-tags-line");
	BX.cleanNode(tagLine);
	BX.adjust(tagLine, {text : tagsString} );

	document.forms["task-filter-advanced-form"]["F_TAGS"].value = tagsString;
}