/**
 * @module tasks/checklist/widget/src/more-menu
 */
jn.define('tasks/checklist/widget/src/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { plus, trashBin, feedOutline, onlyMine, hideDone } = require('assets/icons');
	const { ContextMenu } = require('layout/ui/context-menu');

	/**
	 * @function checklistMoreMenu
	 */
	const showChecklistMoreMenu = (props = {}) => {
		const {
			parentWidget = PageManager,
			onCreateChecklist,
			onHideCompleted,
			onShowOnlyMine,
			onChangeSort,
			onRemove,
		} = props;

		const moreMenu = new ContextMenu({
			actions: [
				{
					id: 'create-checklist',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_CREATE_CHECKLIST'),
					onClickCallback: onCreateChecklist,
					data: {
						svgIcon: plus(),
					},
				},
				{
					id: 'hide-completed',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_HIDE_COMPLETED'),
					isDisabled: true,
					onClickCallback: onHideCompleted,
					data: {
						svgIcon: hideDone(),
					},
				},
				{
					id: 'show-only-mine',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_ONLY_MINE'),
					isDisabled: true,
					onClickCallback: onShowOnlyMine,
					data: {
						svgIcon: onlyMine(),
					},
				},
				{
					id: 'change-sort',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_CHANGE_SORT'),
					isDisabled: true,
					onClickCallback: onChangeSort,
					data: {
						svgIcon: feedOutline(),
					},
				},
				{
					id: 'remove',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_REMOVE'),
					onClickCallback: () => {
						moreMenu.close(onRemove);
					},
					data: {
						svgIcon: trashBin(),
					},
				},
			],
		});

		moreMenu.show(parentWidget);
	};

	showChecklistMoreMenu.propTypes = {
		parentWidget: PropTypes.object,
		onCreateChecklist: PropTypes.func,
		onHideCompleted: PropTypes.func,
		onShowOnlyMine: PropTypes.func,
		onChangeSort: PropTypes.func,
		onRemove: PropTypes.func,
	};

	module.exports = { showChecklistMoreMenu };
});
