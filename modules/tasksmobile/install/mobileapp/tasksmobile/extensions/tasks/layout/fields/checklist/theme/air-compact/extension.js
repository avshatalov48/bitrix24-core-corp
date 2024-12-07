/**
 * @module tasks/layout/fields/checklist/theme/air-compact
 */
jn.define('tasks/layout/fields/checklist/theme/air-compact', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { withTheme } = require('layout/ui/fields/theme');
	const { ChecklistPreview, ClickStrategy } = require('tasks/layout/checklist/preview');

	/**
	 * @param {ChecklistPreview} field
	 * @return {Chip}
	 * @constructor
	 */
	const AirTheme = ({ field }) => {
		const checklists = field.isLoading() ? field.getChecklistStubs() : field.getSortedChecklists();

		return AirCompactThemeView({
			testId: field.testId,
			bindContainerRef: field.bindContainerRef,
			empty: field.isEmpty(),
			readOnly: field.isReadOnly(),
			multiple: field.isMultiple(),
			leftIcon: {
				icon: Icon.TASK_LIST,
			},
			text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_TITLE'),
			textMultiple: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_TITLE_MULTI'),
			onClick: field.getContentClickHandler(),
			count: checklists.length,
		});
	};

	/** @type {function(object): object} */
	const ChecklistField = withTheme(ChecklistPreview, AirTheme);

	module.exports = {
		AirTheme,
		ChecklistField,
		ClickStrategy,
	};
});
