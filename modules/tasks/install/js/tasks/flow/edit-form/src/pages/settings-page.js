import { Dom, Event, Extension, Loc, Tag, Type, ajax } from 'main.core';
import { PULL as Pull } from 'pull.client';
import { Dialog, TagItem, TagSelector } from 'ui.entity-selector';
import { UserSelector } from 'ui.form-elements.view';
import { PullRequests } from '../pull-requests';
import { FormPage } from './form-page';
import { ValueChecker } from '../value-checker';

import type { DistributionType, Flow } from '../edit-form';

type Params = {
	onChangeHandler: Function,
};

const BIG_DEPARTMENT_USER_COUNT = 30;

const HINT_MESSAGES_BY_COUNT = [
	{ condition: (count) => count === 0, message: 'TASKS_FLOW_EDIT_FORM_THIS_IS_EMPTY_DEPARTMENT_HINT' },
	{ condition: (count) => count > BIG_DEPARTMENT_USER_COUNT, message: 'TASKS_FLOW_EDIT_FORM_THIS_IS_BIG_DEPARTMENT_HINT' },
];

export class SettingsPage extends FormPage
{
	#params: Params;

	#layout: {
		settingsPageForm: HTMLFormElement,

		queueDistribution: HTMLElement,
		queueRadio: HTMLInputElement,
		responsiblesQueueSelector: UserSelector,
		responsiblesHimselfSelector: UserSelector,
		manuallyDistribution: HTMLElement,
		manuallyRadio: HTMLInputElement,
		himselfDistribution: HTMLElement,
		himselfRadio: HTMLInputElement,
		moderatorSelector: UserSelector,
		responsibleCanChangeDeadline: ValueChecker,
		notifyAtHalfTime: ValueChecker,
		taskControl: ValueChecker,
		projectSelector: UserSelector,
		taskTemplate: ValueChecker,
		taskTemplateDialog: Dialog,
	};

	#flow: Flow;

	constructor(params: Params)
	{
		super();
		this.#params = params;
		this.#layout = {};
		this.#flow = {};

		this.#init();
	}

	get #currentUser(): number
	{
		const settings = Extension.getSettings('tasks.flow.edit-form');

		return settings.currentUser;
	}

	#init()
	{
		this.#subscribeToPull();
	}

	#subscribeToPull(): void
	{
		const pullRequests = new PullRequests(this.#currentUser);
		pullRequests.subscribe('templateAdded', this.#onTemplateAddedHandler.bind(this));
		pullRequests.subscribe('templateUpdated', this.#onTemplateUpdatedHandler.bind(this));

		Pull.subscribe(pullRequests);
	}

	#onTemplateAddedHandler({ data })
	{
		const template = data.template;
		const templateItem = {
			id: template.id,
			entityId: 'task-template',
			title: template.title,
			tabs: 'recents',
		};

		this.#layout.taskTemplateDialog.addItem(templateItem);

		this.#layout.taskTemplateDialog.getItems().find((item) => item.id === templateItem.id).select();
	}

	#onTemplateUpdatedHandler({ data })
	{
		const template = data.template;
		const templateItem = this.#layout.taskTemplateDialog.getItem({ id: template.id, entityId: 'task-template' });

		if (Type.isStringFilled(template.title))
		{
			templateItem?.setTitle(template.title);
			this.#layout.taskTemplate?.update();
		}

		if (!Type.isArrayFilled(this.#layout.taskTemplateDialog.getSelectedItems()))
		{
			templateItem?.select();
		}
	}

	setFlow(flow: Flow): void
	{
		this.#flow = flow;
	}

	getId(): string
	{
		return 'settings';
	}

	getTitle(): string
	{
		return Loc.getMessage('TASKS_FLOW_EDIT_FORM_SETTINGS');
	}

	getRequiredData(): string[]
	{
		const requiredData = [
			'groupId',
			'responsibleList',
		];

		if (this.#layout.taskTemplate.isChecked())
		{
			requiredData.push('templateId');
		}

		return requiredData;
	}

	update(): void
	{
		super.update();

		Dom.removeClass(this.#layout.queueDistribution, '--active');
		Dom.removeClass(this.#layout.manuallyDistribution, '--active');
		Dom.removeClass(this.#layout.himselfDistribution, '--active');

		if (this.#layout.queueRadio.checked)
		{
			Dom.addClass(this.#layout.queueDistribution, '--active');
		}

		if (this.#layout.manuallyRadio.checked)
		{
			Dom.addClass(this.#layout.manuallyDistribution, '--active');
		}

		if (this.#layout.himselfRadio.checked)
		{
			Dom.addClass(this.#layout.himselfDistribution, '--active');
		}
	}

	showErrors(incorrectData: string[]): void
	{
		if (incorrectData.includes('responsibleList'))
		{
			this.#showResponsibleListError();
		}

		if (incorrectData.includes('groupId'))
		{
			this.#layout.projectSelector.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_PROJECT_FOR_TASKS_ERROR')]);
		}

		if (incorrectData.includes('templateId'))
		{
			this.#layout.taskTemplate.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_TEMPLATE_FOR_TASKS_ERROR')]);
		}
	}

	#showResponsibleListError(): void
	{
		const distributionType = new FormData(this.#layout.settingsPageForm).get('distribution');

		// eslint-disable-next-line default-case
		switch (distributionType)
		{
			case 'manually':
				this.#layout.moderatorSelector.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_MODERATOR_ERROR')]);
				break;
			case 'queue':
				this.#layout.responsiblesQueueSelector.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_RESPONSIBLES_ERROR')]);
				break;
			case 'himself':
				this.#layout.responsiblesHimselfSelector.setErrors([Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_RESPONSIBLES_ERROR')]);
				break;
		}
	}

	cleanErrors(): void
	{
		this.#layout.projectSelector.cleanError();
		this.#layout.responsiblesQueueSelector.cleanError();
		this.#layout.responsiblesHimselfSelector.cleanError();
		this.#layout.moderatorSelector.cleanError();
		this.#layout.taskTemplate.cleanError();
	}

	getFields(flowData: Flow = {}): Flow
	{
		const selectedDistributionType = new FormData(this.#layout.settingsPageForm).get('distribution');
		const distributionType: DistributionType = flowData.distributionType ?? (selectedDistributionType || 'queue');

		const responsibleList = this.#getResponsiblesByDistributionType(distributionType, flowData);

		let groupId = this.#flow.groupId;
		if (this.#layout.projectSelector?.getSelector().getDialog().isLoaded())
		{
			groupId = this.#layout.projectSelector.getSelector().getTags()[0]?.id;
		}

		return {
			distributionType,
			responsibleList,
			responsibleCanChangeDeadline: flowData.responsibleCanChangeDeadline ?? (this.#layout.responsibleCanChangeDeadline?.isChecked() ?? false),
			notifyAtHalfTime: flowData.notifyAtHalfTime ?? (this.#layout.notifyAtHalfTime?.isChecked() ?? false),
			taskControl: flowData.taskControl ?? (this.#layout.taskControl?.isChecked() ?? false),
			groupId: flowData.groupId ?? (groupId || 0),
			templateId: flowData.templateId ?? (this.#layout.taskTemplate?.isChecked() ? this.#layout.taskTemplate.getValue() : 0),
		};
	}

	#getResponsiblesByDistributionType(distributionType: DistributionType, flowData: Flow = {}): Array
	{
		let responsibleList = [];

		const isConsiderFlowResponsible = this.#flow.distributionType === distributionType;
		if (isConsiderFlowResponsible)
		{
			responsibleList = this.#flow.responsibleList;
		}

		const responsibleListFromSelector = this.#getResponsiblesFromSelectorByDistributionType(distributionType);
		if (!Type.isNull(responsibleListFromSelector))
		{
			responsibleList = responsibleListFromSelector;
		}

		const isConsiderFlowDataResponsible = flowData.distributionType === distributionType;
		if (isConsiderFlowDataResponsible)
		{
			return flowData.responsibleList ?? responsibleList;
		}

		return responsibleList;
	}

	#getResponsiblesFromSelectorByDistributionType(distributionType: DistributionType): ?Array
	{
		switch (distributionType)
		{
			case 'manually':
				return this.#getResponsiblesFromSelector(this.#layout.moderatorSelector);

			case 'queue':
				return this.#getResponsiblesFromSelector(this.#layout.responsiblesQueueSelector);

			case 'himself':
				return this.#getResponsiblesFromSelector(this.#layout.responsiblesHimselfSelector);

			default:
				return null;
		}
	}

	#getResponsiblesFromSelector(selector: UserSelector): ?Array
	{
		if (selector?.getSelector().getDialog().isLoaded())
		{
			return selector
				?.getSelector()
				.getTags()
				.map((tag) => [tag.entityId, tag.id])
			;
		}

		return null;
	}

	render(): HTMLElement
	{
		this.#layout.responsibleCanChangeDeadline = new ValueChecker({
			id: 'responsible-can-change-deadline',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_RESPONSIBLE_CAN_CHANGE_DEADLINE'),
			value: this.#flow.responsibleCanChangeDeadline,
		});

		const notifyAtHalfTimeTitle = `
			<div class="tasks-flow__create-title-with-hint">
				<span>${Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_AT_HALF_TIME')}</span>
				<span
					data-id="notifyAtHalfTimeHint"
					class="ui-hint ui-hint-flow-value-checker"
					data-hint="${Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_AT_HALF_TIME_HINT')}" 
					data-hint-no-icon
				>
					<span class="ui-hint-icon ui-hint-icon-flow-value-checker"></span>
				</span>
			</div>
		`;

		this.#layout.notifyAtHalfTime = new ValueChecker({
			id: 'notify-at-half-time',
			title: notifyAtHalfTimeTitle,
			value: this.#flow.notifyAtHalfTime,
		});

		this.#layout.taskControl = new ValueChecker({
			id: 'task-control',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASK_CONTROL'),
			value: this.#flow.taskControl,
		});

		this.#layout.projectSelector = new UserSelector({
			label: this.#getProjectLabel(),
			enableUsers: false,
			enableDepartments: false,
			multiple: false,
			entities: [
				{
					id: 'project',
					options: {
						features: {
							tasks: [],
						},
						checkFeatureForCreate: true,
						'!type': ['collab'],
						isFromFlowCreationForm: true,
					},
				},
			],
			values: this.#flow.groupId ? [['project', this.#flow.groupId]] : [],
		});

		this.#layout.taskTemplate = new ValueChecker({
			id: 'task-template',
			title: Loc.getMessage('TASKS_FLOW_EDIT_FORM_ACCEPT_TASKS_BY_TEMPLATE_TITLE'),
			entitySelector: this.#getTaskTemplateDialog(),
		});

		this.#layout.settingsPageForm = Tag.render`
			<form class="tasks-flow__create-settings">
				${this.#renderDistribution()}
				<div class="tasks-flow__create-separator --empty"></div>
				${this.#layout.responsibleCanChangeDeadline.render()}
				${this.#layout.notifyAtHalfTime.render()}
				${this.#layout.taskControl.render()}
				<div class="tasks-flow__create-separator"></div>
				${this.#layout.projectSelector.render()}
				<div class="tasks-flow__create-separator --empty"></div>
				${this.#layout.taskTemplate.render()}
			</form>
		`;

		Event.bind(this.#layout.settingsPageForm, 'change', this.#params.onChangeHandler);

		return this.#layout.settingsPageForm;
	}

	#renderDistribution(): HTMLElement
	{
		this.#layout.responsiblesQueueSelector = this.#getResponsiblesSelector(
			this.#flow.distributionType === 'queue' ? this.#flow.responsibleList : [],
			false,
		);

		this.#layout.responsiblesHimselfSelector = this.#getResponsiblesSelector(
			this.#flow.distributionType === 'himself' ? this.#flow.responsibleList : [],
		);

		this.#layout.moderatorSelector = new UserSelector({
			enableAll: false,
			enableDepartments: false,
			multiple: false,
			values: [[
				'user',
				this.#flow.distributionType === 'manually' && Type.isArrayFilled(this.#flow.responsibleList)
					? this.#flow.responsibleList[0][1]
					: this.#currentUser,
			]],
		});

		const { root: queueDistribution, radio: queueRadio } = this.#renderDistributionType({
			type: 'queue',
			selector: this.#layout.responsiblesQueueSelector,
		});
		this.#layout.queueDistribution = queueDistribution;
		this.#layout.queueRadio = queueRadio;

		const { root: manuallyDistribution, radio: manuallyRadio } = this.#renderDistributionType({
			type: 'manually',
			selector: this.#layout.moderatorSelector,
		});
		this.#layout.manuallyDistribution = manuallyDistribution;
		this.#layout.manuallyRadio = manuallyRadio;

		const { root: himselfDistribution, radio: himselfRadio } = this.#renderDistributionType({
			type: 'himself',
			selector: this.#layout.responsiblesHimselfSelector,
		});
		this.#layout.himselfDistribution = himselfDistribution;
		this.#layout.himselfRadio = himselfRadio;

		this.#layout.queueRadio.checked = this.#flow.distributionType === 'queue';
		this.#layout.manuallyRadio.checked = this.#flow.distributionType === 'manually';
		this.#layout.himselfRadio.checked = this.#flow.distributionType === 'himself';
		this.update();

		const selector = this.#layout.responsiblesHimselfSelector.getSelector();
		selector.getDialog().subscribe('onHide', this.checkDepartmentUsersCount.bind(this, selector));

		return Tag.render`
			<div class="ui-section__field-container">
				<div class="ui-section__field-label_box">
					<label class="ui-section__field-label">
						${Loc.getMessage('TASKS_FLOW_EDIT_FORM_DISTRIBUTION_TYPE')}
					</label>
				</div>
				${this.#layout.queueDistribution}
				${this.#layout.manuallyDistribution}
				${this.#layout.himselfDistribution}
			</div>
		`;
	}

	#getResponsiblesSelector(
		responsibleValues: string[],
		enableDepartments: boolean = true,
		multiple: boolean = true,
	): UserSelector
	{
		return new UserSelector({
			enableAll: false,
			multiple,
			enableDepartments,
			values: responsibleValues,
			label: Loc.getMessage('TASKS_FLOW_EDIT_FORM_DISTRIBUTION_QUEUE_SELECTOR_LABEL'),
		});
	}

	#renderDistributionType({ type, selector }): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__create-distribution-type --${type}" data-id="tasks-flow-distribution-${type}">
				<label class="ui-ctl ui-ctl-radio ui-ctl-wa">
					<input type="radio" name="distribution" value="${type}" class="ui-ctl-element" ref="radio">
					<div class="tasks-flow__create-distribution-type_title-container">
						<div class="tasks-flow__create-distribution-type_content">
						<div class="tasks-flow__create-distribution-type_title">
								<div class="tasks-flow__create-distribution-type_title-text">
									${Loc.getMessage(`TASKS_FLOW_EDIT_FORM_DISTRIBUTION_${type.toUpperCase()}`)}
								</div>
								<span class="tasks-flow__create-distribution-type_label ui-label ui-label-primary ui-label-fill">
									<span class="ui-label-inner">
										${Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_STUB_BUTTON')}
									</span>
								</span>
							</div>
							<div class="tasks-flow__create-distribution-type_hint">
								${Loc.getMessage(`TASKS_FLOW_EDIT_FORM_DISTRIBUTION_${type.toUpperCase()}_HINT`)}
							</div>
						</div>
						<div class="tasks-flow__create-distribution-type_icon --${type}"></div>
					</div>
				</label>
				<div class="tasks-flow__create-distribution-type_selector">
					${selector?.render()}
				</div>
			</div>
		`;
	}

	#getTaskTemplateDialog(): Dialog
	{
		this.#layout.taskTemplateDialog = new Dialog({
			width: 500,
			context: 'flow',
			preselectedItems: this.#flow.templateId ? [['task-template', this.#flow.templateId]] : '',
			enableSearch: true,
			multiple: false,
			entities: [
				{
					id: 'task-template',
				},
			],
		});

		return this.#layout.taskTemplateDialog;
	}

	#getProjectLabel(): string
	{
		const notifyEmptyProject = `
			<span
				data-id="notifyEmptyProjectHint"
				class="ui-hint ui-hint-flow-value-checker"
				data-hint="${Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_EMPTY_PROJECT_HINT')}" 
				data-hint-no-icon
			>
				<span class="ui-hint-icon ui-hint-icon-flow-value-checker"></span>
			</span>
		`;

		return `
			<div class="tasks-flow-field-label-container">
				<span>${Loc.getMessage('TASKS_FLOW_EDIT_FORM_PROJECT_FOR_TASKS')}</span>
				${this.#flow.id ? '' : notifyEmptyProject}
			</div>
		`;
	}

	checkDepartmentUsersCount(selector: TagSelector): void
	{
		const selectedTags = selector.getTags();

		const selectedDepartments = selectedTags.filter(
			(tag) => tag.getEntityId() === 'department',
		);

		let addedDepartments = [];
		if (Type.isUndefined(this.selectedDepartments) || this.selectedDepartments.length === 0)
		{
			addedDepartments = selectedDepartments;
		}
		else
		{
			addedDepartments = selectedDepartments.filter(
				(departmentTag) => !this.selectedDepartments.includes(departmentTag),
			);
		}

		this.selectedDepartments = selectedDepartments;

		if (addedDepartments.length === 0)
		{
			return;
		}

		this.#getDepartmentsUsersCount(addedDepartments).then((countArray) => {
			const departmentForHint = countArray.find(
				(departmentData) => departmentData.count > BIG_DEPARTMENT_USER_COUNT
				|| departmentData.count === 0,
			);

			if (departmentForHint)
			{
				const tag = addedDepartments.find(
					(item) => item.getId().toString() === departmentForHint.departmentId,
				);

				if (tag)
				{
					this.#showDepartmentHint(tag, this.#getHintMessageCodeByCount(departmentForHint.count));
				}
			}
		}).catch((error) => {
			console.error(error);
		});
	}

	#getHintMessageCodeByCount(count: number): string
	{
		const hintMessage = HINT_MESSAGES_BY_COUNT.find(
			(hint) => hint.condition(count),
		);

		return hintMessage ? hintMessage.message : '';
	}

	#showDepartmentHint(tag: TagItem, code: string): void
	{
		const popup = new BX.PopupWindow({
			content: BX.Loc.getMessage(code),
			darkMode: true,
			bindElement: tag.getContainer(),
			angle: true,
			contentPadding: 5,
			maxWidth: 400,
			offsetLeft: (tag.getContainer().offsetWidth / 2),
			autoHide: true,
			closeByEsc: true,
		});

		popup.show();
	}

	#getDepartmentsUsersCount(departments): ?Array
	{
		const departmentsToBackend = departments.map(
			(department) => [department.getEntityId(), department.getId()],
		);

		return ajax.runAction('tasks.flow.Flow.getDepartmentsMemberCount', {
			data: { departments: departmentsToBackend },
		}).then((result) => {
			return Type.isArrayFilled(result.errors) ? null : result.data;
		}).catch((errors) => {
			console.error(errors);
		});
	}
}
