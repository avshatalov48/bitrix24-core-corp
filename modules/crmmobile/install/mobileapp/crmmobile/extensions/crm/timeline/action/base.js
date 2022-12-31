/**
 * @module crm/timeline/action/base
 */
jn.define('crm/timeline/action/base', (require, exports, module) => {

	/**
	 * @abstract
	 * @class BaseTimelineAction
	 */
	class BaseTimelineAction
	{
		/**
		 * @param {any} value
		 * @param {any} actionParams
		 * @param {TimelineItemBase} source
		 * @param {TimelineEntityProps} entity
		 * @param {TimelineAction} factory
		 */
		constructor({ value, actionParams, source, entity, factory })
		{
			this.value = value;
			this.actionParams = actionParams;

			/** @type TimelineItemBase */
			this.source = source;

			/** @type {TimelineEntityProps} */
			this.entity = entity;

			/** @type {typeof TimelineAction} */
			this.factory = factory;
		}

		/**
		 * @abstract
		 */
		execute() {}
	}

	module.exports = { BaseTimelineAction };

});