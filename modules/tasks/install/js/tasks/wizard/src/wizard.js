import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Button, ButtonState } from 'ui.buttons';
import 'main.polyfill.intersectionobserver';
import 'ui.icon-set.main';
import './style.css';

type Step = {
	id: string,
	title: string,
	content: HTMLElement,
	selected: ?boolean,
	isFilled: ?Function,
	titleNode: ?HTMLElement,
	node: ?HTMLElement,
	fadeTop: ?HTMLElement,
	fadeBottom: ?HTMLElement,
	hintManager?: BX.UI.Hint.Manager,
};

type Params = {
	steps: Step[],
	onCancel: Function,
	finishButton: Button,
	saveChangesButton: ?Button,
	article: ?number,
	onDisabledContinueButtonClick: ?Function,
	onContinueHandler: ?Function,
};

export class Wizard
{
	#params: Params;
	#layout: {
		backButton: Button,
		cancelButton: Button,
		continueButton: Button,
		finishButton: Button,
		saveChangesButton: Button,
	};

	#steps: Step[];

	constructor(params: Params)
	{
		this.#params = params;
		this.#steps = params.steps;
		this.#layout = {};
	}

	render(): HTMLElement
	{
		const wrap = Tag.render`
			<div class="tasks-wizard__container tasks-wizard__scope">
				${this.#renderStepHeader(this.#steps)}
				${this.#renderStepContainer(this.#steps)}
			</div>
		`;

		this.#openStep(this.#steps[0]);

		return wrap;
	}

	getCurrentStep(): Step
	{
		return this.#steps[this.#getCurrentStepIndex()];
	}

	update(): void
	{
		this.#updateStepsAvailability();
	}

	initHints(): void
	{
		this.#steps.forEach((step: Step) => {
			step.hintManager = top.BX.UI.Hint.createInstance({
				id: `tasks-flow-edit-form-${step.id}-${Text.getRandom()}`,
				className: 'skipInitByClassName',
				popupParameters: {
					targetContainer: step.node,
				},
			});
			step.hintManager.init(step.node);
		});
	}

	hideHints(): void
	{
		this.#steps.forEach((step: Step) => {
			step.hintManager?.hide();
		});
	}

	#getPreviousStep(): Step
	{
		return this.#steps[this.#getCurrentStepIndex() - 1];
	}

	#updateStepsAvailability(): void
	{
		let isAvailable = true;

		for (const step of this.#steps)
		{
			if (isAvailable)
			{
				Dom.removeClass(step.titleNode, '--unavailable');
			}
			else
			{
				Dom.addClass(step.titleNode, '--unavailable');
			}

			const isStepUnavailable = Type.isFunction(step.isFilled) && !step.isFilled();
			isAvailable = isAvailable && !isStepUnavailable;

			if (step.selected)
			{
				this.#layout.continueButton.setState(isAvailable ? null : ButtonState.DISABLED);
			}
		}

		this.#layout.finishButton.setState(isAvailable ? null : ButtonState.DISABLED);

		if (this.#layout.saveChangesButton)
		{
			this.#layout.saveChangesButton.setState(isAvailable ? null : ButtonState.DISABLED);
		}
	}

	#getCurrentStepIndex(): number
	{
		for (const [index, step] of this.#steps.entries())
		{
			if (step.selected)
			{
				return index;
			}
		}

		return 0;
	}

	#renderStepHeader(steps: Step[]): HTMLElement
	{
		const firstSteps = steps.slice(0, -1);
		const lastStep = steps.slice(-1)[0];

		return Tag.render`
			<div class="tasks-wizard__step_header">
				${firstSteps.map((step: Step) => this.#renderStepTitle(step, false))}
				${this.#renderStepTitle(lastStep, true)}
			</div>
		`;
	}

	#renderStepTitle(step: Step, isLast: boolean): HTMLElement
	{
		const arrow = isLast ? null : Tag.render`
			<div class="ui-icon-set --chevron-right" style="--ui-icon-set__icon-size: 15px;"></div>
		`;

		step.titleNode = Tag.render`
			<span
				class="tasks-wizard__step_name ${step.selected ? '--selected' : ''}"
				data-id="tasks-wizard-step-${step.id}"
			>${step.title}</span>
		`;

		Event.bind(step.titleNode, 'click', () => this.#openStep(step));

		return Tag.render`
			<span class="tasks-wizard__step_name-container">
				${step.titleNode}
				${arrow}
			</span>
		`;
	}

	#renderStepContainer(steps: Step[]): HTMLElement
	{
		return Tag.render`
			<div class="tasks-wizard__step_container">
				${steps.map((step: Step) => this.#renderStep(step))}
				${this.#renderButtonsContainer()}
			</div>
		`;
	}

	#renderButtonsContainer(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-wizard__step_buttons-container">
				${this.#renderBackButton()}
				${this.#renderCancelButton()}
				${this.#renderContinueButton()}
				${this.#renderFinishButton()}
				${this.#renderSaveChangesButton()}
				${this.#renderArticle()}
			</div>
		`;
	}

	#renderArticle(): HTMLElement | string
	{
		if (!Type.isNumber(this.#params.article))
		{
			return '';
		}

		const article = Tag.render`
			<div class="tasks-wizard__article">
				<span class="ui-icon-set --help"></span>
				${Loc.getMessage('TASKS_WIZARD_HELP')}
			</div>
		`;

		Event.bind(article, 'click', this.#openHelpDesk.bind(this));

		return article;
	}

	#openHelpDesk(): void
	{
		top.BX.Helper.show(`redirect=detail&code=${this.#params.article}`);
	}

	#renderStep(step: Step): HTMLElement
	{
		const fadeTop = Tag.render`
			<div class="tasks-wizard__step_fade --top"></div>
		`;

		const fadeBottom = Tag.render`
			<div class="tasks-wizard__step_fade"></div>
		`;

		step.node = Tag.render`
			<div class="tasks-wizard__step ${step.selected ? '--selected' : ''}">
				${fadeTop}
				${step.content}
				${fadeBottom}
			</div>
		`;

		const observer = new IntersectionObserver(() => {
			if (step.node.offsetWidth > 0)
			{
				this.#updateFade(step.node, fadeTop, fadeBottom);

				observer.disconnect();
			}
		});
		observer.observe(step.node);

		Event.bind(step.node, 'scroll', () => this.#updateFade(step.node, fadeTop, fadeBottom));

		this.#subscribeToPopupInit(step.node);

		return step.node;
	}

	#subscribeToPopupInit(stepContainer: HTMLElement): void
	{
		EventEmitter.subscribe('BX.Main.Popup:onInit', (event) => {
			const data = event.getCompatData();

			const bindElement = data[1];
			const params = data[2];

			if (
				Type.isDomNode(bindElement)
				&& stepContainer.contains(bindElement)
			)
			{
				params.targetContainer = stepContainer;
			}
		});
	}

	#updateFade(container, fadeTop, fadeBottom): void
	{
		const scrollTop = container.scrollTop;
		const maxScroll = container.scrollHeight - container.offsetHeight;
		const scrolledToBottom = Math.abs(scrollTop - maxScroll) < 1;

		if (scrollTop === 0)
		{
			Dom.removeClass(fadeTop, '--show');
		}
		else
		{
			Dom.addClass(fadeTop, '--show');
		}

		if (scrolledToBottom)
		{
			Dom.removeClass(fadeBottom, '--show');
		}
		else
		{
			Dom.addClass(fadeBottom, '--show');
		}
	}

	#renderBackButton(): HTMLElement
	{
		this.#layout.backButton = new Button({
			text: Loc.getMessage('TASKS_FLOW_EDIT_FORM_BACK'),
			color: Button.Color.LIGHT_BORDER,
			round: true,
			size: BX.UI.Button.Size.LARGE,
			onclick: () => {
				this.#openStep(this.#getPreviousStep());
			},
		});

		this.#layout.backButton.setDataSet({
			id: 'tasks-wizard-flow-back',
		});

		return this.#layout.backButton.render();
	}

	#renderCancelButton(): HTMLElement
	{
		this.#layout.cancelButton = new Button({
			text: Loc.getMessage('TASKS_FLOW_EDIT_FORM_CANCEL'),
			color: Button.Color.LIGHT_BORDER,
			round: true,
			size: BX.UI.Button.Size.LARGE,
			onclick: () => this.#params.onCancel(),
		});

		this.#layout.cancelButton.setDataSet({
			id: 'tasks-wizard-flow-cancel',
		});

		return this.#layout.cancelButton.render();
	}

	#renderContinueButton(): HTMLElement
	{
		this.#layout.continueButton = new Button({
			text: Loc.getMessage('TASKS_FLOW_EDIT_FORM_CONTINUE'),
			color: Button.Color.LIGHT_BORDER,
			round: true,
			size: BX.UI.Button.Size.LARGE,
			onclick: this.#onContinueButtonClickHandler.bind(this),
		});

		this.#layout.continueButton.setDataSet({
			id: 'tasks-wizard-flow-continue',
		});

		return this.#layout.continueButton.render();
	}

	async #onContinueButtonClickHandler(): void
	{
		if (this.#layout.continueButton.getState() === ButtonState.DISABLED)
		{
			this.#params.onDisabledContinueButtonClick?.();

			return;
		}

		if (Type.isFunction(this.#params.onContinueHandler) && this.#layout.continueButton.getState() === null)
		{
			const canContinue = await this.#params.onContinueHandler();
			if (!canContinue)
			{
				return;
			}
		}

		const currentStep = this.#getCurrentStepIndex();
		const nextStep = currentStep + 1;

		this.#openStep(this.#steps[nextStep]);
	}

	#renderFinishButton(): HTMLElement
	{
		this.#layout.finishButton = this.#params.finishButton;

		this.#layout.finishButton?.setDataSet({
			id: 'tasks-wizard-flow-finish',
		});

		return this.#layout.finishButton.render();
	}

	#renderSaveChangesButton(): ?HTMLElement
	{
		this.#layout.saveChangesButton = this.#params.saveChangesButton;

		this.#layout.saveChangesButton?.setDataSet({
			id: 'tasks-wizard-flow-save',
		});

		return this.#layout.saveChangesButton?.render();
	}

	#openStep(currentStep: Step): void
	{
		const index = this.#steps.findIndex((step: Step) => step.id === currentStep.id);

		this.#steps.forEach((step: Step) => {
			step.selected = false;
			Dom.removeClass(step.titleNode, '--selected');
			Dom.removeClass(step.node, '--selected');
		});

		currentStep.selected = true;
		Dom.addClass(currentStep.titleNode, '--selected');
		Dom.addClass(currentStep.node, '--selected');

		Dom.style(this.#layout.finishButton.getContainer(), 'display', 'none');
		Dom.style(this.#layout.continueButton.getContainer(), 'display', '');

		if (this.#layout.saveChangesButton)
		{
			Dom.style(this.#layout.saveChangesButton.getContainer(), 'display', '');
		}

		if (index === this.#steps.length - 1)
		{
			Dom.style(this.#layout.finishButton.getContainer(), 'display', '');
			Dom.style(this.#layout.continueButton.getContainer(), 'display', 'none');

			if (this.#layout.saveChangesButton)
			{
				Dom.style(this.#layout.saveChangesButton.getContainer(), 'display', 'none');
			}
		}

		if (index > 0)
		{
			Dom.style(this.#layout.backButton.getContainer(), 'display', '');
			Dom.style(this.#layout.cancelButton.getContainer(), 'display', 'none');
		}
		else
		{
			Dom.style(this.#layout.backButton.getContainer(), 'display', 'none');
			Dom.style(this.#layout.cancelButton.getContainer(), 'display', '');
		}

		this.#updateStepsAvailability();
	}
}
