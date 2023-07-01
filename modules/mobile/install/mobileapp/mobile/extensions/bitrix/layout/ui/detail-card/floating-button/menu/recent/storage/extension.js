/**
 * @module layout/ui/detail-card/floating-button/menu/recent/storage
 */
jn.define('layout/ui/detail-card/floating-button/menu/recent/storage', (require, exports, module) => {
	const { Type } = require('type');

	const STORAGE_PREFIX = 'detail-card-menu-recent-items';
	const EVENTS_STORAGE_KEY = 'events';
	const EVENTS_LIMIT = 100;

	/**
	 * @typedef {Object} MenuRecentEvent
	 * @property {String} actionId
	 * @property {?String} tabId
	 * @property {Number} timestamp
	 */

	/**
	 * @typedef {MenuRecentEvent} MenuRecentItem
	 * @property {Number} quantity
	 */

	/**
	 * @class MenuRecentStorage
	 */
	class MenuRecentStorage
	{
		constructor({ entityTypeId, categoryId })
		{
			if (Type.isNil(entityTypeId) || entityTypeId <= 0)
			{
				throw new TypeError('MenuRecentStorage: {entityTypeId} must be greater than 0.');
			}

			this.entityTypeId = entityTypeId;
			this.categoryId = categoryId;

			this.application = Application;
		}

		setApplication(application)
		{
			this.application = application;
		}

		/**
		 * @public
		 * @return {MenuRecentItem[]}
		 */
		getRankedItems()
		{
			const events = [
				...this.getEventsByEntityTypeId(),
				...this.getEventsByCategoryId(),
			];

			if (events.length === 0)
			{
				return [];
			}

			const items = this.calculateItemsByEvents(events);

			items.sort((itemA, itemB) => {
				const diff = itemB.quantity - itemA.quantity;
				if (diff === 0)
				{
					return itemA.timestamp - itemB.timestamp;
				}

				return diff;
			});

			return items;
		}

		/**
		 * Calculates events with the same actionId and tabId and sums their quantity. Also, takes the latest timestamp.
		 *
		 * @param {MenuRecentEvent[]} events
		 * @return {MenuRecentItem[]}
		 */
		calculateItemsByEvents(events)
		{
			const itemMap = new Map();

			events.forEach((event) => {
				let calculatedItem;

				const hash = this.getItemHash(event);
				if (itemMap.has(hash))
				{
					const existingItem = itemMap.get(hash);

					calculatedItem = {
						...existingItem,
						quantity: existingItem.quantity += 1,
						timestamp: Math.max(event.timestamp, existingItem.timestamp),
					};
				}
				else
				{
					calculatedItem = {
						...event,
						quantity: 1,
					};
				}

				itemMap.set(hash, calculatedItem);
			});

			return Array.from(itemMap.values());
		}

		/**
		 * @private
		 * @param {MenuRecentEvent} item
		 * @return {string}
		 */
		getItemHash(item)
		{
			return `${item.tabId || 'root'}/${item.actionId}`;
		}

		getEventsByEntityTypeId()
		{
			return this.getRecentEvents(this.getEntityTypeIdKey());
		}

		/**
		 * @private
		 * @return {string}
		 */
		getEntityTypeIdKey()
		{
			return `${STORAGE_PREFIX}_${this.entityTypeId}`;
		}

		getEventsByCategoryId()
		{
			if (Type.isNil(this.categoryId))
			{
				return [];
			}

			return this.getRecentEvents(this.getCategoryIdKey());
		}

		/**
		 * @private
		 * @return {string}
		 */
		getCategoryIdKey()
		{
			return `${this.getEntityTypeIdKey()}_by_category_${this.categoryId}`;
		}

		/**
		 * @private
		 * @param {string} key
		 * @return []
		 */
		getRecentEvents(key)
		{
			return this.getStorage(key).getObject(EVENTS_STORAGE_KEY, []);
		}

		/**
		 * @private
		 * @return {KeyValueStorage}
		 */
		getStorage(key)
		{
			return this.application.storageById(key);
		}

		/**
		 * @public
		 * @param {string} actionId
		 * @param {?string} tabId
		 */
		addEvent(actionId, tabId = null)
		{
			if (Type.isNil(actionId))
			{
				throw new TypeError('MenuRecentStorage: {actionId} must be defined.');
			}

			const event = {
				actionId,
				tabId,
				timestamp: new Date().getTime(),
			};

			this.saveEvent(this.getEntityTypeIdKey(), event);

			if (!Type.isNil(this.categoryId))
			{
				this.saveEvent(this.getCategoryIdKey(), event);
			}
		}

		/**
		 * @private
		 * @param {string} key
		 * @param {MenuRecentItem} newItem
		 */
		saveEvent(key, newItem)
		{
			const events = this.getRecentEvents(key);

			if (events.length >= EVENTS_LIMIT)
			{
				events.shift();
			}

			events.push(newItem);

			this.getStorage(key).setObject(EVENTS_STORAGE_KEY, events);
		}
	}

	module.exports = { MenuRecentStorage, EVENTS_LIMIT };
});
