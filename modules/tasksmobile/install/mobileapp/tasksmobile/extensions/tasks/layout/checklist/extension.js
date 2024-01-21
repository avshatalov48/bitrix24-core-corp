/**
 * @module tasks/layout/checklist
 */
jn.define('tasks/layout/checklist', (require, exports, module) => {
	const { CheckList } = require('tasks/layout/checklist/list');
	const { ChecklistPreview } = require('tasks/layout/checklist/preview');
	const { CheckList: ChecklistLegacy } = require('tasks/layout/checklist/legacy');

	module.exports = { CheckList, ChecklistPreview, ChecklistLegacy };
});
