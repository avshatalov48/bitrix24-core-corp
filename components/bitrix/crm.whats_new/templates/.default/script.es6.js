import { BannerDispatcher } from 'crm.integration.ui.banner-dispatcher';
import { ajax as Ajax, clone, Dom, Reflection, Runtime, Tag, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup, PopupManager } from 'main.popup';
import { Button } from 'ui.buttons';
import { Guide as UIGuide } from 'ui.tour';

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

type StepPosition = 'left' | 'right'; // it's bottom by default

type StepConfig = {
	id: string,
	title: string,
	text: string,
	position: ?StepPosition,
	target: ?string,
	reserveTargets: ?string[],
	useDynamicTarget: ?boolean,
	eventName: ?string,
	article: ?number,
	articleAnchor?: string,
	infoHelperCode: ?string,
	ignoreIfTargetNotFound: ?boolean,
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
	numberOfViewsLimit:	number,
	isNumberOfViewsExceeded?: boolean,
	disableBannerDispatcher?: boolean,
	additionalTourIdsForDisable?: Array<string>,
	...
}

class ActionViewMode
{
	#slides: Array<Slide>;
	#steps: Array<Step>;
	#options: Option;

	#popup: ?Popup;
	#bannerDispatcher: ?BannerDispatcher = null;

	#closeOptionName: string;
	#closeOptionCategory: string;

	#isViewHappened: boolean = false;

	constructor({ slides, steps, options, closeOptionCategory, closeOptionName })
	{
		this.#slides = [];
		this.#steps = [];

		this.#options = options;
		this.#popup = null;
		this.slideClassName = 'crm-whats-new-slides-wrapper';
		this.#closeOptionCategory = Type.isString(closeOptionCategory) ? closeOptionCategory : '';
		this.#closeOptionName = Type.isString(closeOptionName) ? closeOptionName : '';
		this.onClickClose = this.onClickCloseHandler.bind(this);

		this.whatNewPromise = null;
		this.tourPromise = null;

		this.#prepareSlides(slides);
		this.#prepareSteps(steps);
	}

	show(): void
	{
		if (this.#options.isNumberOfViewsExceeded)
		{
			// eslint-disable-next-line no-console
			console.warn('Number of views exceeded');

			return;
		}

		if (this.#slides.length > 0)
		{
			this.#executeWhatsNew();
		}
		else if (this.#steps.length > 0)
		{
			this.#executeGuide();
		}
	}

	#prepareSlides(slideConfigs: Array<SlideConfig>): void
	{
		if (slideConfigs.length > 0)
		{
			this.whatNewPromise = Runtime.loadExtension('ui.dialogs.whats-new');
		}

		this.#slides = slideConfigs.map((slideConfig: SlideConfig) => {
			return {
				className: this.slideClassName,
				title: slideConfig.title,
				html: this.#getPreparedSlideHtml(slideConfig),
			};
		});
	}

	#getPreparedSlideHtml(slideConfig: SlideConfig): HTMLElement
	{
		const slide = Tag.render`
			<div class="crm-whats-new-slide">
				<img src="${slideConfig.innerImage}" alt="">
				<div class="crm-whats-new-slide-inner-title"> ${slideConfig.innerTitle} </div>
				<p>${slideConfig.innerDescription}</p>
			</div>
		`;

		const buttons = this.#getPrepareSlideButtons(slideConfig);
		if (buttons.length > 0)
		{
			const buttonsContainer = Tag.render`<div class="crm-whats-new-slide-buttons"></div>`;
			Dom.append(buttonsContainer, slide);

			buttons.forEach((button) => {
				Dom.append(button.getContainer(), buttonsContainer);
			});
		}

		return slide;
	}

	async #getBannerDispatcher(): Promise<BannerDispatcher>
	{
		if (this.#bannerDispatcher)
		{
			return this.#bannerDispatcher;
		}

		const { BannerDispatcher: Dispatcher } = await Runtime.loadExtension('crm.integration.ui.banner-dispatcher');
		this.#bannerDispatcher = new Dispatcher();

		return this.#bannerDispatcher;
	}

	#getPrepareSlideButtons(slideConfig: SlideConfig): Button[]
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
					config.onclick = () => this.#showHelpDesk(buttonConfig.helpDeskCode);
				}

				return new Button(config);
			});
		}

		return buttons;
	}

	#prepareSteps(stepsConfig): void
	{
		this.#steps = stepsConfig.map((stepConfig: StepConfig) => {
			const step = {
				id: stepConfig.id,
				title: stepConfig.title,
				text: stepConfig.text,
				position: stepConfig.position,
				article: stepConfig.article,
				articleAnchor: stepConfig.articleAnchor ?? null,
				infoHelperCode: stepConfig.infoHelperCode,
			};

			if (stepConfig.useDynamicTarget)
			{
				const eventName = (stepConfig.eventName ?? this.#getDefaultStepEventName(step.id));
				EventEmitter.subscribeOnce(eventName, this.#showStepByEvent.bind(this));
			}
			else
			{
				const target = document.querySelector(stepConfig.target);
				if (target && Dom.style(target, 'display') !== 'none')
				{
					step.target = stepConfig.target;
				}
				else if (Type.isArrayFilled(stepConfig.reserveTargets))
				{
					const isFound = stepConfig.reserveTargets.some((reserveTarget) => {
						if (document.querySelector(reserveTarget))
						{
							step.target = reserveTarget;

							return true;
						}

						return false;
					});

					if (!isFound && stepConfig.ignoreIfTargetNotFound)
					{
						return null;
					}
				}
				else if (stepConfig.ignoreIfTargetNotFound)
				{
					return null;
				}
				else
				{
					step.target = stepConfig.target;
				}
			}

			return step;
		});

		this.#steps = this.#steps.filter((step: ?Object) => step !== null);
		if (this.#steps.length > 0)
		{
			this.tourPromise = Runtime.loadExtension('ui.tour');
		}
	}

	#showStepByEvent(event: BaseEvent): void
	{
		const { disableBannerDispatcher = false } = this.#options;

		void this.tourPromise.then((exports) => {
			const { stepId, target } = event.data;
			// eslint-disable-next-line no-shadow
			const step = this.#steps.find((step) => step.id === stepId);
			if (!step)
			{
				console.error('step not found');

				return;
			}

			step.target = target;
			const { Guide } = exports;
			const guide = this.createGuideInstance(Guide, [step], true);

			this.setStepPopupOptions(guide.getPopup());

			if (disableBannerDispatcher === false)
			{
				this.#runGuideWithBannerDispatcher(guide);
			}
			else
			{
				this.#runGuide(guide);
			}
		});
	}

	#runGuideWithBannerDispatcher(guide: Object): void
	{
		void this.#getBannerDispatcher().then((dispatcher: BannerDispatcher) => {
			dispatcher.toQueue((onDone: Function) => {
				this.#runGuide(guide, onDone);
			});
		});
	}

	#onGuideFinish(guide: Object, onDone: Function = null): void
	{
		guide.subscribe('UI.Tour.Guide:onFinish', () => {
			this.save();
			if (onDone)
			{
				onDone();
			}
		});
	}

	#runGuide(guide: Object, onDone: Function = null): void
	{
		this.#onGuideFinish(guide, onDone);
		guide.showNextStep();
	}

	#getDefaultStepEventName(stepId: string): string
	{
		return `Crm.WhatsNew::onTargetSetted::${stepId}`;
	}

	#isMultipleViewsAllowed(): boolean
	{
		return this.#options.numberOfViewsLimit > 1;
	}

	onClickCloseHandler(): void
	{
		const lastPosition = this.#popup.getLastPosition();
		const currentPosition = this.#popup.getPositionBySlide(this.#popup.getCurrentSlide());
		if (currentPosition >= lastPosition)
		{
			this.#popup.destroy();
		}
		else
		{
			this.#popup.selectNextSlide();
		}
	}

	#showHelpDesk(code: string): void
	{
		if (top.BX.Helper)
		{
			top.BX.Helper.show(`redirect=detail&code=${code}`);
			event.preventDefault();
		}
	}

	#executeWhatsNew(): void
	{
		const { disableBannerDispatcher = false } = this.#options;

		if (PopupManager && PopupManager.isAnyPopupShown())
		{
			return;
		}

		void this.whatNewPromise.then((exports) => {
			const { WhatsNew } = exports;
			this.#popup = new WhatsNew({
				slides: this.#slides,
				popupOptions: {
					height: 440,
				},
				events: {
					onDestroy: () => {
						this.save();
						this.#executeGuide(false);
					},
				},
			});

			if (disableBannerDispatcher === false)
			{
				// eslint-disable-next-line promise/no-nesting
				void this.#getBannerDispatcher().then((dispatcher: BannerDispatcher) => {
					dispatcher.toQueue((onDone: Function) => {
						this.#popup.subscribe('onDestroy', onDone);
						this.#popup.show();
					});
				});
			}
			else
			{
				this.#popup.show();
			}

			ActionViewMode.whatsNewInstances.push(this.#popup);
		}, this);
	}

	#executeGuide(isAddToQueue: boolean = true): void
	{
		const { disableBannerDispatcher = false } = this.#options;
		let steps = clone(this.#steps);

		steps = steps.filter((step) => Boolean(step.target));

		if (steps.length === 0)
		{
			return;
		}

		void this.tourPromise.then((exports) => {
			if (ActionViewMode.tourInstances.some((existedGuide) => existedGuide.getPopup()?.isShown()))
			{
				return; // do not allow many guides at the same time
			}

			if (PopupManager && PopupManager.isAnyPopupShown())
			{
				return;
			}

			const { Guide } = exports;
			const guide = this.createGuideInstance(Guide, steps, (this.#steps.length <= 1));
			ActionViewMode.tourInstances.push(guide);

			this.setStepPopupOptions(guide.getPopup());

			if (isAddToQueue)
			{
				if (disableBannerDispatcher === false)
				{
					this.#runGuideWithBannerDispatcher(guide);
				}
				else
				{
					this.#runGuide(guide);
				}

				return;
			}

			this.#showGuide(guide);
		});
	}

	#showGuide(guide: UIGuide): void
	{
		if (guide.steps.length > 1 || this.#options.showOverlayFromFirstStep)
		{
			guide.start();
		}
		else
		{
			guide.showNextStep();
		}
	}

	createGuideInstance(Guide, steps: Array<Step>, onEvents: boolean): Object
	{
		return new Guide({
			onEvents,
			steps,
			events: {
				onFinish: () => {
					if (this.#slides.length === 0)
					{
						this.save();
					}
				},
			},
		});
	}

	setStepPopupOptions(popup: Popup): void
	{
		const { steps, hideTourOnMissClick = false } = this.#options;

		popup.setAutoHide(hideTourOnMissClick);

		if (steps?.popup?.width)
		{
			popup.setWidth(steps.popup.width);
		}
	}

	save(): void
	{
		Ajax.runAction('crm.settings.tour.updateOption', {
			json: {
				category: this.#closeOptionCategory,
				name: this.#closeOptionName,
				options: {
					isMultipleViewsAllowed: !this.#isViewHappened && this.#isMultipleViewsAllowed(),
					numberOfViewsLimit: this.#options.numberOfViewsLimit ?? 1,
					additionalTourIdsForDisable: this.#options.additionalTourIdsForDisable ?? null,
				},
			},
		}).then(({ data }) => {
			this.#options.isNumberOfViewsExceeded = data.isNumberOfViewsExceeded;
			this.#isViewHappened = true;
		}).catch((errors) => {
			console.error('Could not save tour settings', errors);
		});
	}

	static tourInstances = [];
	static whatsNewInstances = [];
}

namespaceCrmWhatsNew.ActionViewMode = ActionViewMode;
