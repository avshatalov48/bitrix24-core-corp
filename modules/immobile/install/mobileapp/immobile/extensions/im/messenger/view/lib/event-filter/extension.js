/**
 * @module im/messenger/view/lib/event-filter
 */
jn.define('im/messenger/view/lib/event-filter', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('view--event-filter');

	/**
	 * @class EventFilter
	 */
	class EventFilter
	{
		constructor()
		{
			this.eventFilters = {
				[EventFilterType.selectMessagesMode]: false,
			};
		}

		/**
		 * @param {string} eventFilter
		 */
		activateEventFilter(eventFilter)
		{
			logger.log(`${this.constructor.name} activateEventFilter:`, eventFilter);
			this.eventFilters[eventFilter] = true;
		}

		/**
		 * @param {string} eventFilter
		 */
		deactivateEventFilter(eventFilter)
		{
			logger.log(`${this.constructor.name} deactivateEventFilter:`, eventFilter);
			this.eventFilters[eventFilter] = false;
		}

		/**
		 * @return {Array<string>}
		 */
		getActiveEventFilters()
		{
			return Object.keys(this.eventFilters).filter((filterName) => this.eventFilters[filterName] === true);
		}

		/**
		 * @param {string} eventName
		 * @param {AvailableEventCollection} availableEventCollection
		 * @return {boolean}
		 */
		canEmitEvent(eventName, availableEventCollection)
		{
			const activeEventFilters = this.getActiveEventFilters();
			if (activeEventFilters.length === 0)
			{
				return true;
			}

			for (const filter of activeEventFilters)
			{
				if (!availableEventCollection[filter]?.includes(eventName))
				{
					logger.info(`${this.constructor.name}.canEmitEvent: event - ${eventName} is not called for event filter - ${filter}`);

					return false;
				}
			}

			return true;
		}
	}

	module.exports = {
		EventFilter,
	};
});
