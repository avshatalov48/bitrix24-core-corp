/**
 * @module crm/timeline/scheduler/providers/meeting
 */
jn.define('crm/timeline/scheduler/providers/meeting', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');

	/**
	 * @class TimelineSchedulerMeetingProvider
	 */
	class TimelineSchedulerMeetingProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'meeting';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MEETING_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MEETING_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.49039 9.39817L13.7185 14.8819C13.9037 15.0262 14.0131 15.2558 14.0131 15.4999C14.0131 15.7441 13.9037 15.9737 13.7185 16.118L8.49039 21.6018C8.27139 21.7724 7.98076 21.7978 7.73862 21.6672C7.49647 21.5366 7.34382 21.2725 7.34382 20.9838V17.8989H4.47065C4.07256 17.8989 3.75 17.5558 3.75 17.1326V13.8673C3.75 13.4441 4.07266 13.1011 4.47065 13.1011H7.34382V10.0162C7.34382 9.72747 7.49647 9.46331 7.73862 9.33282C7.98076 9.20223 8.27129 9.22759 8.49039 9.39817ZM20.2596 21.6018L15.0315 16.1181C14.8463 15.9738 14.7369 15.7442 14.7369 15.5001C14.7369 15.2559 14.8463 15.0263 15.0315 14.882L20.2596 9.39817C20.4786 9.22759 20.7692 9.20223 21.0114 9.33282C21.2535 9.46342 21.4062 9.72747 21.4062 10.0162V13.1011H24.2794C24.6774 13.1011 25 13.4442 25 13.8674V17.1327C25 17.5559 24.6773 17.8989 24.2794 17.8989H21.4062V20.9838C21.4062 21.2725 21.2535 21.5367 21.0114 21.6672C20.7692 21.7978 20.4787 21.7724 20.2596 21.6018Z" fill="${AppTheme.colors.base3}"/></svg>`;
		}

		static isSupported(context = {})
		{
			return false;
		}
	}

	module.exports = { TimelineSchedulerMeetingProvider };
});
