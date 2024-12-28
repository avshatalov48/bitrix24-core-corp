import { ajax, Event, Loc, Extension, Tag, Text, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { TextInput, TextArea, UserSelector } from 'ui.form-elements.view';
import { Interval, IntervalSelector } from 'tasks.interval-selector';
import { FormPage } from './form-page';
import { ValueChecker } from '../value-checker';
import { bindFilterNumberInput } from '../bind-filter-number-input';

import type { Flow } from '../edit-form';

type Params = {
	onChangeHandler: Function,
};

export class AboutPage extends FormPage
{
	#params: Params;

	#layout: {
		aboutPageForm: HTMLFormElement,

		flowName: TextInput,
		flowDescription: TextInput,
		taskCreatorsSelector: UserSelector,
		plannedCompletionTime: TextInput,
		plannedCompletionTimeIntervalSelector: IntervalSelector,
		skipWeekends: ValueChecker,
	};

	#flow: Flow;

	constructor(params: Params)
	{
		super();
		this.#params = params;
		this.#layout = {};
		this.#flow = {};
	}

	setFlow(flow: Flow): void
	{
		this.#flow = flow;
	}

	getId(): string
	{
		return 'about-flow';
	}

	getTitle(): string
	{
		return Loc.getMessage('TASKS_FLOW_EDIT_FORM_ABOUT_FLOW');
	}

	getRequiredData(): string[]
	{
		return ['name', 'plannedCompletionTime', 'taskCreators'];
	}

	showErrors(incorrectData: string[]): void
	{
		if (incorrectData.includes('name'))
		{
			this.#layout.flowName.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_NAME_ERROR')]);
		}

		if (incorrectData.includes('plannedCompletionTime'))
		{
			this.#layout.plannedCompletionTime.setErrors(
				[Loc.getMessage('TASKS_FLOW_EDIT_FORM_PLANNED_COMPLETION_TIME_ERROR')],
			);
			this.#layout.plannedCompletionTime.emit('onSetErrors');
		}

		if (incorrectData.includes('taskCreators'))
		{
			this.#layout.taskCreatorsSelector.setErrors(
				[Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_CREATORS_ERROR')],
			);
		}
	}

	cleanErrors(): void
	{
		this.#layout.flowName.cleanError();
		this.#layout.plannedCompletionTime.cleanError();
		this.#layout.taskCreatorsSelector.cleanError();

		this.#layout.plannedCompletionTime.emit('onCleanErrors');
	}

	getFields(flowData: Flow = {}): Flow
	{
		const plannedCompletionTimeValue = parseInt(this.#layout.plannedCompletionTime?.getValue(), 10);
		const intervalDuration = this.#layout.plannedCompletionTimeIntervalSelector?.getDuration();
		const interval = this.#layout.plannedCompletionTimeIntervalSelector?.getInterval();

		return {
			name: flowData.name ?? this.#layout.flowName?.getValue().trim(),
			description: flowData.description ?? this.#layout.flowDescription?.getValue(),
			taskCreators: flowData.taskCreators ?? this.#getTasksCreatorsFromSelector(),
			plannedCompletionTime: flowData.plannedCompletionTime ?? (plannedCompletionTimeValue * intervalDuration || 0),
			matchSchedule: flowData.matchSchedule ?? this.#isShortInterval(interval),
			matchWorkTime: flowData.matchWorkTime ?? (this.#layout.skipWeekends?.isChecked() ?? true),
		};
	}

	#getTasksCreatorsFromSelector()
	{
		let taskCreators = this.#layout.taskCreatorsSelector
			?.getSelector()
			.getTags()
			.map((tag) => [tag.entityId, tag.id])
		;

		if (Type.isUndefined(taskCreators) || taskCreators.length === 0)
		{
			taskCreators = this.#layout.taskCreatorsSelector
				?.getSelector()
				?.getDialog()
				.getPreselectedItems()
			;
		}

		return taskCreators;
	}

	#isShortInterval(interval: Interval): boolean
	{
		const shortIntervals = ['minutes', 'hours'];

		return shortIntervals.includes(interval);
	}

	render(): HTMLElement
	{
		this.#layout.flowName = new TextInput({
			id: 'tasks-flow-edit-form-field-name',
			label: Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_NAME'),
			placeholder: Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_NAME_EXAMPLE'),
			value: this.#flow.name,
		});

		this.#layout.flowDescription = new TextArea({
			id: 'tasks-flow-edit-form-field-description',
			label: Loc.getMessage('TASKS_FLOW_EDIT_FORM_DESCRIPTION'),
			placeholder: Loc.getMessage('TASKS_FLOW_EDIT_FORM_DESCRIPTION_EXAMPLE'),
			value: this.#flow.description,
			resizeOnlyY: true,
		});

		this.#layout.taskCreatorsSelector = new UserSelector({
			id: 'tasks-flow-edit-form-field-creators',
			label: Loc.getMessage('TASKS_FLOW_EDIT_FORM_WHO_CAN_ADD_TASKS'),
			enableDepartments: true,
			values: this.#flow.taskCreators,
		});

		this.#layout.aboutPageForm = Tag.render`
			<form class="tasks-flow__create-about">
				${this.#layout.flowName.render()}
				${this.#layout.flowDescription.render()}
				<div class="tasks-flow__create-separator --empty"></div>
				${this.#layout.taskCreatorsSelector.render()}
				<div class="tasks-flow__create-separator --empty"></div>
				${this.#renderPlannedCompletionTime()}
			</form>
		`;

		Event.bind(this.#layout.aboutPageForm, 'change', this.#params.onChangeHandler);

		return this.#layout.aboutPageForm;
	}

	focusToEmptyName()
	{
		const isEmpty = this.#layout.flowName.getValue().trim().length === 0;

		if (isEmpty)
		{
			this.#layout.flowName.getInputNode().focus();
		}
	}

	#renderPlannedCompletionTime(): HTMLElement
	{
		const value = this.#flow.plannedCompletionTime;
		this.#layout.plannedCompletionTimeIntervalSelector = new IntervalSelector({ value });

		const needUseSchedule = this.#needUseSchedule;
		if (needUseSchedule)
		{
			this.#layout.plannedCompletionTimeIntervalSelector.subscribe('intervalChanged', (event) => {
				const interval = event.getData().interval;
				const isSkipWeekendsDisabled = this.#isShortInterval(interval);

				this.#layout.skipWeekends.disable(isSkipWeekendsDisabled);
			});
		}

		const duration = this.#layout.plannedCompletionTimeIntervalSelector.getDuration();

		const plannedCompletionTimeLabel = `
			<div class="tasks-flow__create-title-with-hint">
				${Loc.getMessage('TASKS_FLOW_EDIT_FORM_PLANNED_COMPLETION_TIME')}
				<span
					data-id="plannedCompletionTimeHint"
					class="ui-hint"
					data-hint="${Loc.getMessage('TASKS_FLOW_EDIT_FORM_PLANNED_COMPLETION_TIME_HINT')}" 
					data-hint-no-icon
				>
					<span class="ui-hint-icon"></span>
				</span>
			</div>
		`;

		this.#layout.plannedCompletionTime = new TextInput({
			label: plannedCompletionTimeLabel,
			placeholder: '0',
			inputDefaultWidth: true,
			value: String(value / duration || ''),
		});

		const maxInt = (2 ** 32) / 2 - 1;
		const monthDuration = 60 * 60 * 24 * 31;

		bindFilterNumberInput({
			input: this.#layout.plannedCompletionTime.getInputNode(),
			max: Math.floor(maxInt / monthDuration),
		});

		const interval = this.#layout.plannedCompletionTimeIntervalSelector.getInterval();
		const isSkipWeekendsDisabled = needUseSchedule && this.#isShortInterval(interval);

		this.#layout.skipWeekends = new ValueChecker({
			id: 'planned-completion-time-skip-weekends',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_SKIP_WEEKENDS'),
			value: isSkipWeekendsDisabled ? true : this.#flow.id === 0 || this.#flow.matchWorkTime,
			size: 'extra-small',
			isFieldDisabled: isSkipWeekendsDisabled,
			hintText: Loc.getMessage('TASKS_FLOW_EDIT_FORM_DISABLED_SKIP_WEEKENDS_HINT'),
			hintOnDisabled: true,
		});
		this.#layout.skipWeekends.subscribe('lock', (baseEvent: BaseEvent) => {
			requestAnimationFrame(() => {
				const hintManager = top.BX.UI.Hint.createInstance({
					id: `tasks-flow-edit-form-about-page-${Text.getRandom()}`,
					className: 'skipInitByClassName',
					popupParameters: {
						targetContainer: this.#layout.aboutPageForm.closest('.tasks-wizard__step'),
					},
				});
				hintManager.initNode(baseEvent.getData());
			});
		});

		const root = Tag.render`
			<div data-id="tasks-flow-edit-form-field-planned-time">
				<div class="tasks-flow__create-planned_completion-time">
					${this.#layout.plannedCompletionTime.render()}
					<div class="tasks-flow__create-planned_completion-time-interval">
						${this.#layout.plannedCompletionTimeIntervalSelector.render()}
					</div>
				</div>
				${this.#layout.skipWeekends.render()}
			</div>
		`;

		return root;
	}

	get #needUseSchedule(): boolean
	{
		const settings = Extension.getSettings('tasks.flow.edit-form');

		return settings.get('needUseSchedule');
	}

	async onContinueClick(flowData: Flow = {}): Promise<boolean>
	{
		const { data: response } = await ajax.runAction('tasks.flow.Flow.isExists', {
			data: {
				flowData: flowData,
			},
		});

		if (response.exists)
		{
			this.#layout.flowName.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_DUPLICATE_ERROR')]);

			return false;
		}

		return true;
	}
}
