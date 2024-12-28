import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Process, ProcessCallback, ProcessResultStatus, ProcessState } from 'ui.stepprocessing';

type Params = {
	action: string,
	data: Action,
	step?: number,
	requestStopFunction: Function,
};

type Action = {
	forAll: string,
	groupId: string,
	selectedIds: arrow,
	setDeadline?: string,
	num?: number,
	type?: string,
	taskControlState?: string,
	responsibleId?: number,
	originatorId?: number,
	auditorId?: number,
	accompliceId?: number,
	specifyGroupId?: number,
};

export class GroupActionsStepper extends EventEmitter
{
	static ACTION_CONTROLLER = 'tasks.task.action.group';
	static ACTION_CONTROLLER_COUNT_TASKS = 'getTotalCountTasks';

	// eslint-disable-next-line no-unused-private-class-members
	#params: Params;
	#action: string;
	#data: Action;
	#title: ?string = null;
	#forAll: ?boolean = false;
	#step: number;
	#requestStopFunction: Function;

	#process: ?Process = null;

	constructor(params: Params)
	{
		super();

		this.setEventNamespace('BX.Tasks.GroupActionsStepper');
		this.#params = params;
		this.#action = params.action;
		this.#data = params.data;
		this.#step = params.step ?? 20;
		this.#requestStopFunction = params.requestStopFunction;
		this.#forAll = this.#data.forAll === 'Y';
		this.#title = this.#getTitle(this.#action);
		this.#process = this.#getProcess();
	}

	showDialog(): Process
	{
		this.#process.showDialog();

		return this.#process;
	}

	closeDialog(): Process
	{
		this.#process.closeDialog();

		return this;
	}

	#getProcess(): Process
	{
		if (!this.#process)
		{
			this.#process = new Process({
				id: 'TaskListGroupActionsStepper',
				controller: GroupActionsStepper.ACTION_CONTROLLER,
				messages:
				{
					DialogTitle: Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME'),
					DialogSummary: Loc.getMessage('TASKS_GRID_GROUP_ACTION_DESCRIPTION'),
					RequestCanceling: Loc.getMessage('TASKS_GRID_GROUP_ACTION_CANCELING'),
				},
				showButtons:
				{
					start: true,
					stop: true,
					close: true,
				},
				dialogMaxWidth: 600,
				popupOptions:
				{
					resizable: false,
					draggable: false,
					disableScroll: true,
				},
			});

			if (this.#forAll)
			{
				this.#getTotalElements();
			}

			this.#allSteps();
		}

		return this.#process;
	}

	#getTotalElements(): void
	{
		this.#data.nPageSize = this.#step;
		delete this.#data.selectedIds;
		delete this.#data.forAll;

		// add spetial step for determine total sessions
		this.#process.addQueueAction({
			title: Loc.getMessage('TASKS_GRID_GROUP_ACTION_COUNTING_ELEMENTS_PROGRESS'),
			action: GroupActionsStepper.ACTION_CONTROLLER_COUNT_TASKS,
			handlers:
			{
				StepCompleted(state, result)
				{
					if (state === ProcessResultStatus.completed)
					{
						const data = this.getParam('data') || [];
						// add total count in request
						if (result.TOTAL_ITEMS)
						{
							data.totalItems = parseInt(result.TOTAL_ITEMS, 10);
						}

						this.setParam('data', data);
					}
				},
			},
		});
	}

	#allSteps(): void
	{
		const requestStopFunction = this.#requestStopFunction;
		// on finish
		this.#process.setHandler(
			ProcessCallback.StateChanged,
			function(state)
			{
				if (state === ProcessResultStatus.completed)
				{
					requestStopFunction();
					// eslint-disable-next-line no-invalid-this
					this.closeDialog();
				}
			},
		)
			// on cancel
			.setHandler(
				ProcessCallback.RequestStop,
				function(actionData)
				{
					setTimeout(
						// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
						BX.delegate(
							function()
							{
								requestStopFunction();
								// eslint-disable-next-line no-invalid-this
								this.closeDialog();
							},
							// eslint-disable-next-line no-invalid-this
							this,
						),
						2000,
					);
				},
			)
			// payload action step
			.addQueueAction({
				title: this.#title,
				action: this.#action,
				handlers: {
					// keep total and processed in request
					StepCompleted(state, result)
					{
						if (state === ProcessResultStatus.progress)
						{
							const data = this.getParam('data') || [];

							if (result.PROCESSED_ITEMS)
							{
								data.processedItems = parseInt(result.PROCESSED_ITEMS, 10);
							}

							this.setParam('data', data);
						}

						if (state === ProcessState.error)
						{
							requestStopFunction();
							this.setMessage('RequestError', result.ERRORS);
							this.getDialog().setWarning(result.WARNING_TEXT, true);
						}
					},
				},
			})
			// params
			.setParam('data', this.#data)
		;
	}

	#getTitle(action: string): string
	{
		let title = '';

		switch (action)
		{
			case 'unmute':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_UNMUTE');
				break;
			case 'mute':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_MUTE');
				break;
			case 'ping':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_PING');
				break;
			case 'complete':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_COMPLETE');
				break;
			case 'setdeadline':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETDEADLINE');
				break;
			case 'adjustdeadline':
			case 'substractdeadline':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADJUSTDEADLINE');
				break;
			case 'settaskcontrol':
				if (this.#data.taskControlState === 'Y')
				{
					title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETTASKCONTROL_YES');
				}
				else
				{
					title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETTASKCONTROL');
				}
				break;
			case 'setresponsible':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETRESPONSIBLE');
				break;
			case 'setoriginator':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETORIGINATOR');
				break;
			case 'addauditor':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADDAUDITOR');
				break;
			case 'addaccomplice':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADDACCOMPLICE');
				break;
			case 'addtofavorite':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_ADDTOFAVORITE');
				break;
			case 'removefromfavorite':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_REMOVEFROMFAVORITE');
				break;
			case 'setgroup':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETGROUP');
				break;
			case 'setflow':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_SETFLOW');
				break;
			case 'delete':
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NAME_DELETE');
				break;
			default:
				title = Loc.getMessage('TASKS_GRID_GROUP_ACTION_NONE');
		}

		return title;
	}
}
