/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/smart-activity-menu-item
 */
jn.define('crm/entity-detail/component/smart-activity-menu-item', (require, exports, module) => {

	const { TypeId } = require('crm/type');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');

	const pathToIcons = currentDomain + '/bitrix/mobileapp/crmmobile/components/crm/crm.entity.details/icons/';

	const getSmartActivityMenuItem = (checked) => ({
		id: 'smartActivityItem',
		sectionCode: 'action',
		onItemSelected: () => {
			if (checked)
			{
				askDisableSmartActivity();
			}
			else
			{
				enableSmartActivity();
			}
		},
		title: BX.message('M_CRM_ACTION_SMART_ACTIVITY2'),
		checked,
		iconUrl: pathToIcons + 'smart_activities.png',
	});

	const askDisableSmartActivity = () => {
		const periods = ['day', 'week', 'month', 'forever'];

		const actions = periods.map((period) => ({
			id: period,
			title: Loc.getMessage(`M_CRM_ACTION_SMART_ACTIVITY_SKIP_${period.toUpperCase()}`),
			onClickCallback: () => new Promise((resolve) => {
				menu.close(() => disableSmartActivity(period));
				resolve({ closeMenu: false });
			}),
		}));

		const menu = new ContextMenu({
			actions,
			params: {
				title: Loc.getMessage('M_CRM_ACTION_SMART_ACTIVITY_SKIP_TITLE'),
				showCancelButton: true,
				showActionLoader: false,
			},
		});
		void menu.show();
	};

	const disableSmartActivity = (period) => {
		BX.ajax
			.runAction('crm.activity.todo.skipEntityDetailsNotification', {
				data: {
					entityTypeId: TypeId.Deal,
					period,
				},
			})
			.then(() => {
				BX.postComponentEvent('Crm.Activity.Todo::onChangeNotifications', [false]);

				const title = BX.message('M_CRM_ACTION_SMART_ACTIVITY_DISABLED_NOTIFY_TITLE');
				const text = BX.message('M_CRM_ACTION_SMART_ACTIVITY_DISABLED_NOTIFY_TEXT');

				Notify.showUniqueMessage(text, title, { time: 5 });
				Haptics.impactLight();
			})
		;
	};

	const enableSmartActivity = () => {
		BX.ajax
			.runAction('crm.activity.todo.skipEntityDetailsNotification', {
				data: {
					entityTypeId: TypeId.Deal,
					period: '',
				},
			})
			.then(() => {
				BX.postComponentEvent('Crm.Activity.Todo::onChangeNotifications', [true]);

				const title = BX.message('M_CRM_ACTION_SMART_ACTIVITY_ENABLED_NOTIFY_TITLE');
				const text = BX.message('M_CRM_ACTION_SMART_ACTIVITY_ENABLED_NOTIFY_TEXT');

				Notify.showUniqueMessage(text, title, { time: 5 });
				Haptics.impactLight();
			})
		;
	};

	module.exports = { getSmartActivityMenuItem };
});
