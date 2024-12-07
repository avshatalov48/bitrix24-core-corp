/**
 * @module crm/timeline/scheduler/providers/mail
 */
jn.define('crm/timeline/scheduler/providers/mail', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { MailOpener } = require('crm/mail/opener');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { Type } = require('crm/type');

	/**
	 * @class TimelineSchedulerMailProvider
	 */
	class TimelineSchedulerMailProvider extends TimelineSchedulerBaseProvider
	{
		static open({ scheduler, context = {} })
		{
			const { entity: { id, typeId } } = scheduler;
			const typeName = Type.resolveNameById(typeId);

			MailOpener.openSend({
				owner: {
					ownerId: id,
					ownerType: typeName,
				},
			});
		}

		static getId()
		{
			return 'email';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MAIL_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MAIL_MENU_FULL_TITLE');
		}

		/**
		 * @abstract
		 * @return {string|null}
		 */
		static getMenuSubtitle()
		{
			if (MailOpener.isActiveMail())
			{
				return null;
			}

			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MAIL_MENU_DISABLED');
		}

		/**
		 * @abstract
		 * @return {string|null}
		 */
		static getMenuSubtitleType()
		{
			if (MailOpener.isActiveMail())
			{
				return null;
			}

			return 'warning';
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MAIL_MENU_TITLE_MSGVER_1');
		}

		static getMenuIcon()
		{
			return Icon.MAIL;
		}

		static getDefaultPosition()
		{
			return 7;
		}

		static isSupported(context = {})
		{
			return true;
		}
	}

	module.exports = { TimelineSchedulerMailProvider };
});
