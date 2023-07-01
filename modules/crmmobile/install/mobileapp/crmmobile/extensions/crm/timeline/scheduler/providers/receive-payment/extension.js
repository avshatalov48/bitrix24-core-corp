/**
 * @module crm/timeline/scheduler/providers/receive-payment
 */
jn.define('crm/timeline/scheduler/providers/receive-payment', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { ModeSelectionMenu } = require('crm/receive-payment/mode-selection');
	const { get } = require('utils/object');
	const { TypeId } = require('crm/type/id');

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
			return '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.5767 11.9473C23.5165 11.4545 23.2223 11.0282 22.802 10.7705L6.25 10.7717V20.8478L6.25795 21.0348C6.35275 22.1443 7.28319 23.0151 8.41727 23.0151H21.8679L22.0333 23.0076C22.9048 22.9287 23.5881 22.2333 23.5881 21.3896L23.5875 19.5555L17.4554 19.5564L17.3096 19.548C16.688 19.4758 16.2054 18.9475 16.2054 18.3064V14.9503L16.2138 14.8046C16.286 14.1829 16.8144 13.7003 17.4554 13.7003L23.5875 13.6992L23.5881 12.1349L23.5767 11.9473ZM23.5875 18.3605V14.8955L17.4204 14.8958V18.361L23.5875 18.3605ZM19.2536 16.4695C19.2536 15.8711 19.7389 15.3858 20.3372 15.3858C20.9356 15.3858 21.4209 15.8711 21.4209 16.4695C21.4209 17.0678 20.9356 17.5531 20.3372 17.5531C19.7389 17.5531 19.2536 17.0678 19.2536 16.4695ZM21.5649 9.5775L20.4735 5.5L8.1987 8.78902L8.40995 9.5775H21.5649Z" fill="#767C87"/></svg>';
		}

		static getMenuPosition()
		{
			return 600;
		}

		static isSupported(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}
			const detailCardParams = context.detailCard.getComponentParams();
			return Boolean(get(detailCardParams, 'isAvailableReceivePayment', false));
		}

		static isAvailableInMenu(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}
			const detailCardParams = context.detailCard.getComponentParams();
			return (
				Boolean(get(detailCardParams, 'isAvailableReceivePayment', false))
				&& get(detailCardParams, 'entityTypeId', 0) === TypeId.Deal
			);
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
