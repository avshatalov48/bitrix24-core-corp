/**
 * @module tasks/layout/checklist/list/src/menu/checklists-menu
 */
jn.define('tasks/layout/checklist/list/src/menu/checklists-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');
	const { PropTypes } = require('utils/validation');

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

			this.menu = this.#createMenu();
		}

		show()
		{
			const { targetRef } = this.props;

			this.menu.show({ target: targetRef });
		}

		#createMenu()
		{
			return new UIMenu(this.getMenuActions());
		}

		/**
		 * @private
		 * @return {object[]} actions
		 */
		getMenuActions()
		{
			const { checklists, sourceChecklistId } = this.props;
			const actions = [];

			[...checklists.values()].sort((a, b) => {
				const itemA = a.getRootItem()?.getSortIndex();
				const itemB = b.getRootItem()?.getSortIndex();

				return itemA - itemB;
			}).forEach((checklist) => {
				const rootItem = checklist.getRootItem();
				const checklistId = rootItem.getId();

				if (sourceChecklistId !== checklistId)
				{
					actions.push({
						id: String(checklistId),
						title: rootItem.getTitle(),
						isCustomIconColor: true,
						iconName: Icon.TASK_LIST,
						onItemSelected: () => {
							this.handleItemSelected(checklistId);
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
				iconName: Icon.PLUS,
				onItemSelected: () => {
					this.handleItemSelected();
				},
			};
		}

		/**
		 * @private
		 * @param {string | number} checklistId
		 */
		handleItemSelected = (checklistId) => {
			const { moveItemToChecklist } = this.props;

			if (moveItemToChecklist)
			{
				moveItemToChecklist(checklistId);
			}
		};
	}

	ChecklistsMenu.propTypes = {
		parentWidget: PropTypes.object,
		moveItemToChecklist: PropTypes.func,
		checklists: PropTypes.array,
		sourceChecklistId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
	};

	module.exports = { ChecklistsMenu };
});
