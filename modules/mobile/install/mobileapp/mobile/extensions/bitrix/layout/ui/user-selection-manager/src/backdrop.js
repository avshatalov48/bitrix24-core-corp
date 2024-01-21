/**
 * @module layout/ui/user-selection-manager/src/backdrop
 */
jn.define('layout/ui/user-selection-manager/src/backdrop', (require, exports, module) => {

	/**
	 * @function showSelectionManagerBackdrop
	 */
	const showSelectionManagerBackdrop = (props) => {
		const { parentWidget = PageManager, component } = props;

		return new Promise((resolve) => {
			parentWidget.openWidget('layout', {
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: false,
					hideNavigationBar: true,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					mediumPositionPercent: 70,
				},
			}).then((layoutWidget) => {
				layoutWidget.showComponent(component);

				resolve(layoutWidget);
			}).catch(console.error);
		});

	};

	module.exports = { showSelectionManagerBackdrop };
});
