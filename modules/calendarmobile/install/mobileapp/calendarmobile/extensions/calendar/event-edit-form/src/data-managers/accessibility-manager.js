/**
 * @module calendar/event-edit-form/data-managers/accessibility-manager
 */
jn.define('calendar/event-edit-form/data-managers/accessibility-manager', (require, exports, module) => {
	const { DateHelper } = require('calendar/date-helper');
	const { SlotsCalculator } = require('calendar/event-edit-form/data-managers/slots-calculator');
	const { AccessibilityAjax } = require('calendar/ajax');

	class AccessibilityManager
	{
		constructor()
		{
			this.init();
		}

		init()
		{
			this.accessibility = {};
			this.slots = [];
			this.slotsKey = null;
			this.accessibilityPromises = {};
		}

		calculateSlots({ userIds, date, slotSize, skipEventId })
		{
			if (!this.hasAccessibility({ userIds, date }))
			{
				return {};
			}

			const { datesKey } = this.prepareTimestamps(date);

			const slotsKey = `${datesKey}.${userIds.sort().join(',')}.${slotSize}.${skipEventId}`;
			if (this.slotsKey !== slotsKey)
			{
				const accessibility = userIds.flatMap((userId) => this.accessibility[datesKey][userId]);

				const year = date.getFullYear();
				const month = date.getMonth();

				this.slots = SlotsCalculator.calculate({
					year,
					month,
					accessibility,
					slotSize,
					skipEventId,
				});

				this.slotsKey = slotsKey;
			}

			return this.slots;
		}

		hasAccessibility({ userIds, date })
		{
			const { datesKey } = this.prepareTimestamps(date);

			return userIds.every((userId) => this.accessibility[datesKey]?.[userId]);
		}

		async loadAccessibility({ userIds, date })
		{
			const { datesKey, timestampFrom, timestampTo } = this.prepareTimestamps(date);

			this.accessibilityPromises[datesKey] ??= {};

			const requested = new Set(Object.keys(this.accessibilityPromises[datesKey])
				.flatMap((key) => key.split(','))
				.map((id) => parseInt(id, 10)))
			;

			const notRequestedUserIds = userIds.filter((userId) => !requested.has(userId));

			if (notRequestedUserIds.length === 0)
			{
				return;
			}

			const notRequestedEntities = userIds.filter((userId) => !requested.has(userId));

			this.accessibilityPromises[datesKey][notRequestedEntities] ??= this.requestAccessibility({
				userIds: notRequestedUserIds,
				timestampFrom,
				timestampTo,
			});

			const { data } = await this.accessibilityPromises[datesKey][notRequestedEntities];

			const fullDayOffset = DateHelper.timezoneOffset;
			const accessibility = Object.keys(data).reduce((acc, entityId) => ({
				[entityId]: data[entityId].map((it) => ({
					...it,
					from: it.from * 1000 + (it.isFullDay ? fullDayOffset : 0),
					to: it.to * 1000 + (it.isFullDay ? fullDayOffset : 0),
				})),
				...acc,
			}), {});

			this.accessibility[datesKey] ??= {};
			Object.assign(this.accessibility[datesKey], accessibility);
		}

		/**
		 * @private
		 */
		prepareTimestamps(date)
		{
			const timestampFrom = new Date(date.getFullYear(), date.getMonth()).getTime();
			const timestampTo = new Date(date.getFullYear(), date.getMonth() + 1).getTime();
			const datesKey = `${timestampFrom}-${timestampTo}`;

			return { datesKey, timestampFrom, timestampTo };
		}

		/**
		 * @private
		 */
		requestAccessibility({ userIds, timestampFrom, timestampTo })
		{
			return AccessibilityAjax.get({ userIds, timestampFrom, timestampTo });
		}
	}

	module.exports = { AccessibilityManager: new AccessibilityManager() };
});
