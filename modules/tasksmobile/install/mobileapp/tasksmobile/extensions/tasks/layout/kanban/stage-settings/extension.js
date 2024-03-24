/**
 * @module tasks/layout/kanban/stage-settings
 */
jn.define('tasks/layout/kanban/stage-settings', (require, exports, module) => {
	const { NotifyManager } = require('notify-manager');
	const { KanbanStageSettings } = require('layout/ui/kanban/stage-settings');
	const { connect } = require('statemanager/redux/connect');
	const {
		selectById,
		updateStage,
		deleteStage,
	} = require('tasks/statemanager/redux/slices/stage-settings');

	/**
	 * @class TasksKanbanStageSettings
	 */
	class TasksKanbanStageSettings extends KanbanStageSettings
	{
		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderStageName(),
				this.renderColorPicker(),
				this.renderDeleteButton(),
			);
		}

		updateStage()
		{
			return new Promise((resolve, reject) => {
				if ((typeof this.changedFields.name === 'string') && this.changedFields.name.trim() === '')
				{
					reject();

					return;
				}
				NotifyManager.showLoadingIndicator();
				const {
					statusId: stageStatusId,
					id,
					name,
					color,
				} = this.stage;

				this.props.updateStage(
					{
						view: this.props.view,
						projectId: this.props.projectId,
						stageId: id,
						statusId: stageStatusId,
						name: this.changedFields.name || name,
						color: this.changedFields.color || color,
					},
				)
					.then(() => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						resolve();
					})
					.catch((errors) => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						console.error(errors);
						reject();
					});
			});
		}

		deleteStage()
		{
			const { id } = this.stage;

			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();
				this.props.deleteStage(
					{
						stageId: id,
						view: this.props.view,
						projectId: this.props.projectId,
						ownerId: this.props.ownerId,
					},
				).unwrap()
					.then((deleteResult) => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						resolve();
					}).catch((deleteResult) => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						console.error(deleteResult);
						reject();
						if (deleteResult.status === 'error'
							&& deleteResult.errors.length > 0)
						{
							if (deleteResult.errors[0].customData?.public)
							{
								NotifyManager.showError(deleteResult.errors[0].message);
							}
							else
							{
								NotifyManager.showDefaultError();
							}
						}
					});
			});
		}

		static open(params, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams())
					.then((layout) => {
						layout.enableNavigationBarBorder(false);

						layout.showComponent(connect(mapStateToProps, mapDispatchToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch(reject);
			});
		}
	}

	const mapStateToProps = (state, ownProps) => ({
		stage: selectById(state, ownProps.stageId),
	});

	const mapDispatchToProps = ({
		updateStage,
		deleteStage,
	});

	module.exports = {
		TasksKanbanStageSettings,
	};
});
