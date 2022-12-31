/**
 * @module crm/timeline/scheduler/providers/mail
 */
jn.define('crm/timeline/scheduler/providers/mail', (require, exports, module) => {

	const { Loc } = require('loc');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');

	/**
	 * @class TimelineSchedulerMailProvider
	 */
	class TimelineSchedulerMailProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'mail';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MAIL_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MAIL_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 13.6972L7.38462 8H22.6154L15 13.6972Z" fill="#828B95"/><path d="M24 9.71734L15 16.947L6 9.71731V20.7662C6 21.4485 6.57975 22 7.29438 22H22.7056C23.422 22 24 21.4477 24 20.7662L24 9.71734Z" fill="#828B95"/></svg>`;
		}

		static getMenuPosition()
		{
			return 400;
		}

		static isSupported()
		{
			return false;
		}
	}

	module.exports = { TimelineSchedulerMailProvider };

});