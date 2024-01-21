/**
 * @module tasks/layout/stage-list
 */

jn.define('tasks/layout/stage-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { StageList } = require('layout/ui/stage-list');

	const {
		TasksStageListItem,
	} = require('tasks/layout/stage-list/item');

	/**
	 * @class TasksStageList
	 */
	class TasksStageList extends StageList
	{
		getStageListTitle()
		{
			return this.props.title ?? BX.message('TASKS_STAGE_LIST_DEFAULT_TITLE');
		}

		getAllStagesItem()
		{
			return {
				id: 'total', // this.kanbanSettingsId,
				stage: {
					id: 'total', // this.kanbanSettingsId,
					color: AppTheme.colors.bgContentPrimary,
					name: BX.message('STAGE_LIST_ALL_STAGES_TITLE'),
					statusId: '',
					listMode: true,
				},
				showContentBorder: true,
			};
		}

		renderStageListItem(stage)
		{
			const {
				onSelectedStage,
				canMoveStages,
				enableStageSelect,
				filterParams,
			} = this.props;

			const active = this.getActiveStageId() === stage.id;

			return View(
				{},
				TasksStageListItem({
					...stage,
					readOnly: this.readOnly,
					onSelectedStage,
					canMoveStages,
					showTotal: this.showTotal,
					showCount: this.showCount,
					showCounters: this.showCounters,
					showAllStagesItem: this.showAllStagesItem,
					onOpenStageDetail: this.onOpenStageDetailHandler,
					enableStageSelect,
					enabled: this.isStageEnabled(stage.id),
					active,
					filterParams,
					isReversed: this.isReversed,
				}),
			);
		}

		isStageEnabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}
	}

	module.exports = {
		TasksStageList,
	};
});
