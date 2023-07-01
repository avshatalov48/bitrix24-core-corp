/**
 * @module crm/controllers/base
 */
jn.define('crm/controllers/base', (require, exports, module) => {
	/**
	 * @abstract
	 * @class TimelineBaseController
	 */
	class TimelineBaseController
	{
		/**
		 * @param {TimelineItemBase} item
		 * @param {TimelineEntityProps} entity
		 * @param {TimelineScheduler} scheduler
		 */
		constructor(item, entity, scheduler)
		{
			/** @type TimelineItemBase */
			this.item = item;

			/** @type {TimelineEntityProps} */
			this.entity = entity;

			/** @type {TimelineScheduler} */
			this.scheduler = scheduler;
		}

		/**
		 * @abstract
		 */
		onItemAction()
		{}

		/**
		 * @abstract
		 * @return {string[]}
		 */
		static getSupportedActions()
		{
			return [];
		}

		/**
		 * @param {string} action
		 * @return {boolean}
		 */
		static isActionSupported(action)
		{
			return this.getSupportedActions().includes(action);
		}

		/**
		 * @return {EventEmitter}
		 */
		get itemScopeEventBus()
		{
			return this.item.itemScopeEventBus;
		}

		/**
		 * @return {EventEmitter}
		 */
		get timelineScopeEventBus()
		{
			return this.item.timelineScopeEventBus;
		}

		/**
		 * @private
		 * @param {string} template
		 * @param {object} data
		 */
		openDetailCardTopToolbar(template, data = {})
		{
			this.timelineScopeEventBus.emit('DetailCard::onShowTopToolbar', [template, data]);
		}
	}

	module.exports = { TimelineBaseController };
});
