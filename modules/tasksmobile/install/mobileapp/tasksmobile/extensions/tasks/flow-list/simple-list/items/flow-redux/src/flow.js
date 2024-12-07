/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { mergeImmutable } = require('utils/object');
	const { FlowContentChooserView } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-content-chooser');

	/**
	 * @class Flow
	 */
	class Flow extends Base
	{
		getStyles()
		{
			return mergeImmutable(super.getStyles(), {
				wrapper: {
					paddingBottom: 0,
					backgroundColor: this.colors.bgContentPrimary,
				},
				item: {
					position: 'relative',
					backgroundColor: this.colors.bgContentPrimary,
				},
			});
		}

		renderItemContent()
		{
			return FlowContentChooserView({
				id: this.props.item.id,
				isLast: this.props.item.isLast,
				testId: this.props.testId,
				itemLayoutOptions: this.props.itemLayoutOptions,
				type: this.props.type,
				layout: this.props.layout,
				onCloseButtonClick: this.props.onCloseButtonClick,
				analyticsLabel: this.props.analyticsLabel,
			});
		}

		blink(callback = null, showUpdated = true)
		{
			if (typeof callback === 'function')
			{
				callback();
			}
		}

		/**
		 * @public
		 * @param callback
		 */
		setLoading(callback = null)
		{
			this.blink(callback);
		}

		/**
		 * @public
		 * @param callback
		 * @param blink
		 */
		dropLoading(callback = null, blink = true)
		{
			this.blink(callback);
		}
	}

	module.exports = { Flow };
});
