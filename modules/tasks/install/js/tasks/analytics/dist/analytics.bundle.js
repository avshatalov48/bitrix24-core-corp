/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_analytics) {
	'use strict';

	class AnalyticsParams {}
	AnalyticsParams.ACTION_LEFT_MENU_ADD = 'left_menu_add';
	AnalyticsParams.ACTION_WIDGET_ADD_BUTTON = 'widget_add_button';
	AnalyticsParams.ACTION_VERTICAL_MENU_ADD = 'vertical_menu_add';
	AnalyticsParams.ACTION_CRM_TASK_VIEW = 'crm_task_view';
	AnalyticsParams.ACTION_GANTT_CONTEXT_VIEW = 'gantt_context_view';
	AnalyticsParams.ACTION_GANTT_CONTEXT_ADD = 'gantt_context_add';
	AnalyticsParams.TOOL_TASKS = 'tasks';
	AnalyticsParams.CATEGORY_TASKS = 'task_operations';
	AnalyticsParams.CATEGORY_COMMENTS = 'comments_operations';
	AnalyticsParams.EVENT_TASK_CREATE = 'task_create';
	AnalyticsParams.EVENT_TASK_VIEW = 'task_view';
	AnalyticsParams.EVENT_TASK_COMPLETE = 'task_complete';
	AnalyticsParams.EVENT_COMMENT_ADD = 'comment_add';
	AnalyticsParams.EVENT_STATUS_SUMMARY_ADD = 'status_summary_add';
	AnalyticsParams.EVENT_SUBTASK_ADD = 'subtask_add';
	AnalyticsParams.EVENT_OVERDUE_COUNTERS_ON = 'overdue_counter_on';
	AnalyticsParams.COMMENTS_COUNTER_ON = 'comments_counter_on';
	AnalyticsParams.TYPE_TASK = 'task';
	AnalyticsParams.TYPE_COMMENT = 'comment';
	AnalyticsParams.SECTION_TASKS = 'tasks';
	AnalyticsParams.SECTION_PROJECT = 'project';
	AnalyticsParams.SECTION_SCRUM = 'scrum';
	AnalyticsParams.SECTION_FEED = 'feed';
	AnalyticsParams.SECTION_CHAT = 'chat';
	AnalyticsParams.SECTION_MAIL = 'mail';
	AnalyticsParams.SECTION_CRM = 'crm';
	AnalyticsParams.SECTION_CALENDAR = 'calendar';
	AnalyticsParams.SECTION_LEFT_MENU = 'left_menu';
	AnalyticsParams.SECTION_WIDGET_MENU = 'widget_menu';
	AnalyticsParams.SECTION_VERTICAL_MENU = 'vertical_menu';
	AnalyticsParams.SUB_SECTION_TASK_LIST = 'list';
	AnalyticsParams.SUB_SECTION_TASK_KANBAN = 'kanban';
	AnalyticsParams.SUB_SECTION_TASK_DEADLINE = 'deadline';
	AnalyticsParams.SUB_SECTION_TASK_PLANNER = 'planner';
	AnalyticsParams.SUB_SECTION_TASK_CALENDAR = 'calendar';
	AnalyticsParams.SUB_SECTION_TASK_GANTT = 'gantt';
	AnalyticsParams.SUB_SECTION_TASK_CRM_DEAL = 'deal';
	AnalyticsParams.SUB_SECTION_TASK_CRM_LEAD = 'lead';
	AnalyticsParams.SUB_SECTION_TASK_CRM_CONTACT = 'contact';
	AnalyticsParams.SUB_SECTION_TASK_CRM_COMPANY = 'company';
	AnalyticsParams.ELEMENT_CREATE_BUTTON = 'create_button';
	AnalyticsParams.ELEMENT_VIEW_BUTTON = 'view_button';
	AnalyticsParams.ELEMENT_QUICK_BUTTON = 'quick_button';
	AnalyticsParams.ELEMENT_TITLE_CLICK = 'title_click';
	AnalyticsParams.ELEMENT_COMPLETE_BUTTON = 'complete_button';
	AnalyticsParams.ELEMENT_CONTEXT_MENU = 'context_menu';

	class Analytics {
	  constructor() {}
	  static sendFromCode(code, additionalParams = {}) {
	    switch (code) {
	      case AnalyticsParams.ACTION_LEFT_MENU_ADD:
	        return Analytics.leftMenuAddButton();
	      case AnalyticsParams.ACTION_WIDGET_ADD_BUTTON:
	        return Analytics.widgetAddButton();
	      case AnalyticsParams.ACTION_VERTICAL_MENU_ADD:
	        return Analytics.verticalMenuAdd();
	      case AnalyticsParams.ACTION_CRM_TASK_VIEW:
	        return Analytics.crmTaskView(additionalParams.subSection);
	      case AnalyticsParams.ACTION_GANTT_CONTEXT_VIEW:
	        return Analytics.ganttContextView(additionalParams.section);
	      case AnalyticsParams.ACTION_GANTT_CONTEXT_ADD:
	        return Analytics.ganttContextAdd(additionalParams.section);
	    }
	  }
	  static leftMenuAddButton() {
	    ui_analytics.sendData({
	      tool: AnalyticsParams.TOOL_TASKS,
	      category: AnalyticsParams.CATEGORY_TASKS,
	      event: AnalyticsParams.EVENT_TASK_CREATE,
	      type: AnalyticsParams.TYPE_TASK,
	      c_section: AnalyticsParams.SECTION_LEFT_MENU,
	      c_element: AnalyticsParams.ELEMENT_CREATE_BUTTON
	    });
	  }
	  static widgetAddButton() {
	    ui_analytics.sendData({
	      tool: AnalyticsParams.TOOL_TASKS,
	      category: AnalyticsParams.CATEGORY_TASKS,
	      event: AnalyticsParams.EVENT_TASK_CREATE,
	      type: AnalyticsParams.TYPE_TASK,
	      c_section: AnalyticsParams.SECTION_WIDGET_MENU,
	      c_element: AnalyticsParams.ELEMENT_CREATE_BUTTON
	    });
	  }
	  static verticalMenuAdd() {
	    ui_analytics.sendData({
	      tool: AnalyticsParams.TOOL_TASKS,
	      category: AnalyticsParams.CATEGORY_TASKS,
	      event: AnalyticsParams.EVENT_TASK_CREATE,
	      type: AnalyticsParams.TYPE_TASK,
	      c_section: AnalyticsParams.SECTION_VERTICAL_MENU,
	      c_element: AnalyticsParams.ELEMENT_CREATE_BUTTON
	    });
	  }
	  static crmTaskView(subSection) {
	    ui_analytics.sendData({
	      tool: AnalyticsParams.TOOL_TASKS,
	      category: AnalyticsParams.CATEGORY_TASKS,
	      event: AnalyticsParams.EVENT_TASK_VIEW,
	      type: AnalyticsParams.TYPE_TASK,
	      c_section: AnalyticsParams.SECTION_CRM,
	      c_element: AnalyticsParams.ELEMENT_TITLE_CLICK,
	      c_sub_section: subSection || AnalyticsParams.SUB_SECTION_TASK_CRM_DEAL
	    });
	  }
	  static ganttContextView(section) {
	    ui_analytics.sendData({
	      tool: AnalyticsParams.TOOL_TASKS,
	      category: AnalyticsParams.CATEGORY_TASKS,
	      event: AnalyticsParams.EVENT_TASK_VIEW,
	      type: AnalyticsParams.TYPE_TASK,
	      c_section: section || AnalyticsParams.SECTION_TASKS,
	      c_element: AnalyticsParams.ELEMENT_CONTEXT_MENU,
	      c_sub_section: AnalyticsParams.SUB_SECTION_TASK_GANTT
	    });
	  }
	  static ganttContextAdd(section) {
	    ui_analytics.sendData({
	      tool: AnalyticsParams.TOOL_TASKS,
	      category: AnalyticsParams.CATEGORY_TASKS,
	      event: AnalyticsParams.EVENT_TASK_CREATE,
	      type: AnalyticsParams.TYPE_TASK,
	      c_section: section || AnalyticsParams.SECTION_TASKS,
	      c_element: AnalyticsParams.ELEMENT_CONTEXT_MENU,
	      c_sub_section: AnalyticsParams.SUB_SECTION_TASK_GANTT
	    });
	  }
	}

	exports.Analytics = Analytics;
	exports.AnalyticsParams = AnalyticsParams;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.UI.Analytics));
//# sourceMappingURL=analytics.bundle.js.map
