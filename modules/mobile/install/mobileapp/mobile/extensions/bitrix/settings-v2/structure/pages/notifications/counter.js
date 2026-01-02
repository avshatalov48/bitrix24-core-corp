/**
 * @module settings-v2/structure/pages/notifications/counter
 */
jn.define('settings-v2/structure/pages/notifications/counter', (require, exports, module) => {
	const {
		createSection,
		createToggle,
		createDescription,
		createImage,
	} = require('settings-v2/structure/src/item-create-helper');
	const { NotificationCounterSettingController } = require('settings-v2/controller/notification-counter');
	const { NotificationLoadService } = require('settings-v2/services/notification-load');

	const { SettingsPageId, NotificationCounterKey } = require('settings-v2/const');
	const { Loc } = require('loc');

	const requestSettingsData = async () => {
		const cachedCounterTypes = Application.storage.get(NotificationCounterKey.TYPES);
		const cachedCounterConfig = Application.storage.get(NotificationCounterKey.CONFIG);
		if (cachedCounterTypes && cachedCounterConfig)
		{
			return cachedCounterTypes;
		}

		const data = await NotificationLoadService.fetchCounterSettings();

		return data.counterTypes;
	};

	const prepareItems = (countersData) => {
		if (!countersData)
		{
			return [];
		}

		const items = countersData.map((counter) => {
			return createToggle({
				id: `notifications-counter-${counter.type}`,
				title: counter.name,
				controller: new NotificationCounterSettingController({
					counterType: counter.type,
					settingId: `notifications-counter-${counter.type}`,
				}),
			});
		});

		items[items.length - 1].divider = false;

		items.push(createDescription({
			id: 'notifications-counter-description',
			text: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_COUNTER_DESCRIPTION'),
		}));

		return items;
	};

	/** @type SettingPage */
	const NotificationsCounterPage = {
		id: SettingsPageId.NOTIFICATIONS_COUNTER,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_COUNTER_TITLE'),
		requestSettingsData,
		items: [
			createImage({
				id: 'notifications-counter-image',
				name: 'notifications-counter',
				externalStyle: {
					height: 246,
				},
			}),
			createSection(
				{
					id: 'notifications-counter-section',
					items: [],
					prepareItems,
				},
			),
		],
	};

	module.exports = {
		NotificationsCounterPage,
	};
});
