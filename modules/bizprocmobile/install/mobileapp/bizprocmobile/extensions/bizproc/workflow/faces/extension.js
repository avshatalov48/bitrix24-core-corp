/**
 * @module bizproc/workflow/faces
 * */
jn.define('bizproc/workflow/faces', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { formatRoundedTime, roundTimeInSeconds } = require('bizproc/helper/duration');
	const { WorkflowFacesColumn } = require('bizproc/workflow/faces/column-view');
	const { WorkflowFacesTimeline } = require('bizproc/workflow/faces/timeline');

	class WorkflowFaces extends PureComponent
	{
		get faces()
		{
			return this.props.faces;
		}

		get needRenderMoreTasksIcon()
		{
			return (
				(this.faces.completedTaskCount >= 3)
				|| (!this.faces.workflowIsCompleted && this.faces.completedTaskCount >= 2)
			);
		}

		getDurations()
		{
			return {
				author: Type.isNil(this.faces.time?.author) ? 0 : this.faces.time.author,
				completed: Type.isNil(this.faces.time?.completed) ? 0 : this.faces.time.completed,
				running: Type.isNil(this.faces.time?.running) ? 0 : this.faces.time.running,
				done: Type.isNil(this.faces.time?.done) ? 0 : this.faces.time.done,
			};
		}

		render()
		{
			return View(
				{ style: styles.wrapper },
				View(
					{
						style: styles.facesWrapper,
					},
					this.renderStick(),
					this.renderFirstColumn(),
					this.renderMoreTasksIcon(),
					this.renderSecondColumn(),
					this.needRenderMoreTasksIcon && View({ style: { width: 1 } }),
					this.renderThirdColumn(),
				),
				new WorkflowFacesTimeline({
					isCompleted: this.faces.workflowIsCompleted,
					durations: this.getDurations(),
				}),
			);
		}

		renderFirstColumn()
		{
			return new WorkflowFacesColumn({
				label: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_AUTHOR'),
				avatars: [this.faces.author],
				time: this.getFormattedTime(this.getDurations().author),
				columnStyles: {
					marginLeft: 9,
					minWidth: 42,
					flexShrink: 2,
				},
			});
		}

		renderSecondColumn()
		{
			if (this.faces.completed.length === 0)
			{
				return this.faces.workflowIsCompleted ? this.renderDoneColumn() : this.renderRunningColumn();
			}

			return new WorkflowFacesColumn({
				label: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_COMPLETED_MSGVER_1'),
				avatars: this.faces.completed,
				status: this.faces.completedSuccess ? 'accept' : 'decline',
				time: this.getFormattedTime(this.getDurations().completed),
			});
		}

		renderThirdColumn()
		{
			if (this.faces.completed.length === 0)
			{
				return new WorkflowFacesColumn({
					testId: `${this.testId}_EMPTY_BLOCK`,
					columnStyles: {
						minWidth: 42,
					},
				});
			}

			return this.faces.workflowIsCompleted ? this.renderDoneColumn() : this.renderRunningColumn();
		}

		renderRunningColumn()
		{
			return new WorkflowFacesColumn({
				label: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_RUNNING_1'),
				status: 'progress',
				avatars: Type.isArrayFilled(this.faces.running) ? this.faces.running : null,
				time: this.getFormattedTime(this.getDurations().running),
			});
		}

		renderDoneColumn()
		{
			return new WorkflowFacesColumn({
				label: Loc.getMessage('BPMOBILE_WORKFLOW_FACES_COMPLETED_DONE_MSGVER_1'),
				avatars: Type.isArrayFilled(this.faces.done) ? this.faces.done : null,
				status: (
					Type.isArrayFilled(this.faces.done)
						? (this.faces.doneSuccess ? 'accept' : 'decline')
						: 'accept'
				),
				time: this.getFormattedTime(this.getDurations().done),
			});
		}

		renderMoreTasksIcon()
		{
			if (!this.needRenderMoreTasksIcon)
			{
				return null;
			}

			return View(
				{ style: { alignSelf: 'center' } },
				Image({
					style: { width: 16, height: 16 },
					svg: { content: dots },
				}),
			);
		}

		getFormattedTime(time)
		{
			return (
				time === 0
					? Loc.getMessage('BPMOBILE_WORKFLOW_FACES_EMPTY_TIME')
					: formatRoundedTime(roundTimeInSeconds(Number(time)))
			);
		}

		renderStick()
		{
			return View({ style: styles.facesStick });
		}
	}

	const styles = {
		wrapper: {
			flexDirection: 'row',
			alignItems: 'stretch',
			marginTop: 10,
			maxWidth: 440,
			height: 100,
		},
		facesWrapper: {
			flex: 1,
			flexDirection: 'row',
			alignItems: 'flex-start',
			justifyContent: 'space-between',
			paddingTop: 12.5,
			paddingBottom: 11.5,
			paddingRight: 10,
			paddingLeft: 4.5,
			borderTopLeftRadius: 6,
			borderBottomLeftRadius: 6,
			borderLeftWidth: 1,
			borderLeftColor: AppTheme.colors.base7,
			borderTopWidth: 1,
			borderTopColor: AppTheme.colors.base7,
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.base7,
		},
		facesStick: {
			height: 1,
			position: 'absolute',
			top: 50,
			left: 35,
			right: 35,
			backgroundColor: AppTheme.colors.base6,
		},
	};

	const dots = `
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<rect y="2" width="16" height="12" rx="6" fill="${AppTheme.colors.base7}"/>
			<path
				d="M6.60827 7.86957C6.60827 8.34981 6.21895 8.73913 5.73871 8.73913C5.25846 8.73913 4.86914 8.34981 4.86914 7.86957C4.86914 7.38932 5.25846 7 5.73871 7C6.21895 7 6.60827 7.38932 6.60827 7.86957Z"
				fill="${AppTheme.colors.base3}"
			/>
			<path
				d="M9.04305 7.86957C9.04305 8.34981 8.65374 8.73913 8.17349 8.73913C7.69324 8.73913 7.30392 8.34981 7.30392 7.86957C7.30392 7.38932 7.69324 7 8.17349 7C8.65374 7 9.04305 7.38932 9.04305 7.86957Z"
				fill="${AppTheme.colors.base3}"
			/>
			<path
				d="M11.4778 7.86957C11.4778 8.34981 11.0885 8.73913 10.6083 8.73913C10.128 8.73913 9.73871 8.34981 9.73871 7.86957C9.73871 7.38932 10.128 7 10.6083 7C11.0885 7 11.4778 7.38932 11.4778 7.86957Z"
				fill="${AppTheme.colors.base3}"
			/>
		</svg>
	`;

	module.exports = { WorkflowFaces };
});
