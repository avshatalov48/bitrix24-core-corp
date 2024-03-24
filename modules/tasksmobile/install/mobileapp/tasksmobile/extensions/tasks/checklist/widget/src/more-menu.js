/**
 * @module tasks/checklist/widget/src/more-menu
 */
jn.define('tasks/checklist/widget/src/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { outline: { plus, trashCan, newsfeed, onlyMine, hideDone } } = require('assets/icons');
	const { ContextMenu } = require('layout/ui/context-menu');

	const ACTION_IDS = {
		hideCompleted: 'hide-completed',
		onlyMine: 'show-only-mine',
	};

	/**
	 * @class ChecklistMoreMenu
	 */
	class ChecklistMoreMenu extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.state = {
				selectedId: null,
			};
		}

		show(parentWidget)
		{
			this.menu = this.createMenu();
			this.menu.show(parentWidget);
		}

		createMenu()
		{
			return new ContextMenu({
				actions: this.getAction(),
			});
		}

		selectedAction(action, actionId)
		{
			const { selectedId: stateSelectedId } = this.state;

			const isSelected = stateSelectedId === actionId;
			const selectedId = isSelected ? null : actionId;

			this.setState({ selectedId }, () => {
				action(Boolean(selectedId));
			});
		}

		getAction()
		{
			const {
				onCreateChecklist,
				onHideCompleted,
				onShowOnlyMine,
				onChangeSort,
				onRemove,
			} = this.props;

			const { selectedId: stateSelectedId } = this.state;

			return [
				{
					id: 'create-checklist',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_CREATE_CHECKLIST'),
					onClickCallback: () => {
						this.menu.close(onCreateChecklist);
					},
					data: {
						svgIcon: plus(),
					},
				},
				{
					id: ACTION_IDS.hideCompleted,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_HIDE_COMPLETED'),
					onClickCallback: () => {
						this.selectedAction(onHideCompleted, ACTION_IDS.hideCompleted);
					},
					isSelected: stateSelectedId === ACTION_IDS.hideCompleted,
					data: {
						svgIcon: hideDone(),
					},
				},
				{
					id: 'show-only-mine',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_ONLY_MINE'),
					onClickCallback: () => {
						this.selectedAction(onShowOnlyMine, ACTION_IDS.onlyMine);
					},
					isSelected: stateSelectedId === ACTION_IDS.onlyMine,
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
						svgIcon: newsfeed(),
					},
				},
				{
					id: 'remove',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_REMOVE'),
					isDestructive: true,
					onClickCallback: () => {
						this.menu.close(onRemove);
					},
					data: {
						svgIcon: trashCan(),
					},
				},
			];
		}
	}

	ChecklistMoreMenu.propTypes = {
		parentWidget: PropTypes.object,
		onCreateChecklist: PropTypes.func,
		onHideCompleted: PropTypes.func,
		onShowOnlyMine: PropTypes.func,
		onChangeSort: PropTypes.func,
		onRemove: PropTypes.func,
	};

	module.exports = { ChecklistMoreMenu };
});
