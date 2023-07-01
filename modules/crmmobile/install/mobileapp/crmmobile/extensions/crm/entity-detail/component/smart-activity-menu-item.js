/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/smart-activity-menu-item
 */
jn.define('crm/entity-detail/component/smart-activity-menu-item', (require, exports, module) => {
	const { getEntityMessage } = require('crm/loc');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');

	const pathToIcons = `${currentDomain}/bitrix/mobileapp/crmmobile/components/crm/crm.entity.details/icons/`;

	const getSmartActivityMenuItem = (checked, entityTypeId) => ({
		id: 'smartActivityItem',
		sectionCode: 'action',
		onItemSelected: () => {
			if (checked)
			{
				askDisableSmartActivity(entityTypeId);
			}
			else
			{
				enableSmartActivity(entityTypeId);
			}
		},
		title: BX.message('M_CRM_ACTION_SMART_ACTIVITY2'),
		checked,
		iconUrl: `${pathToIcons}smart_activities.png`,
	});

	const askDisableSmartActivity = (entityTypeId) => {
		const periods = ['day', 'week', 'month', 'forever'];

		const actions = periods.map((period) => ({
			id: period,
			title: Loc.getMessage(`M_CRM_ACTION_SMART_ACTIVITY_SKIP_${period.toUpperCase()}`),
			onClickCallback: () => new Promise((resolve) => {
				menu.close(() => disableSmartActivity(period, entityTypeId));
				resolve({ closeMenu: false });
			}),
		}));

		const menu = new ContextMenu({
			actions,
			params: {
				title: getEntityMessage('M_CRM_ACTION_SMART_ACTIVITY_SKIP_TITLE', entityTypeId),
				showCancelButton: true,
				showActionLoader: false,
			},
		});
		void menu.show();
	};

	const disableSmartActivity = (period, entityTypeId) => {
		BX.ajax
			.runAction('crm.activity.todo.skipEntityDetailsNotification', {
				data: {
					entityTypeId,
					period,
				},
			})
			.then(() => {
				BX.postComponentEvent('Crm.Activity.Todo::onChangeNotifications', [false]);

				const title = getEntityMessage('M_CRM_ACTION_SMART_ACTIVITY_DISABLED_NOTIFY_TITLE', entityTypeId);
				const text = getEntityMessage('M_CRM_ACTION_SMART_ACTIVITY_DISABLED_NOTIFY_TEXT', entityTypeId);

				Notify.showUniqueMessage(text, title, { time: 5 });
				Haptics.impactLight();
			})
		;
	};

	const enableSmartActivity = (entityTypeId) => {
		BX.ajax
			.runAction('crm.activity.todo.skipEntityDetailsNotification', {
				data: {
					entityTypeId,
					period: '',
				},
			})
			.then(() => {
				BX.postComponentEvent('Crm.Activity.Todo::onChangeNotifications', [true]);

				const title = BX.message('M_CRM_ACTION_SMART_ACTIVITY_ENABLED_NOTIFY_TITLE');
				const text = getEntityMessage('M_CRM_ACTION_SMART_ACTIVITY_ENABLED_NOTIFY_TEXT', entityTypeId);

				Notify.showUniqueMessage(text, title, { time: 5 });
				Haptics.impactLight();
			})
		;
	};

	module.exports = { getSmartActivityMenuItem };
});
