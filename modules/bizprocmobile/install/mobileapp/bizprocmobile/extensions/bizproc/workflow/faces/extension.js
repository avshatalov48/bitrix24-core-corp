/**
 * @module bizproc/workflow/faces
 * */
jn.define('bizproc/workflow/faces', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { WorkflowFacesColumn } = require('bizproc/workflow/faces/column-view');
	const { WorkflowFacesTimeline } = require('bizproc/workflow/faces/timeline');
	const { ElementsStackSteps, Stick } = require('layout/ui/elements-stack-steps');

	class WorkflowFaces extends PureComponent
	{
		get faces()
		{
			return this.props.faces;
		}

		get needRenderMoreTasksIcon()
		{
			return Boolean(this.faces.progressBox);
		}

		get isWorkflowFinished()
		{
			return this.faces.isWorkflowFinished;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						maxWidth: 440,
						height: 104,
						borderTopColor: Color.bgSeparatorSecondary.toHex(),
						borderTopWidth: 1,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						borderBottomWidth: 1,
					},
				},
				View(
					{ style: { flex: 3, justifyContent: 'center' } },
					ElementsStackSteps(
						{ testId: `bizproc-workflow-faces-${this.props.workflowId}` },
						Stick({ leftOffset: '17%', rightOffset: '17%' }),
						this.renderFirstColumn(),
						this.renderMoreTasksIcon(),
						this.renderSecondColumn(),
						this.renderThirdColumn(),
					),
				),
				this.renderChevron(),
				View(
					{
						style: {
							flex: 1,
							justifyContent: 'center',
							backgroundColor: this.isWorkflowFinished ? Color.accentSoftGreen3.toHex() : null,
						},
					},
					ElementsStackSteps(
						{ testId: `bizproc-workflow-faces-timeline-${this.props.workflowId}` },
						this.renderTimeColumn(),
					),
				),
			);
		}

		renderFirstColumn()
		{
			const step = this.faces.steps[0] || {};

			return new WorkflowFacesColumn({
				label: step.name,
				avatars: step.avatars,
				duration: step.duration,
				status: Type.isArrayFilled(step.avatars) ? null : 'bp',
			});
		}

		renderSecondColumn()
		{
			const step = this.faces.steps[1] || {};

			return new WorkflowFacesColumn({
				label: step.name,
				avatars: step.avatars,
				duration: step.duration,
				status: step.status,
			});
		}

		renderThirdColumn()
		{
			const step = this.faces.steps[2] || {};

			return new WorkflowFacesColumn({
				label: step.name,
				avatars: step.avatars,
				duration: step.duration,
				status: step.status,
			});
		}

		renderChevron()
		{
			return View(
				{
					style: {
						width: 16,
						height: '100%',
						backgroundColor: this.isWorkflowFinished ? Color.accentSoftGreen3.toHex() : null,
					},
				},
				Image({
					style: { width: 16, height: '100%' },
					svg: { content: chevron },
				}),
			);
		}

		renderTimeColumn()
		{
			const step = this.faces.timeStep || {};

			return new WorkflowFacesTimeline({
				label: step.name,
				duration: step.duration,
				isCompleted: this.isWorkflowFinished,
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
	}

	const chevron = `
		<svg width="14" height="92" viewBox="0 0 14 92" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M13 46L0 92V0L13 46Z" fill="${AppTheme.colors.bgContentPrimary}"/>
			<path d="M0.5 0L13 46.2514L0.5 92" stroke="${Color.bgSeparatorSecondary.toHex()}"/>
		</svg>
	`;

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
