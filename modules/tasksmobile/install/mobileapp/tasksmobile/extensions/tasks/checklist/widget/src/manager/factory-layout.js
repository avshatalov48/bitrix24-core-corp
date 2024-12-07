/**
 * @module tasks/checklist/widget/src/manager/factory-layout
 */
jn.define('tasks/checklist/widget/src/manager/factory-layout', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { ChecklistBottomSheet } = require('tasks/checklist/widget/src/manager/bottom-sheet');
	const { ChecklistPageLayout } = require('tasks/checklist/widget/src/manager/page-layout');

	const BOTTOM_SHEET = 'bottomSheet';
	const PAGE_LAYOUT = 'pageLayout';

	const layoutsMap = {
		[BOTTOM_SHEET]: ChecklistBottomSheet,
		[PAGE_LAYOUT]: ChecklistPageLayout,
	};

	/**
	 * @typedef {Object} ChecklistBottomSheetProps
	 *
	 * @param layoutType
	 * @param {ChecklistBottomSheetProps} restProps
	 *
	 * @function ChecklistBottomSheet
	 */
	function checklistWidgetFactoryLayout({ layoutType, ...restProps })
	{
		const Layout = layoutsMap[layoutType];

		if (!Layout)
		{
			return new layoutsMap[BOTTOM_SHEET](restProps);
		}

		return new Layout(restProps);
	}

	checklistWidgetFactoryLayout.propTypes = {
		layoutType: PropTypes.string,
		parentWidget: PropTypes.object,
		component: PropTypes.object,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
		onShowMoreMenu: PropTypes.func,
	};

	module.exports = { checklistWidgetFactoryLayout, BOTTOM_SHEET, PAGE_LAYOUT };
});
