/**
 * @module tasks/selector/task
 */
jn.define('tasks/selector/task', (require, exports, module) => {
	const { BaseSelectorEntity } = require('selector/widget/entity');

	/**
	 * @class TaskSelector
	 */
	class TaskSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'task';
		}

		static getContext()
		{
			return 'TASKS';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_TASK');
		}

		static getStartTypingWithCreationText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_TASK');
		}

		static getSearchPlaceholderWithCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_TASK');
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_TASK');
		}

		static isCreationEnabled()
		{
			return false;
		}

		static canCreateWithEmptySearch()
		{
			return false;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_TASK');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_TASK');
		}
	}

	module.exports = {
		TaskSelector,
	};
});
