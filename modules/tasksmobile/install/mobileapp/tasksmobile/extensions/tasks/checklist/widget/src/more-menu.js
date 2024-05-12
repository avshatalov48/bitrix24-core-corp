/**
 * @module tasks/checklist/widget/src/more-menu
 */
jn.define('tasks/checklist/widget/src/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { outline: { plus, trashCan, onlyMine, hideDone } } = require('assets/icons');
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

			this.initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.initState(props);
		}

		initState(props)
		{
			this.state = {
				onlyMine: props.onlyMine,
				hideCompleted: props.hideCompleted,
			};
		}

		reload(params)
		{
			return new Promise((resolve) => {
				this.setState(params, () => {
					resolve(this.state);
				});
			});
		}

		show(parentWidget)
		{
			this.menu = this.createMenu();
			this.menu.show(parentWidget);
		}

		createMenu()
		{
			return new ContextMenu({
				testId: 'checklist_more_menu',
				actions: this.getAction(),
			});
		}

		selectedAction({ state, action })
		{
			this.setState(
				state,
				() => {
					action(state);
				},
			);
		}

		getAction()
		{
			const {
				onCreateChecklist,
				onHideCompleted,
				onShowOnlyMine,
				onRemove,
			} = this.props;

			const { hideCompleted: hideCompletedState, onlyMine: onlyMineState } = this.state;

			return [
				{
					id: 'create-checklist',
					testId: 'create-checklist',
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
					testId: ACTION_IDS.hideCompleted,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_HIDE_COMPLETED'),
					onClickCallback: () => {
						this.selectedAction({
							state: { hideCompleted: !hideCompletedState },
							action: onHideCompleted,
						});
					},
					isSelected: hideCompletedState,
					data: {
						svgIcon: hideDone(),
					},
				},
				{
					id: 'show-only-mine',
					testId: 'show-only-mine',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_ONLY_MINE_MSGVER_1'),
					onClickCallback: () => {
						this.selectedAction({
							state: { onlyMine: !onlyMineState },
							action: onShowOnlyMine,
						});
					},
					isSelected: onlyMineState,
					data: {
						svgIcon: onlyMine(),
					},
				},
				// {
				// 	id: 'change-sort',
				// 	title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_CHANGE_SORT'),
				// 	isDisabled: true,
				// 	onClickCallback: onChangeSort,
				// 	data: {
				// 		svgIcon: newsfeed(),
				// 	},
				// },
				{
					id: 'remove',
					testId: 'remove',
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
