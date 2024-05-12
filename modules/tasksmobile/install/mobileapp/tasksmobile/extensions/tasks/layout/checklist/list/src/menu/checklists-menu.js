/**
 * @module tasks/layout/checklist/list/src/menu/checklists-menu
 */
jn.define('tasks/layout/checklist/list/src/menu/checklists-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PropTypes } = require('utils/validation');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { outline: { arrowRight, taskList1 } } = require('assets/icons');

	/**
	 * @class ChecklistsMenu
	 */
	class ChecklistsMenu
	{
		/**
		 * @public
		 * @param {object} props
		 * @param {object} [props.parentWidget]
		 * @param {function} [props.moveItemToChecklist]
		 * @param {array} [props.checklists]
		 */
		static open(props)
		{
			const menu = new ChecklistsMenu(props);

			menu.show();
		}

		constructor(props)
		{
			this.props = props;

			this.contextMenu = this.createContextMenu();
		}

		show()
		{
			const { parentWidget } = this.props;

			this.contextMenu.show(parentWidget);
		}

		/**
		 * @private
		 * @returns {ContextMenu}
		 */
		createContextMenu()
		{
			return new ContextMenu({
				actions: this.getMenuActions(),
				params: {
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO'),
				},
			});
		}

		/**
		 * @private
		 * @return {object[]} actions
		 */
		getMenuActions()
		{
			const { checklists, sourceChecklistId } = this.props;
			const actions = [];

			checklists.forEach((checkList) => {
				const rootItem = checkList.getRootItem();
				const checkListId = rootItem.getId();

				if (sourceChecklistId !== checkListId)
				{
					actions.push({
						id: checkListId,
						title: rootItem.getTitle(),
						isCustomIconColor: true,
						data: {
							svgIcon: taskList1({ color: AppTheme.colors.accentMainPrimary }),
						},
						onClickCallback: () => {
							this.onClose(rootItem.getId());
						},
					});
				}
			});

			actions.push(this.getActionToNewChecklistItem());

			return actions;
		}

		/**
		 * @private
		 * @return {object} createNewChecklistAction
		 */
		getActionToNewChecklistItem()
		{
			return {
				id: 'newChecklist',
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO_NEW'),
				isCustomIconColor: true,
				data: {
					svgIcon: arrowRight({ color: AppTheme.colors.base1 }),
				},
				onClickCallback: () => {
					this.onClose();
				},
			};
		}

		/**
		 * @private
		 * @param {string | number} checklistId
		 */
		onClose(checklistId)
		{
			const { moveItemToChecklist } = this.props;

			this.contextMenu.close(() => {
				if (moveItemToChecklist)
				{
					moveItemToChecklist(checklistId);
				}
			});
		}
	}

	ChecklistsMenu.propTypes = {
		parentWidget: PropTypes.object,
		moveItemToChecklist: PropTypes.func,
		checklists: PropTypes.array,
		sourceChecklistId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
	};

	module.exports = { ChecklistsMenu };
});
