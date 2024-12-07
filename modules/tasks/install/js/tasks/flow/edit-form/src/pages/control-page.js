import { Event, Extension, Loc, Tag } from 'main.core';
import { UserSelector } from 'ui.form-elements.view';
import { FormPage } from './form-page';
import { ValueChecker } from '../value-checker';
import { bindFilterNumberInput } from '../bind-filter-number-input';

import type { Flow } from '../edit-form';

type Params = {
	onChangeHandler: Function,
};

export class ControlPage extends FormPage
{
	#params: Params;

	#layout: {
		controlPageForm: HTMLFormElement,

		flowOwnerSelector: UserSelector,
		notifyOnQueueOverflow: ValueChecker,
		notifyOnTasksInProgressOverflow: ValueChecker,
		notifyWhenEfficiencyDecreases: ValueChecker,
		analyticsPermissionsSelector: UserSelector,
	};

	#flow: Flow;

	constructor(params: Params)
	{
		super();
		this.#params = params;
		this.#layout = {};
		this.#flow = {};
	}

	get #currentUser(): number
	{
		const settings = Extension.getSettings('tasks.flow.edit-form');

		return settings.currentUser;
	}

	setFlow(flow: Flow): void
	{
		this.#flow = flow;
	}

	getId(): string
	{
		return 'control';
	}

	getTitle(): string
	{
		return Loc.getMessage('TASKS_FLOW_EDIT_FORM_CONTROL');
	}

	getRequiredData(): string[]
	{
		return this.#getCheckerValues().filter((checker) => this.#layout[checker].isChecked());
	}

	showErrors(incorrectData: string[]): void
	{
		if (incorrectData.includes('ownerId'))
		{
			this.#layout.flowOwnerSelector.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_OWNER_ERROR')]);
		}

		this.#getCheckerValues().forEach((checker) => {
			if (incorrectData.includes(checker))
			{
				this.#layout[checker].setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_VALUE_ERROR')]);
			}
		});
	}

	cleanErrors(): void
	{
		this.#layout.flowOwnerSelector.cleanError();
		this.#getCheckerValues().forEach((checker) => this.#layout[checker].cleanError());
	}

	#getCheckerValues(): string[]
	{
		return ['notifyOnQueueOverflow', 'notifyOnTasksInProgressOverflow', 'notifyWhenEfficiencyDecreases'];
	}

	getFields(flowData: Flow = {}): Flow
	{
		let ownerId = this.#currentUser;
		if (this.#layout.flowOwnerSelector?.getSelector().getDialog().isLoaded())
		{
			ownerId = this.#layout.flowOwnerSelector.getSelector().getTags()[0]?.id;
		}

		return {
			ownerId: flowData.ownerId || (ownerId || 0),

			notifyOnQueueOverflow: flowData.notifyOnQueueOverflow ?? this.#getCheckerNumericValue(this.#layout.notifyOnQueueOverflow),
			notifyOnTasksInProgressOverflow: flowData.notifyOnTasksInProgressOverflow ?? this.#getCheckerNumericValue(this.#layout.notifyOnTasksInProgressOverflow),
			notifyWhenEfficiencyDecreases: flowData.notifyWhenEfficiencyDecreases ?? this.#getCheckerNumericValue(this.#layout.notifyWhenEfficiencyDecreases),
		}
	}

	#getCheckerNumericValue(checker: ?ValueChecker)
	{
		return checker?.isChecked() ? this.#getInteger(checker.getValue()) : null;
	}

	#getInteger(value: string): boolean
	{
		return /^\d+$/.test(value) ? parseInt(value, 10) : 0;
	}

	render(): HTMLElement
	{
		const flowOwnerLabel = `
			<div class="tasks-flow__create-title-with-hint">
				${Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_OWNER')}
				<span
					data-id="flowOwnerHint"
					class="ui-hint"
					data-hint="${Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_OWNER_HINT')}" 
					data-hint-no-icon
				><span class="ui-hint-icon"></span></span>
			</div>
		`;

		this.#layout.flowOwnerSelector = new UserSelector({
			id: 'tasks-flow-edit-form-field-owner',
			label: flowOwnerLabel,
			enableAll: false,
			enableDepartments: false,
			multiple: false,
			values: [['user', this.#flow.ownerId]],
		});

		this.#layout.notifyOnQueueOverflow = new ValueChecker({
			id: 'notify-on-queue-overflow',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_ON_QUEUE_OVERFLOW'),
			placeholder: 50,
			value: this.#flow.notifyOnQueueOverflow,
			size: 'extra-small',
		});

		bindFilterNumberInput({
			input: this.#layout.notifyOnQueueOverflow.getInputNode(),
			max: 99999,
		});

		this.#layout.notifyOnTasksInProgressOverflow = new ValueChecker({
			id: 'notify-on-tasks-in-progress-overflow',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW'),
			placeholder: 50,
			value: this.#flow.notifyOnTasksInProgressOverflow,
			size: 'extra-small',
		});

		bindFilterNumberInput({
			input: this.#layout.notifyOnTasksInProgressOverflow.getInputNode(),
			max: 99999,
		});

		this.#layout.notifyWhenEfficiencyDecreases = new ValueChecker({
			id: 'notify-when-efficiency-decreases',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_WHEN_EFFICIENCY_DECREASES'),
			placeholder: 70,
			unit: '%',
			value: this.#flow.notifyWhenEfficiencyDecreases,
			size: 'extra-small',
		});

		bindFilterNumberInput({
			input: this.#layout.notifyWhenEfficiencyDecreases.getInputNode(),
			max: 100,
		});

		this.#layout.analyticsPermissionsSelector = new UserSelector({
			id: 'tasks-flow-edit-form-field-analytics',
			label: Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_PERMISSIONS'),
			enableAll: true,
			enableDepartments: true,
			className: '',
		});

		this.#layout.controlPageForm = Tag.render`
			<form class="tasks-flow__create-control">
				${this.#layout.flowOwnerSelector.render()}
				${this.#layout.notifyOnQueueOverflow.render()}
				${this.#layout.notifyOnTasksInProgressOverflow.render()}
				${this.#layout.notifyWhenEfficiencyDecreases.render()}
				<div class="tasks-flow__create-separator"></div>
				${this.#renderAnalyticsStub()}
			</form>
		`;

		Event.bind(this.#layout.controlPageForm, 'change', this.#params.onChangeHandler);

		return this.#layout.controlPageForm;
	}

	#renderAnalyticsStub(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__create_analytics-stub">
				<span>${Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_STUB_LABEL')}</span>
				<span class="tasks-flow__create_analytics-stub-label ui-label ui-label-primary ui-label-fill">
					<span class="ui-label-inner">
						${Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_STUB_BUTTON')}
					</span>
				</span>
			</div>
		`;
	}
}
