/**
 * @module layout/ui/viewer-list
 */
jn.define('layout/ui/viewer-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ViewerList } = require('layout/ui/viewer-list/src/list');
	const { BottomSheet } = require('bottom-sheet');

	const DEFAULT_HEIGHT = 50;

	class ViewerListManager
	{
		/**
		 * @param {Object} props
		 * @param {String} props.entityType
		 * @param {Number} props.entityId
		 * @param {Function} [props.renderCustomDescription]
		 * @param {string} [props.title]
		 * @param {string} [props.testId]
		 * @param {number} [props.backdropMediumPositionPercent]
		 * @param {Object} [props.parentWidget=PageManager]
		 */
		static open(props)
		{
			const mediumPositionPercent = props.backdropMediumPositionPercent ?? DEFAULT_HEIGHT;
			const bottomSheet = new BottomSheet({
				titleParams: {
					text: props.title || Loc.getMessage('M_LAYOUT_UI_VIEWER_LIST_DEFAULT_TITLE'),
					type: 'dialog',
				},
				component: (layout) => {
					return new ViewerList({
						layout,
						entityType: props.entityType,
						entityId: props.entityId,
						testId: props.testId,
						renderCustomDescription: props.renderCustomDescription,
					});
				},
			});

			void bottomSheet
				.setParentWidget(props.parentWidget || PageManager)
				.enableSwipe()
				.enableBounce()
				.disableShowOnTop()
				.enableResizeContent()
				.disableHorizontalSwipe()
				.setMediumPositionPercent(mediumPositionPercent)
				.disableOnlyMediumPosition()
				.open();
		}
	}

	module.exports = { ViewerListManager };
});
