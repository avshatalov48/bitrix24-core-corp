import { EventEmitter } from 'main.core.events';

/** @memberof BX.Crm.Timeline.Tools */
export default class WorkflowEventManager
{
	constructor(settings)
	{
		this.hasRunningWorkflow = settings.hasRunningWorkflow;
		this.hasWaitingWorkflowTask = settings.hasWaitingWorkflowTask;
		this.workflowTaskActivityId = settings.workflowTaskActivityId;
		this.workflowFirstTourClosed = settings.workflowFirstTourClosed;
		this.workflowTaskStatusTitle = settings.workflowTaskStatusTitle;

		this.init();
	}

	init(): void
	{
		this.handleRunningWorkflow();
		this.handleWaitingWorkflowTask();
		this.subscribeToTimelineEvents();
		this.subscribeToPullEvents();
	}

	handleRunningWorkflow(): void
	{
		if (!this.hasRunningWorkflow)
		{
			return;
		}

		this.runFirstAutomationTour();
	}

	runFirstAutomationTour(): void
	{
		if (!document.querySelector('.bp_starter'))
		{
			return;
		}

		EventEmitter.emit(
			'BX.Crm.Timeline.Bizproc::onAfterWorkflowStarted',
			{
				stepId: 'on-after-started-workflow',
				target: '.bp_starter',
			},
		);
	}

	handleWaitingWorkflowTask(): void
	{
		if (!this.hasWaitingWorkflowTask)
		{
			return;
		}

		if (this.workflowFirstTourClosed)
		{
			this.runSecondAutomationTour(`ACTIVITY_${this.workflowTaskActivityId}`);
		}
		else
		{
			EventEmitter.subscribe('UI.Tour.Guide:onFinish', (event) => {
				const eventData = event.data;
				const stepId = eventData?.guide?.steps?.[0]?.id;
				if (stepId === 'on-after-started-workflow')
				{
					this.runSecondAutomationTour(`ACTIVITY_${this.workflowTaskActivityId}`);
				}
			});
		}
	}

	runSecondAutomationTour(activityId = null): void
	{
		if (!activityId)
		{
			return;
		}

		const task = document.querySelector(`div[data-id="${activityId}"]`);
		if (task && this.isElementInViewport(task))
		{
			EventEmitter.emit(
				'BX.Crm.Timeline.Bizproc::onAfterCreatedTask',
				{
					stepId: 'on-after-created-task',
					target: task.querySelector('.crm-timeline__card-status'),
				},
			);
		}
	}

	subscribeToTimelineEvents(): void
	{
		EventEmitter.subscribe('BX.Crm.Timeline.Items.Bizproc:onAfterItemLayout', (event) => {
			const eventData = event.data;

			if (eventData?.options?.add === false)
			{
				return;
			}

			const isWorkflowStarted = eventData?.type === 'BizprocWorkflowStarted';
			if (isWorkflowStarted)
			{
				this.runFirstAutomationTour();
			}

			const isBizprocTask = eventData?.type === 'Activity:BizprocTask';
			if (eventData?.target && this.isElementInViewport(eventData?.target) && isBizprocTask)
			{
				EventEmitter.subscribe('UI.Tour.Guide:onFinish', (params) => {
					const paramsData = params.data;
					const stepId = paramsData?.guide?.steps?.[0]?.id;
					const card = eventData?.target.querySelector('.crm-timeline__card');
					const activityId = card.getAttribute('data-id');
					if (stepId === 'on-after-started-workflow' && activityId)
					{
						this.runSecondAutomationTour(activityId);
					}
				});
			}
		});
	}

	subscribeToPullEvents(): void
	{
		EventEmitter.subscribe('onPullEvent-crm', (event) => {
			const eventData = event.data?.[1];
			const isUpdateAction = eventData?.action === 'update';
			const itemLayout = eventData?.item?.layout;
			const itemTypeTask = eventData?.item?.type === 'Activity:BizprocTask';
			const itemId = eventData?.id;

			if (isUpdateAction && itemLayout && itemId && itemTypeTask)
			{
				const card = document.querySelector(`div[data-id="${itemId}"]`);
				const isSecondaryStatus = itemLayout?.header?.tags?.status?.title === this.workflowTaskStatusTitle;

				if (isSecondaryStatus && card)
				{
					EventEmitter.emit(
						'BX.Crm.Timeline.Bizproc::onAfterCompletedTask',
						{
							stepId: 'on-after-completed-task',
							target: card.querySelector('.crm-timeline__card-top_checkbox'),
						},
					);
				}
			}
		});
	}

	isElementInViewport(element: HTMLElement): boolean
	{
		const rect = element.getBoundingClientRect();

		return rect.bottom > 0 && rect.top < (window.innerHeight || document.documentElement.clientHeight);
	}
}
