import { ajax, Event, Loc, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Button, ButtonState } from 'ui.buttons';
import { Wizard } from 'tasks.wizard';
import { FormPage } from './pages/form-page';
import { AboutPage } from './pages/about-page';
import { SettingsPage } from './pages/settings-page';
import { ControlPage } from './pages/control-page';
import { Lottie } from 'ui.lottie';
import flowfLottieIconInfo from '../lottie/tasks-flow-info-icon.json';
import 'ui.sidepanel-content';
import 'ui.forms';
import 'ui.hint';
import './style.css';

type Params = {
	flowId?: number,
	flowName?: string,
	demoFlow?: 'Y' | 'N',
	guideFlow?: 'Y' | 'N',
};

export type DistributionType = 'manually' | 'queue' | 'himself' | 'by_workload';

export type Flow = {
	id: ?number,
	name: string,
	description: string,
	taskCreators: string[],
	plannedCompletionTime: number,
	matchSchedule: boolean,
	matchWorkTime: boolean,
	templateId: number,
	responsibleList: string[],
	responsibleCanChangeDeadline: boolean,
	distributionType: DistributionType,
	groupId: number,
	ownerId: number,
	demo: boolean,
	notifyOnQueueOverflow: ?number,
	notifyOnTasksInProgressOverflow: ?number,
	notifyWhenEfficiencyDecreases: ?number,
	notifyAtHalfTime: boolean,
	taskControl: boolean,
	trialFeatureEnabled: boolean;
};

const SLIDER_WIDTH = 692;
const HELPDESK_ARTICLE = 21272066;

export class EditForm extends EventEmitter
{
	#params: Params;

	#layout: {};

	#wizard: Wizard;
	#pages: FormPage[];
	#finishButton: Button;
	#saveChangesButton: Button;

	#flow: Flow;

	#flowLottieAnimation: any = null;
	#lottieIconContainer: HTMLElement;

	#pageChanging: boolean = false;

	constructor(params: Params = {})
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Flow.EditForm');

		this.#params = params;
		this.#layout = {};
		this.#flowLottieAnimation = null;
		this.#lottieIconContainer = null;

		const onChangeHandler = this.#onChangeHandler.bind(this);
		this.#pages = [
			new AboutPage({ onChangeHandler }),
			new SettingsPage({ onChangeHandler }),
			new ControlPage({ onChangeHandler }),
		];

		const initFlowData = {
			notifyAtHalfTime: true,
			responsibleCanChangeDeadline: false,
			taskControl: true,
			notifyOnQueueOverflow: 50,
			notifyOnTasksInProgressOverflow: 50,
			notifyWhenEfficiencyDecreases: 70,
			taskCreators: [
				['meta-user', 'all-users'],
			],
		};
		if (this.#params.flowName)
		{
			initFlowData.name = this.#params.flowName;
		}

		this.#flow = this.#getFlow(initFlowData);
	}

	static async createInstance(params: Params = {}): EditForm
	{
		const { EditForm } = await top.BX.Runtime.loadExtension('tasks.flow.edit-form');

		const instance = new EditForm(params);
		instance.openInSlider();

		return instance;
	}

	openInSlider()
	{
		const sidePanelId = `tasks-flow-create-slider-${Text.getRandom()}`;

		BX.SidePanel.Instance.open(sidePanelId, {
			cacheable: true,
			contentCallback: async (slider) => {
				this.slider = slider;

						const { data: noAccess } = await ajax.runAction('tasks.flow.View.Access.check', {
							data: {
								flowId: this.#flow.id > 0 ? this.#flow.id : 0,
								context: 'edit-form',
								demoFlow: this.#params.demoFlow,
								guideFlow: this.#params.guideFlow,
							},
						});

				if (noAccess !== null)
				{
					return Tag.render`${noAccess.html}`;
				}

				if (this.#flow.id > 0)
				{
					const { data: flowData } = await ajax.runAction('tasks.flow.Flow.get', {
						data: {
							flowId: this.#flow.id,
						},
					});

					this.#flow = this.#getFlow(flowData);
				}

				this.#pages.forEach((page) => page.setFlow(this.#flow));

				return this.#render();
			},
			width: SLIDER_WIDTH,
			events: {
				onLoad: (event) => {
					const aboutPage = this.#pages
						.find((page) => page.getId() === 'about-flow')
					;
					aboutPage.focusToEmptyName();
					requestAnimationFrame(() => {
						this.#wizard.initHints();
					});
				},
				onClose: () => {
					this.#wizard.hideHints();
					this.emit('afterClose');
				},
			},
		});
	}

	#render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__create">
				${this.#renderHeader()}
				${this.#renderWizard()}
			</div>
		`;
	}

	#renderHeader(): HTMLElement
	{
		const title = (
			Loc.getMessage(this.#flow.id
				? 'TASKS_FLOW_EDIT_FORM_HEADER_TITLE_EDIT'
				: 'TASKS_FLOW_EDIT_FORM_HEADER_TITLE')
		);

		const subTitle = (
			Loc.getMessage(this.#flow.id
				? 'TASKS_FLOW_EDIT_FORM_HEADER_SUBTITLE'
				: 'TASKS_FLOW_EDIT_FORM_HEADER_SUBTITLE_CREATE')
		);

		return Tag.render`
			<div class="ui-slider-section ui-slider-section-icon-center --rounding --icon-sm">
				<span class="tasks-flow__create-header_icon ui-icon ui-slider-icon"></span>
				<div class="ui-slider-content-box">
					<div class="ui-slider-heading-2">${title}</div>
					<div class="ui-slider-inner-box">
						<p class="ui-slider-paragraph-2">
							${subTitle}
						</p>
					</div>
				</div>
			</div>
		`;
	}

	#renderWizard(): HTMLElement
	{
		this.#wizard = new Wizard({
			steps: this.#pages.map((page) => ({
				id: page.getId(),
				title: page.getTitle(),
				content: page.render(),
				isFilled: () => !this.#hasIncorrectData(page.getRequiredData()),
			})),
			onCancel: () => this.slider.close(false, () => this.slider.destroy()),
			onDisabledContinueButtonClick: this.#showErrors.bind(this),
			onContinueHandler: this.#onContinueHandler.bind(this),
			finishButton: this.#getFinishButton(),
			saveChangesButton: this.#getSaveChangesButton(),
			article: HELPDESK_ARTICLE,
		});

		return this.#wizard.render();
	}

	#saveChangesAction(): ?Function
	{
		const isEdit = this.#flow.id > 0;

		if (!isEdit || this.#flow.demo)
		{
			return null;
		}

		return this.#saveFlowAction();
	}

	#getFinishButton(): Button
	{
		this.#finishButton ??= new Button({
			text: Loc.getMessage(this.#flow.id ? 'TASKS_FLOW_EDIT_FORM_SAVE_FLOW' : 'TASKS_FLOW_EDIT_FORM_CREATE_FLOW'),
			color: Button.Color.PRIMARY,
			round: true,
			size: Button.Size.LARGE,
			onclick: () => this.#saveFlowAction(),
		});

		return this.#finishButton;
	}

	#getSaveChangesButton(): ?Button
	{
		if (!this.#flow.id || this.#flow.demo)
		{
			return null;
		}

		this.#saveChangesButton ??= new Button({
			text: Loc.getMessage('TASKS_FLOW_EDIT_FORM_SAVE_CHANGES'),
			color: Button.Color.SUCCESS,
			round: true,
			size: BX.UI.Button.Size.LARGE,
			onclick: () => this.#saveChangesAction(),
		});

		return this.#saveChangesButton;
	}

	#onChangeHandler(): void
	{
		this.#flow = this.#getFlow();
		this.#pages.forEach((page) => page.update());
		this.#wizard.update();
	}

	#saveFlowAction(): void
	{
		if (this.#hasIncorrectData())
		{
			this.#showErrors();

			return;
		}

		if (this.#saveChangesButton?.isDisabled() || this.#finishButton?.isDisabled())
		{
			return;
		}

		this.#saveChangesButton?.setState(ButtonState.DISABLED);
		this.#finishButton?.setState(ButtonState.DISABLED);

		const flowData = Object.fromEntries(Object.entries(this.#getFlow()).map(([key, value]) => [
			key,
			Type.isBoolean(value) ? (value ? 1 : 0) : value,
		]));

		const action = flowData.id ? 'update' : 'create';
		const textNotification = flowData.id ? 'TASKS_FLOW_EDIT_FORM_FLOW_UPDATE' : 'TASKS_FLOW_EDIT_FORM_FLOW_CREATE';

		ajax.runAction(
			`tasks.flow.Flow.${flowData.demo ? 'activateDemo' : action}`,
			{
				data: {
					flowData,
					guideFlow: this.#params.guideFlow,
				},
			},
		).then((response) => {
			if (response.status === 'success')
			{
				const flowData: Flow = response.data;

				this.emit('afterSave', flowData);

				this.slider.close(false, () => {
					if (flowData.trialFeatureEnabled)
					{
						this.#showDemoInfo();
					}
					this.slider.destroy();
				});

				BX.UI.Notification.Center.notify({
					content: Loc.getMessage(textNotification),
					width: 'auto',
				});
			}
		}, (error) => {
			this.#saveChangesButton?.setState(null);
			this.#finishButton?.setState(null);
			alert(error.errors.map((e) => e.message).join('\n'));
		});
	}

	#showErrors(): void
	{
		const incorrectData = this.#getIncorrectData();
		this.#pages.forEach((page) => page.showErrors(incorrectData));
	}

	#showDemoInfo(): void
	{
		const popup: Popup = new Popup({
			id: 'tasks-flow-task-demo-info',
			className: 'tasks-flow__task-demo-info',
			width: 620,
			overlay: true,
			padding: 48,
			closeIcon: true,
			content: this.#renderDemoInfoContent(),
			events: {
				onFirstShow: (baseEvent: BaseEvent) => {
					this.#bindStartWorkBtn(baseEvent.getTarget());
				},
			},
		});

		popup.show();
	}

	#renderDemoInfoContent(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__task-demo-info_wrapper">
				<div class="tasks-flow__task-demo-info_content">
					<div class="tasks-flow__task-demo-info_title">
						${Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_TITLE_1')}
					</div>
					<div class="tasks-flow__task-demo-info_text">
						${Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_TEXT_1')}
					</div>
					<div class="tasks-flow__task-demo-info_text-trial">
						${Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_TEXT_TRIAL_1')}
					</div>
					<div class="ui-btn ui-btn-sm ui-btn-primary ui-btn-round ui-btn-no-caps">
						${Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_BTN_1')}
					</div>
				</div>
				${this.#getLottieIconContainer()}
			</div>
		`;
	}

	#getLottieIconContainer(): HTMLElement
	{
		if (!this.#lottieIconContainer)
		{
			this.#lottieIconContainer = Tag.render`
				<div class="tasks-flow__task-demo-info_image"></div>
			`;

			this.#flowLottieAnimation = Lottie.loadAnimation({
				container: this.#lottieIconContainer,
				renderer: 'svg',
				loop: false,
				animationData: flowfLottieIconInfo,
			});
		}

		return this.#lottieIconContainer;
	}

	#bindStartWorkBtn(popup: Popup)
	{
		const popupContainer = popup.getContentContainer();
		if (Type.isDomNode(popupContainer))
		{
			const btnNode = popup.getContentContainer().querySelector('.ui-btn');
			if (Type.isDomNode(popupContainer))
			{
				Event.bind(btnNode, 'click', () => popup.close());
			}
		}
	}

	#hasIncorrectData(fields: string[] = []): boolean
	{
		const incorrectData = this.#getIncorrectData();

		for (const field of fields)
		{
			if (incorrectData.includes(field))
			{
				return true;
			}
		}

		return !Type.isArrayFilled(fields) && Type.isArrayFilled(incorrectData);
	}

	#getIncorrectData(): string[]
	{
		const flowData = this.#flow;

		const incorrectData = [];
		if (!Type.isStringFilled(flowData.name))
		{
			incorrectData.push('name');
		}

		if (flowData.plannedCompletionTime <= 0)
		{
			incorrectData.push('plannedCompletionTime');
		}

		if (!Type.isArrayFilled(flowData.taskCreators))
		{
			incorrectData.push('taskCreators');
		}

		if (
			!Type.isArrayFilled(flowData.responsibleList)
			|| (flowData.distributionType === 'manually' && flowData.responsibleList[0] <= 0)
		)
		{
			incorrectData.push('responsibleList');
		}

		if (flowData.id > 0 && flowData.groupId <= 0 && flowData.demo === false)
		{
			incorrectData.push('groupId');
		}

		if (!Type.isNumber(flowData.templateId))
		{
			incorrectData.push('templateId');
		}

		if (flowData.id > 0 && flowData.ownerId <= 0 && flowData.demo === false)
		{
			incorrectData.push('ownerId');
		}

		if (Type.isNumber(flowData.notifyOnQueueOverflow) && flowData.notifyOnQueueOverflow <= 0)
		{
			incorrectData.push('notifyOnQueueOverflow');
		}

		if (Type.isNumber(flowData.notifyOnTasksInProgressOverflow) && flowData.notifyOnTasksInProgressOverflow <= 0)
		{
			incorrectData.push('notifyOnTasksInProgressOverflow');
		}

		if (
			Type.isNumber(flowData.notifyWhenEfficiencyDecreases)
			&& (
				flowData.notifyWhenEfficiencyDecreases <= 0
				|| flowData.notifyWhenEfficiencyDecreases > 100
			)
		)
		{
			incorrectData.push('notifyWhenEfficiencyDecreases');
		}

		return incorrectData;
	}

	#getFlow(flowData: Flow = {}): Flow
	{
		return {
			id: this.#params.flowId,
			demo: ('demo' in flowData) ? flowData.demo === true : this.#flow?.demo === true,
			...this.#pages.reduce((fields, page) => ({ ...fields, ...page.getFields(flowData) }), {}),
		};
	}

	#onContinueHandler(): Promise<boolean>
	{
		if (this.#pageChanging === true)
		{
			return Promise.resolve(false);
		}

		this.#pageChanging = true;

		const stepId = this.#wizard.getCurrentStep().id;
		const currentPage = this.#pages.find((page) => page.getId() === stepId);

		return currentPage?.onContinueClick(this.#flow)
			.then((canContinue: Boolean) => {
				this.#pageChanging = false;

				return canContinue;
			})
		;
	}
}
