/**
 * @module tasks/selector/editable-task
 */
jn.define('tasks/selector/editable-task', (require, exports, module) => {
	const { TaskSelector } = require('tasks/selector/task');

	/**
	 * @class EditableTaskSelector
	 */
	class EditableTaskSelector extends TaskSelector
	{
		static getEntityId()
		{
			return 'editable_task';
		}

		static getContext()
		{
			return 'EDITABLE_TASK';
		}
	}

	module.exports = { EditableTaskSelector };
});
