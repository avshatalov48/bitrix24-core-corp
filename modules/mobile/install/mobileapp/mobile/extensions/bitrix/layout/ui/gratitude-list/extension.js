/**
 * @module layout/ui/gratitude-list
 */
jn.define('layout/ui/gratitude-list', (require, exports, module) => {
	const { GratitudeList } = require('layout/ui/gratitude-list/src/list');
	const { Loc } = require('loc');

	/**
	 * @class GratitudeListManager
	 */
	class GratitudeListManager
	{
		/**
		 * @param {Object} props
		 * @param {Object} props.parentWidget
		 * @param {number} props.ownerId
		 * @returns {void}
		 */
		static openList(props)
		{
			if (!props.ownerId)
			{
				return;
			}

			const parentWidget = props.parentWidget ?? PageManager;

			const config = {
				enableNavigationBarBorder: false,
				titleParams: {
					text: Loc.getMessage('M_UI_GRATITUDE_LIST_TITLE'),
					type: 'dialog',
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new GratitudeList({
						layout: readyLayout,
						ownerId: props.ownerId,
					}));
				},
			};

			void parentWidget.openWidget('layout', config);
		}
	}

	module.exports = {
		GratitudeListManager,
	};
});
