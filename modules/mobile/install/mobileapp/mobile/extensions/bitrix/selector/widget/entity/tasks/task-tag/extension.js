/**
 * @module selector/widget/entity/tasks/task-tag
 */
jn.define('selector/widget/entity/tasks/task-tag', (require, exports, module) => {
	class TaskTagSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'task-tag';
		}

		static getContext()
		{
			return 'TASKS_TAG';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_TASK_TAG');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_TASK_TAG');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATING_TASK_TAG');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				return new Promise((resolve) => {
					resolve({
						id: text,
						entityId: this.getEntityId(),
						title: text,
					});
				});
			};
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_TASK_TAG_2');
		}
	}

	module.exports = {TaskTagSelector};
});