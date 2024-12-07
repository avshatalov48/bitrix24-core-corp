import { Call } from 'crm.ai.call';
import { Slider } from 'crm.ai.slider';
import { addCustomEvent, Loc, removeAllCustomEvents, Type } from 'main.core';
import { Button } from 'ui.buttons';
import { AiFormFillApplication } from './app';
import SliderButtonsAdapter from './services/slider-buttons-adapter';

export let sliderButtonsAdapter: ?SliderButtonsAdapter = null;

export let copilotSliderInstance: ?Slider = null;

interface CreateOptions
{
	mergeUuid: string;
	label: string;
	activityId: number;
	activityDirection: string;
	ownerId: number;
	ownerTypeId: number;
	languageTitle?: string;
}

class ConflictFieldsliderCreator
{
	#options: CreateOptions;

	#copilotSliderClass: function;

	#app: AiFormFillApplication;

	#sliderInstance;

	constructor(options: CreateOptions, CopilotSliderWrapper: function)
	{
		this.#options = options;
		this.#copilotSliderClass = CopilotSliderWrapper;
		sliderButtonsAdapter = new SliderButtonsAdapter();
	}

	get #onLoadEventName(): string {
		return `CopilotSliderWrapper:onLoad_${this.#options.mergeUuid}`;
	}

	get #onCloseEventName(): string {
		return `CopilotSliderWrapper:onClose_${this.#options.mergeUuid}`;
	}

	get #sliderUrl(): string {
		return `crm:copilot-wrapper-slider-${this.#options.mergeUuid}`;
	}

	get #containerId(): string {
		return `crm-ai-merge-fields__container__${this.#options.mergeUuid}`;
	}

	create() {
		this.#sliderInstance = this.#createSliderWrapper();

		addCustomEvent('SidePanel.Slider:onLoad', this.#onSliderLoadFn.bind(this), this.#onLoadEventName);
		addCustomEvent('SidePanel.Slider:onClose', this.#onSliderCloseFn.bind(this), this.#onCloseEventName);
		addCustomEvent(window, 'BX.Crm.AiFormFill:CloseSlider', this.#onAiFormFillDownFn.bind(this));

		this.#sliderInstance.open();
	}

	#makeSliderToolbar(): Array {
		const toolbarButtons = this.#copilotSliderClass.makeDefaultToolbarButtons();

		const transcriptButton = new Button({
			text: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_TRANSCRIPTION'),
			size: Button.Size.MEDIUM,
			color: Button.Color.LIGHT_BORDER,
			dependOnTheme: true,
			onclick: () => {
				if (top.BX.Helper)
				{
					const transcription = new Call.Transcription({
						activityId: this.#options.activityId,
						ownerTypeId: this.#options.ownerTypeId,
						ownerId: this.#options.ownerId,
						languageTitle: this.#options.languageTitle,
					});

					transcription.open();
				}
			},
		});

		const resumeButton = new Button({
			text: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_RESUME'),
			size: Button.Size.MEDIUM,
			color: Button.Color.LIGHT_BORDER,
			dependOnTheme: true,
			onclick: () => {
				if (top.BX.Helper)
				{
					const resume = new Call.Summary({
						activityId: this.#options.activityId,
						ownerTypeId: this.#options.ownerTypeId,
						ownerId: this.#options.ownerId,
						languageTitle: this.#options.languageTitle,
					});

					resume.open();
				}
			},
		});

		return [
			transcriptButton,
			resumeButton,
			...toolbarButtons,
		];
	}

	#createSliderWrapper() {
		const buttons = sliderButtonsAdapter.getButtons();
		const toolbarButtons = this.#makeSliderToolbar();

		return new this.#copilotSliderClass({
			content: () => `<div id="${this.#containerId}"></div>`,
			sliderTitle: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_TITLE'),
			label: this.#options.label,
			extensions: ['crm.ai-form-fill', 'crm.entity-editor'],
			url: this.#sliderUrl,
			width: this.#calculateSliderWidth(),
			toolbar: () => toolbarButtons,
			buttons: () => buttons,
		});
	}

	#calculateSliderWidth(): number {
		const topSlider = BX.SidePanel.Instance.getTopSlider();
		const width = topSlider.getWidth() || (window.screen.width * 0.86);

		return Math.floor(width * 0.86);
	}

	#onSliderLoadFn(event) {
		if (event.getSlider().getUrl() !== this.#sliderUrl)
		{
			return;
		}
		copilotSliderInstance = this.#sliderInstance;

		this.#app = new AiFormFillApplication(
			this.#containerId,
			{
				mergeUuid: this.#options.mergeUuid,
				activityId: this.#options.activityId,
				activityDirection: this.#options.activityDirection,
			},
		);
		this.#app.start();
		removeAllCustomEvents('SidePanel.Slider:onLoad', this.#onLoadEventName);
	}

	#onSliderCloseFn(event) {
		if (event.getSlider().getUrl() !== this.#sliderUrl)
		{
			return;
		}

		if (!this.#app || this.#app.isAppLoading())
		{
			event.denyAction();

			return;
		}

		if (this.#app.isNeededShowCloseConfirm())
		{
			this.#app.showCloseConfirm();
			event.denyAction();

			return;
		}

		if (this.#app.isShowAiFeedbackBeforeClose())
		{
			this.#app.showAiFeedbackBeforeClose();
			event.denyAction();

			return;
		}

		removeAllCustomEvents('SidePanel.Slider:onClose', this.#onCloseEventName);
		removeAllCustomEvents(window, 'BX.Crm.AiFormFill:CloseSlider');
		if (this.#app)
		{
			this.#app.stop();
			this.#app = null;
		}
		sliderButtonsAdapter = null;
		copilotSliderInstance = null;
	}

	#onAiFormFillDownFn(event) {
		const mergeUuid = event?.data?.mergeUuid;
		if (mergeUuid === this.#options.mergeUuid)
		{
			this.#sliderInstance.close();
		}
	}
}

export const createAiFormFillApplicationInsideSlider = function(options: CreateOptions)
{
	const makeApp = (CopilotSliderWrapper: function) => {
		const creator = new ConflictFieldsliderCreator(options, CopilotSliderWrapper);
		creator.create();
	};

	if (Type.isFunction(BX?.Crm?.AI?.Slider))
	{
		makeApp(BX.Crm.AI.Slider);
	}
	else
	{
		top.BX.Runtime.loadExtension('crm.ai.slider')
			.then((exports) => {
				const { Slider } = exports;
				makeApp(Slider);
			})
			.catch(() => {
				throw new Error('Cant load Crm.AI.Slider extension');
			});
	}
};
