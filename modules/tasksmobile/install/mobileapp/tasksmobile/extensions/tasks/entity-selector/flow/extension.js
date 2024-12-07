/**
 * @module tasks/entity-selector/flow
 */
jn.define('tasks/entity-selector/flow', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('tasks/loc');

	const DEFAULT_ICON = `/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/fields/flow/images/${AppTheme.id}/flow-icon.png`;

	// eslint-disable-next-line no-undef
	class TaskFlowSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'flow';
		}

		static isCreationEnabled()
		{
			return false;
		}

		static getTitle()
		{
			return Loc.getMessage('M_TASKS_FLOWS');
		}

		static prepareItemForDrawing(entity)
		{
			return { imageUrl: DEFAULT_ICON };
		}
	}

	module.exports = { TaskFlowSelector };
});
