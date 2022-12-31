/**
 * @module crm/timeline/item/ui/body/blocks/base
 */
jn.define('crm/timeline/item/ui/body/blocks/base', (require, exports, module) => {

	/**
	 * @class TimelineItemBodyBlock
	 * @abstract
	 */
	class TimelineItemBodyBlock extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {TimelineItemBodyBlockFactory} factory
		 */
		constructor(props, factory)
		{
			super(props);

			/** @type {TimelineItemBodyBlockFactory} */
			this.factory = factory;
		}

		/**
		 * @return {TimelineItemModel}
		 */
		get model()
		{
			return this.factory.model;
		}

		/**
		 * @return {boolean}
		 */
		get isReadonly()
		{
			return this.model.isReadonly;
		}

		/**
		 * @return {EventEmitter}
		 */
		get itemScopeEventBus()
		{
			return this.factory.itemScopeEventBus;
		}

		/**
		 * @return {EventEmitter}
		 */
		get timelineScopeEventBus()
		{
			return this.factory.timelineScopeEventBus;
		}

		emitAction(params)
		{
			if (this.factory.onAction && params)
			{
				this.factory.onAction(params);
			}
		}
	}

	module.exports = { TimelineItemBodyBlock };

});