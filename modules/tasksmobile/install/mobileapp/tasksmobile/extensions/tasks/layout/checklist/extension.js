/**
 * @module tasks/layout/checklist
 */
jn.define('tasks/layout/checklist', (require, exports, module) => {
	const { Checklist } = require('tasks/layout/checklist/list');
	const { ChecklistPreview } = require('tasks/layout/checklist/preview');

	module.exports = { Checklist, ChecklistPreview };
});
