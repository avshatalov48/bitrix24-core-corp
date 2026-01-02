/**
 * @module bizproc/workflow/info
 */
jn.define('bizproc/workflow/info', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');

	const { PureComponent } = require('layout/pure-component');

	const { WorkflowDetailsSkeleton } = require('bizproc/skeleton');
	const { WorkflowDetails } = require('bizproc/workflow/details');
	const { TaskDetails } = require('bizproc/task/details');

	class WorkflowInfo extends PureComponent
	{
		/**
		 * @param {{}} props
		 * @param {?string} props.uid
		 * @param {?string} props.title
		 * @param {?string} props.workflowId
		 * @param {?number} props.taskId
		 * @param {?number} props.targetUserId
		 * @param {?boolean} props.readOnlyTimeline
		 * @param {?boolean} props.showNotifications
		 * @param layout
		 */
		static open(props, layout = PageManager)
		{
			layout.openWidget(
				'layout',
				{
					modal: true,
					titleParams: {
						text: props.title || Loc.getMessage('M_BP_WORKFLOW_INFO_WIDGET_TITLE'),
						textColor: Color.base4.toHex(),
						type: 'dialog',
					},
					backdrop: {
						mediumPositionPercent: 90,
						onlyMediumPosition: true,
						swipeAllowed: true,
						swipeContentAllowed: true,
						horizontalSwipeAllowed: false,
						hideNavigationBar: false,
					},
				},
			)
				.then((readyLayout) => {
					readyLayout.showComponent(new WorkflowInfo({
						uid: props.uid || null,
						workflowId: props.workflowId || null,
						parentLayout: layout,
						layout: readyLayout,
						taskId: props.taskId || null,
						targetUserId: props.targetUserId || null,
						readOnlyTimeline: props.readOnlyTimeline || false,
						showNotifications: props.showNotifications || false,
					}));
				})
				.catch(() => {});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				workflowId: null,
				taskId: null,
			};
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.#loadInfo();
		}

		#loadInfo()
		{
			const userId = this.props.targetUserId || 0;

			BX.ajax.runAction(
				'bizprocmobile.Workflow.loadWorkflowInfo',
				{ data: { workflowId: this.props.workflowId, taskId: this.props.taskId, userId } },
			)
				.then((response) => {
					if (response.data.taskId > 0)
					{
						this.setState({ taskId: response.data.taskId });
					}

					this.setState({ workflowId: response.data.workflowId });
				})
				.catch((response) => {
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);

						return;
					}

					console.error(response.errors);
				});
		}

		render()
		{
			if (this.state.taskId === null && this.state.workflowId === null)
			{
				return WorkflowDetailsSkeleton();
			}

			if (this.state.taskId > 0)
			{
				return new TaskDetails({
					uid: this.props.uid,
					parentLayout: this.props.parentLayout,
					layout: this.props.layout,
					taskId: this.state.taskId,
					workflowId: this.props.workflowId || null,
					targetUserId: this.props.targetUserId || null,
					readOnlyTimeline: this.props.readOnlyTimeline || false,
					showNotifications: this.props.showNotifications || false,
				});
			}

			return new WorkflowDetails({
				parentLayout: this.props.parentLayout,
				layout: this.props.layout,
				workflowId: this.state.workflowId,
			});
		}
	}

	module.exports = { WorkflowInfo };
});
