/**
 * @module crm/timeline/scheduler/providers/receive-payment
 */
jn.define('crm/timeline/scheduler/providers/receive-payment', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { ModeSelectionMenu } = require('crm/receive-payment/mode-selection');
	const { get } = require('utils/object');
	const { Icon } = require('assets/icons');
	const { TypeId } = require('crm/type');

	/**
	 * @class TimelineSchedulerReceivePaymentProvider
	 */
	class TimelineSchedulerReceivePaymentProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'receive-payment';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_PAYMENT_MENU_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_PAYMENT_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_PAYMENT_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return Icon.PAYMENT_TERMINAL;
		}

		static getDefaultPosition()
		{
			return 6;
		}

		static isSupported(context = {})
		{
			return true;
		}

		static isAvailableInMenu(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}

			const detailCardParams = context.detailCard.getComponentParams();

			return get(detailCardParams, 'entityTypeId', 0) === TypeId.Deal;
		}

		static open(data)
		{
			const { context } = data;

			const menu = new ModeSelectionMenu({
				entityModel: context.detailCard.entityModel,
				uid: context.detailCard.uid,
			});
			menu.open();
		}
	}

	module.exports = { TimelineSchedulerReceivePaymentProvider };
});
