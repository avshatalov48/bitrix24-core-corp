/**
 * @module tasks/layout/fields/checklist/theme/air
 */
jn.define('tasks/layout/fields/checklist/theme/air', (require, exports, module) => {
	const { Loc } = require('loc');
	const { withTheme } = require('layout/ui/fields/theme');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { ChecklistPreview } = require('tasks/layout/checklist/preview');
	const { Title } = require('tasks/layout/task/fields/checklist/theme/air/src/title');
	const { Item } = require('tasks/layout/task/fields/checklist/theme/air/src/item');
	const { Indent } = require('tokens');

	/**
	 * @param {ChecklistPreview} field
	 * @param {function} handleOnCreateChecklist
	 */
	const AirTheme = ({ field, handleOnCreateChecklist }) => {
		const checklists = field.getSortedChecklists();

		return View(
			{
				style: {
					paddingHorizontal: Indent.XL3,
				},
			},
			Title({
				count: checklists.length,
			}),
			View(
				{},
				...field.getSortedChecklists().map((checklist) => {
					const rootItem = checklist.getRootItem();
					const completedCount = rootItem.getCompleteCount();
					const totalCount = rootItem.getDescendantsCount();
					const title = rootItem.getTitle();

					return Item({
						totalCount,
						completedCount,
						title,
						onClick: field.openPageManager.bind(field, checklist),
					});
				}),
			),
			AddButton({
				text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_ADD_CHECKLIST'),
				onClick: handleOnCreateChecklist(field.props.parentWidget),
			}),
		);
	};

	/** @type {function(object): object} */
	const ChecklistField = withTheme(ChecklistPreview, AirTheme);

	module.exports = {
		AirTheme,
		ChecklistField,
	};
});
