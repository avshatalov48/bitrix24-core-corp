/**
 * @module tasks/layout/fields/checklist/theme/air-compact
 */
jn.define('tasks/layout/fields/checklist/theme/air-compact', (require, exports, module) => {
	const { Loc } = require('loc');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { withTheme } = require('layout/ui/fields/theme');

	const { ChecklistPreview } = require('tasks/layout/checklist/preview');

	const AirTheme = ({ field, handleOnCreateChecklist }) => {
		const checklists = field.getSortedChecklists();

		return AirCompactThemeView({
			testId: 'CHECKLIST_FIELD',
			empty: checklists.length === 0,
			multiple: true,
			leftIcon: 'taskList1',
			text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_COMPACT_TITLE'),
			onClick: handleOnCreateChecklist(field.props.parentWidget),
			count: checklists.length,
		});
	};

	/** @type {function(object): object} */
	const ChecklistField = withTheme(ChecklistPreview, AirTheme);

	module.exports = {
		AirTheme,
		ChecklistField,
	};
});
