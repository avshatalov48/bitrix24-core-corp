import { sendData } from 'ui.analytics';
import { AnalyticsParams } from "./analytics-params";

export class Analytics
{
	constructor()
	{}

	static sendFromCode(code, additionalParams = {})
	{
		switch (code)
		{
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

	static leftMenuAddButton()
	{
		sendData({
			tool: AnalyticsParams.TOOL_TASKS,
			category: AnalyticsParams.CATEGORY_TASKS,
			event: AnalyticsParams.EVENT_TASK_CREATE,
			type: AnalyticsParams.TYPE_TASK,
			c_section: AnalyticsParams.SECTION_LEFT_MENU,
			c_element: AnalyticsParams.ELEMENT_CREATE_BUTTON,
		});
	}

	static widgetAddButton()
	{
		sendData({
			tool: AnalyticsParams.TOOL_TASKS,
			category: AnalyticsParams.CATEGORY_TASKS,
			event: AnalyticsParams.EVENT_TASK_CREATE,
			type: AnalyticsParams.TYPE_TASK,
			c_section: AnalyticsParams.SECTION_WIDGET_MENU,
			c_element: AnalyticsParams.ELEMENT_CREATE_BUTTON,
		});
	}

	static verticalMenuAdd()
	{
		sendData({
			tool: AnalyticsParams.TOOL_TASKS,
			category: AnalyticsParams.CATEGORY_TASKS,
			event: AnalyticsParams.EVENT_TASK_CREATE,
			type: AnalyticsParams.TYPE_TASK,
			c_section: AnalyticsParams.SECTION_VERTICAL_MENU,
			c_element: AnalyticsParams.ELEMENT_CREATE_BUTTON,
		});
	}

	static crmTaskView(subSection)
	{
		sendData({
			tool: AnalyticsParams.TOOL_TASKS,
			category: AnalyticsParams.CATEGORY_TASKS,
			event: AnalyticsParams.EVENT_TASK_VIEW,
			type: AnalyticsParams.TYPE_TASK,
			c_section: AnalyticsParams.SECTION_CRM,
			c_element: AnalyticsParams.ELEMENT_TITLE_CLICK,
			c_sub_section: subSection || AnalyticsParams.SUB_SECTION_TASK_CRM_DEAL,
		});
	}

	static ganttContextView(section)
	{
		sendData({
			tool: AnalyticsParams.TOOL_TASKS,
			category: AnalyticsParams.CATEGORY_TASKS,
			event: AnalyticsParams.EVENT_TASK_VIEW,
			type: AnalyticsParams.TYPE_TASK,
			c_section: section || AnalyticsParams.SECTION_TASKS,
			c_element: AnalyticsParams.ELEMENT_CONTEXT_MENU,
			c_sub_section: AnalyticsParams.SUB_SECTION_TASK_GANTT,
		});
	}

	static ganttContextAdd(section)
	{
		sendData({
			tool: AnalyticsParams.TOOL_TASKS,
			category: AnalyticsParams.CATEGORY_TASKS,
			event: AnalyticsParams.EVENT_TASK_CREATE,
			type: AnalyticsParams.TYPE_TASK,
			c_section: section || AnalyticsParams.SECTION_TASKS,
			c_element: AnalyticsParams.ELEMENT_CONTEXT_MENU,
			c_sub_section: AnalyticsParams.SUB_SECTION_TASK_GANTT,
		});
	}
}