/**
 * @module tasks/checklist
 */
jn.define('tasks/checklist', (require, exports, module) => {
	const { CheckListFlatTree } = require('tasks/checklist/flat-tree');
	const { ChecklistController } = require('tasks/checklist/controller');

	module.exports = { CheckListFlatTree, ChecklistController };
});
