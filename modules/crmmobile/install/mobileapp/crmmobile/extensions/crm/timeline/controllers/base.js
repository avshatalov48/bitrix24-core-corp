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
		 */
		constructor(item, entity)
		{
			/** @type TimelineItemBase */
			this.item = item;

			/** @type {TimelineEntityProps} */
			this.entity = entity;
		}

		/**
		 * @abstract
		 */
		onItemAction() {}

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

		pinInTopToolbar(actionParams)
		{
			if (!this.item)
			{
				return;
			}

			this.timelineScopeEventBus.emit(
				'DetailCard::onShowTopToolbar',
				[this.item.model.props, actionParams]
			);
		}
	}

	module.exports = { TimelineBaseController };

});