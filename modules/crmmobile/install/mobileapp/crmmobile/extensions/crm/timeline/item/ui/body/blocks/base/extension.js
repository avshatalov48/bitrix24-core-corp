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

		/**
		 * @public
		 * @return {number}
		 */
		getBottomGap()
		{
			return 10;
		}

		/**
		 * @private
		 * @param {any} params
		 */
		emitAction(params)
		{
			if (this.factory.onAction && params)
			{
				this.factory.onAction(params);
			}
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

	module.exports = { TimelineItemBodyBlock };
});
