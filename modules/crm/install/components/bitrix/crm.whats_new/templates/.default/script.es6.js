import { clone, Dom, Reflection, Runtime, Tag, Type } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { Button } from 'ui.buttons';
import { EventEmitter } from "main.core.events";

const namespaceCrmWhatsNew = Reflection.namespace('BX.Crm.WhatsNew');

type SlideConfig = {
	title: string,
	innerImage: string,
	innerTitle: string,
	innerDescription: string,
	buttons: Array<ButtonConfig>,
};

type ButtonConfig = {
	text: string,
	className: string,
	onClickClose: ?boolean,
	helpDeskCode: ?string,
}

type Slide = {
	title: string,
	className: ?string,
	html: string,
}

type StepPosition = 'left' | 'right'; //it's bottom by default

type StepConfig = {
	id: string,
	title: string,
	text: string,
	position: ?StepPosition,
	target: ?string,
	useDynamicTarget: ?boolean,
	eventName: ?string,
	article: ?number
}

type Step = {
	id: string,
	title: string,
	text: string,
	position: ?StepPosition,
	target: ?string,
}

type Option = {
	showOverlayFromFirstStep?: boolean,
	hideTourOnMissClick?: boolean,
	...
}

class ActionViewMode
{
	slides: Array<Slide>;
	steps: Array<Step>;
	closeOptionName: string;
	closeOptionCategory: string;
	options: Option;
	popup;

	constructor({ slides, steps, options, closeOptionCategory, closeOptionName })
	{
		this.popup = null;
		this.slides = [];
		this.steps = [];
		this.options = options;
		this.slideClassName = 'crm-whats-new-slides-wrapper';
		this.closeOptionCategory = Type.isString(closeOptionCategory) ? closeOptionCategory : '';
		this.closeOptionName = Type.isString(closeOptionName) ? closeOptionName : '';
		this.onClickClose = this.onClickCloseHandler.bind(this);

		this.whatNewPromise = null;
		this.tourPromise = null;

		this.prepareSlides(slides);
		this.prepareSteps(steps);
	}

	prepareSlides(slideConfigs: Array<SlideConfig>): void
	{
		if (slideConfigs.length)
		{
			this.whatNewPromise = Runtime.loadExtension('ui.dialogs.whats-new');
		}

		this.slides = slideConfigs.map((slideConfig: SlideConfig) => {
			return {
				className: this.slideClassName,
				title: slideConfig.title,
				html: this.getPreparedSlideHtml(slideConfig),
			};
		}, this);
	}

	getPreparedSlideHtml(slideConfig: SlideConfig): HTMLElement
	{
		const slide = Tag.render`
			<div class="crm-whats-new-slide">
				<img src="${slideConfig.innerImage}" alt="">
				<div class="crm-whats-new-slide-inner-title"> ${slideConfig.innerTitle} </div>
				<p>${slideConfig.innerDescription}</p>
			</div>
		`;

		const buttons = this.getPrepareSlideButtons(slideConfig);
		if (buttons.length)
		{
			const buttonsContainer =  Tag.render`<div class="crm-whats-new-slide-buttons"></div>`;
			Dom.append(buttonsContainer, slide);

			buttons.forEach(button => {
				Dom.append(button.getContainer(), buttonsContainer);
			});
		}

		return slide;
	}

	getPrepareSlideButtons(slideConfig: SlideConfig): Button[]
	{
		let buttons = [];
		if (slideConfig.buttons)
		{
			const className = 'ui-btn ui-btn-primary ui-btn-hover ui-btn-round ';

			buttons = slideConfig.buttons.map((buttonConfig) => {
				const config = {
					className: className + (buttonConfig.className ?? ''),
					text: buttonConfig.text,
				};

				if (buttonConfig.onClickClose)
				{
					config.onclick = () => this.onClickClose();
				}
				else if (buttonConfig.helpDeskCode)
				{
					config.onclick = () => this.showHelpDesk(buttonConfig.helpDeskCode);
				}

				return new Button(config);
			}, this);
		}

		return buttons;
	}

	prepareSteps(stepsConfig)
	{
		if (stepsConfig.length)
		{
			this.tourPromise = Runtime.loadExtension('ui.tour');
		}

		this.steps = stepsConfig.map((stepConfig: StepConfig) => {
			const step = {
				id: stepConfig.id,
				title: stepConfig.title,
				text: stepConfig.text,
				position: stepConfig.position,
				article: stepConfig.article,
			};

			if (stepConfig.useDynamicTarget)
			{
				const eventName = (stepConfig.eventName ?? this.getDefaultStepEventName(step.id));
				EventEmitter.subscribeOnce(eventName, this.showStepByEvent.bind(this));
			}
			else
			{
				step.target = stepConfig.target;
			}

			return step;

		}, this);
	}

	showStepByEvent(event): void
	{
		this.tourPromise.then((exports) => {
			const { stepId, target, delay }  = event.data;
			const step = this.steps.find(step => step.id === stepId);
			if (!step)
			{
				console.error('step not found');
				return;
			}

			setTimeout(() => {
				step.target = target;
				const { Guide } = exports;
				const guide = this.createGuideInstance(Guide, [step], true);

				this.setStepPopupOptions(guide.getPopup());
				guide.showNextStep();
				this.save();

			}, delay || 0);
		});
	}

	getDefaultStepEventName(stepId: string): string
	{
		return `Crm.WhatsNew::onTargetSetted::${stepId}`;
	}

	onClickCloseHandler(): void
	{
		const lastPosition = this.popup.getLastPosition();
		const currentPosition = this.popup.getPositionBySlide(this.popup.getCurrentSlide());
		if (currentPosition >= lastPosition)
		{
			this.popup.destroy();
		}
		else
		{
			this.popup.selectNextSlide();
		}
	}

	showHelpDesk(code: string): void
	{
		if(top.BX.Helper)
		{
			top.BX.Helper.show(`redirect=detail&code=${code}`);
			event.preventDefault();
		}
	}

	show(): void
	{
		if (this.slides.length)
		{
			this.executeWhatsNew();
		}
		else if (this.steps.length)
		{
			this.executeGuide();
		}
	}

	executeWhatsNew(): void
	{
		if (PopupManager && PopupManager.isAnyPopupShown())
		{
			return;
		}

		this.whatNewPromise.then(exports => {
			const { WhatsNew } = exports;
			this.popup = new WhatsNew({
				slides: this.slides,
				popupOptions: {
					height: 440,
				},
				events: {
					onDestroy: () => {
						this.save();
						this.executeGuide();
					},
				},
			});

			this.popup.show();

			ActionViewMode.whatsNewInstances.push(this.popup);
		}, this);
	}

	executeGuide(): void
	{
		let steps = clone(this.steps);
		steps = steps.filter(step => Boolean(step.target));
		if (!steps.length)
		{
			return;
		}

		this.tourPromise.then((exports) => {
			const { Guide } = exports;
			const guide = this.createGuideInstance(Guide, steps, (this.steps.length <= 1));

			if (ActionViewMode.tourInstances.find((existedGuide) => existedGuide.getPopup()?.isShown()))
			{
				return; // do not allow many guides at the same time
			}
			ActionViewMode.tourInstances.push(guide);

			this.setStepPopupOptions(guide.getPopup());

			if (guide.steps.length > 1 || this.options.showOverlayFromFirstStep)
			{
				guide.start();
			}
			else
			{
				guide.showNextStep();
			}
			this.save();
		});
	}

	createGuideInstance(guide, steps: Array<Step>, onEvents: boolean)
	{
		return new guide({
			onEvents,
			steps: steps,
			events: {
				onFinish: () => {
					if (!this.slides.length)
					{
						this.save();
					}
				},
			},
		});
	}

	setStepPopupOptions(popup: Popup)
	{
		const { steps, hideTourOnMissClick = false } = this.options;

		popup.setAutoHide(hideTourOnMissClick);
		if (steps && steps.popup)
		{
			if (steps.popup.width)
			{
				popup.setWidth(steps.popup.width)
			}
		}
	}

	save(): void
	{
		BX.userOptions.save(this.closeOptionCategory, this.closeOptionName, 'closed', 'Y');
	}

	static tourInstances = [];
	static whatsNewInstances = [];
}

namespaceCrmWhatsNew.ActionViewMode = ActionViewMode;
