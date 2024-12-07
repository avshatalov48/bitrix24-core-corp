/**
 * @module tasks/checklist/widget/src/more-menu
 */
jn.define('tasks/checklist/widget/src/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');
	const { confirmDestructiveAction } = require('alert');

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

			this.#initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initState(props);
		}

		#initState(props)
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
			return new UIMenu(this.getActions());
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

		getActions()
		{
			const {
				accessRestrictions = {},
				onCreateChecklist,
				onHideCompleted,
				onShowOnlyMine,
				onRemove,
			} = this.props;

			const { hideCompleted: hideCompletedState, onlyMine: onlyMineState } = this.state;
			const actions = [];
			const { remove: canRemove, add: canAdd } = accessRestrictions;

			if (canAdd)
			{
				actions.push({
					id: 'create-checklist',
					testId: 'create-checklist',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_CREATE_CHECKLIST_MSGVER_1'),
					onItemSelected: onCreateChecklist,
					iconName: Icon.PLUS,
				});
			}

			// actions.push({
			// 	id: 'change-sort',
			// 	title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_CHANGE_SORT'),
			// 	isDisabled: true,
			// 	onClickCallback: onChangeSort,
			// 	data: {
			// 		svgIcon: newsfeed(),
			// 	},
			// });

			actions.push(
				{
					id: ACTION_IDS.hideCompleted,
					testId: ACTION_IDS.hideCompleted,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_HIDE_COMPLETED_MSGVER_1'),
					onItemSelected: () => {
						this.selectedAction({
							state: { hideCompleted: !hideCompletedState },
							action: onHideCompleted,
						});
					},
					checked: hideCompletedState,
					iconName: Icon.BAN,
				},
				{
					id: 'show-only-mine',
					testId: 'show-only-mine',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_ONLY_MINE_MSGVER_1'),
					onItemSelected: () => {
						this.selectedAction({
							state: { onlyMine: !onlyMineState },
							action: onShowOnlyMine,
						});
					},
					checked: onlyMineState,
					iconName: Icon.PERSON,
				},
			);

			if (canRemove)
			{
				actions.push({
					id: 'remove',
					testId: 'remove',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_REMOVE'),
					isDestructive: true,
					onItemSelected: () => {
						confirmDestructiveAction({
							title: '',
							description: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_SHOW_CONFIRM_REMOVE'),
							onDestruct: onRemove,
						});
					},
					iconName: Icon.TRASHCAN,
				});
			}

			return actions;
		}
	}

	ChecklistMoreMenu.propTypes = {
		parentWidget: PropTypes.object,
		onCreateChecklist: PropTypes.func,
		onHideCompleted: PropTypes.func,
		onShowOnlyMine: PropTypes.func,
		onChangeSort: PropTypes.func,
		onRemove: PropTypes.func,
		accessRestrictions: PropTypes.object,
	};

	module.exports = { ChecklistMoreMenu };
});
