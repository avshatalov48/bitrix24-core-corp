import { Tag, Event, Loc, Dom, Text } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { Lottie } from 'ui.lottie';
import { SpeechConverter, speechConverterEvents } from 'ai.speech-converter';
import { CopilotHint } from './copilot-hint';
import 'ui.hint';
import { Icon, Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';

import { CopilotInputFieldTextarea } from './copilot-input-field-textarea';

import copilotLottieIcon from '../lottie/copilot-icon-1.json';

import { CopilotVoiceInputBtn } from './copilot-voice-input-btn';
import { CopilotInputError } from './copilot-input-error';
import { CopilotInputPlaceholder } from './copilot-input-placeholder';
import { CopilotSubmitBtn, CopilotSubmitBtnEvents } from './copilot-submit-btn';

import './css/copilot-input.css';

import type { CopilotInputErrorInfo } from './copilot-input-error';
import type {
	SpeechConverterErrorEventData,
	SpeechConverterResultEventData,
} from 'ai.speech-converter';

type CopilotInputOptions = {
	readonly: boolean;
	useForImages: boolean;
}

type CopilotInputContainer = {
	root: HTMLElement;
	icon: HTMLElement;
}

export const CopilotInputEvents = Object.freeze({
	submit: 'submit',
	cancelLoading: 'cancelLoading',
	focus: 'focus',
	input: 'input',
	goOutFromBottom: 'goOutFromBottom',
	startRecording: 'startRecording',
	stopRecording: 'stopRecording',
	adjustHeight: 'adjustHeight',
	containerClick: 'containerClick',
});

export class CopilotInput extends EventEmitter
{
	#textarea: CopilotInputFieldTextarea;
	#container: CopilotInputContainer;
	#isLoading: boolean;
	#placeholder: CopilotInputPlaceholder | null;
	#loaderTextContainer: HTMLElement;
	#errorContainer: HTMLElement | null;
	#inputError: CopilotInputError | null;
	#textareaOldValue: string = '';
	#disableEnterAndArrows: boolean = false;
	#copilotLottieAnimation: any = null;
	#lottieIconContainer: HTMLElement;
	#speechConverter: SpeechConverter | null = null;
	#submitBtn: CopilotSubmitBtn | null = null;
	#voiceButton: CopilotVoiceInputBtn | null = null;
	#readonly: boolean = false;
	#useForImages: boolean = false;
	#isGoOutFromBottomEnabled: boolean = true;
	#usedVoiceRecord: boolean;
	#usedTextInput: boolean;

	constructor(options: CopilotInputOptions = {})
	{
		super(options);
		this.#readonly = options.readonly === true;
		this.#isLoading = false;
		this.#errorContainer = null;
		this.#inputError = null;
		this.#copilotLottieAnimation = null;
		this.#lottieIconContainer = null;

		this.#usedVoiceRecord = false;
		this.#usedTextInput = false;
		this.setEventNamespace('AI.Copilot.Input');
	}

	render(): HTMLElement
	{
		this.#container = Tag.render`
			<div class="ai__copilot_input-field">
				<div ref="icon" class="ai__copilot_input-field-icon">
					${this.#renderInputIcon()}
				</div>
				${this.#renderLoader()}
				<div class="ai__copilot_input-field-content">
					${this.#renderTextArea()}
					${this.#renderPlaceholder()}
					${this.#renderErrorContainer()}
				</div>
				${this.#renderSubmitButton()}
				<div class="ai__copilot_input-field-baas-point"></div>
			</div>
		`;

		Event.bind(this.#container.root, 'click', () => {
			this.emit(CopilotInputEvents.containerClick);
		});

		this.#updateContainerClassname();

		return this.#container.root;
	}

	usedTextInput(): boolean
	{
		return this.#usedTextInput;
	}

	usedVoiceRecord(): boolean
	{
		return this.#usedVoiceRecord;
	}

	setValue(value: string): void
	{
		this.#setTextareaValue(value);
	}

	getValue(): string
	{
		return this.#textarea.value;
	}

	focus(setCursorAtStart: boolean): void
	{
		if (this.#textarea)
		{
			this.#textarea.focus(setCursorAtStart);
			this.#disableEnterAndArrows = false;
		}
	}

	getContainer(): HTMLElement
	{
		return this.#container?.root;
	}

	clear(): void
	{
		this.#setTextareaValue('', false);
		this.#voiceButton?.enable();
	}

	startGenerating(): void
	{
		this.#copilotLottieAnimation.play();
		this.clearErrors();
		this.enable();
		this.#textarea.disabled = true;
		this.#isLoading = true;
		this.#setTextareaValue('', false);
		Dom.addClass(this.getContainer(), '--loading');
		Dom.removeClass(this.getContainer(), '--error');
	}

	finishGenerating(): void
	{
		this.#textarea.disabled = false;
		this.#isLoading = false;
		this.#setTextareaValue(this.#textareaOldValue, false);
		Dom.removeClass(this.getContainer(), '--loading');
		setTimeout(() => {
			this.#copilotLottieAnimation.stop();
		}, 550);
	}

	stopRecording(): void
	{
		this.#speechConverter?.stop();
	}

	setErrors(errors: CopilotInputErrorInfo[]): void
	{
		Dom.clean(this.#errorContainer);
		if (this.#inputError)
		{
			this.#inputError.setErrors(errors);
		}
		else
		{
			this.#inputError = new CopilotInputError({
				errors,
			});
		}

		this.#setErrorIcon();

		Dom.addClass(this.getContainer(), '--error');
		const content = this.#inputError.render();
		Dom.append(content, this.#errorContainer);
		this.#textarea.disabled = true;
		requestAnimationFrame(() => {
			this.#adjustTextareaHeight();
		});
	}

	adjustHeight(): void
	{
		this.#adjustTextareaHeight();
	}

	#setErrorIcon(): void
	{
		this.#setIcon(this.#renderErrorIcon());
	}

	#setInputIcon(): void
	{
		this.#setIcon(this.#renderInputIcon());
	}

	#setIcon(icon: HTMLElement): void
	{
		if (icon.className === this.#getIconContainer().firstElementChild.className)
		{
			return;
		}

		Event.bindOnce(this.#getIconContainer(), 'transitionend', () => {
			this.#getIconContainer().innerHTML = '';
			Dom.append(icon, this.#getIconContainer());
			Dom.style(this.#getIconContainer(), 'opacity', 1);
		});

		Dom.style(this.#getIconContainer(), 'opacity', 0);
	}

	clearErrors(): void
	{
		if (this.#inputError && this.#inputError.getErrors().length > 0)
		{
			this.#inputError.setErrors([]);
			Dom.removeClass(this.getContainer(), '--error');
			this.#textarea.disabled = false;
			this.#setInputIcon();
		}

		this.#adjustTextareaHeight();
	}

	enableEnterAndArrows(): void
	{
		this.#disableEnterAndArrows = false;
	}

	disableEnterAndArrows(): void
	{
		this.#disableEnterAndArrows = true;
	}

	disable(): void
	{
		Dom.addClass(this.getContainer(), '--disabled');
		this.#textarea.disabled = true;
		Dom.style(this.getContainer(), 'opacity', 0.7);
		this.disableEnterAndArrows();
	}

	enable(): void
	{
		Dom.removeClass(this.getContainer(), '--disabled');
		this.#textarea.disabled = false;
		Dom.style(this.getContainer(), 'opacity', 1);
		this.enableEnterAndArrows();
	}

	isDisabled(): boolean
	{
		return this.#textarea.disabled;
	}

	setUseForImages(useForImages: boolean): void
	{
		this.#useForImages = useForImages;
		this.#placeholder.setUseForImages(useForImages);

		if (useForImages)
		{
			this.#isGoOutFromBottomEnabled = false;
			this.#loaderTextContainer.innerText = Loc.getMessage('AI_COPILOT_INPUT_IMAGE_LOADER_TEXT');
		}
		else
		{
			this.#isGoOutFromBottomEnabled = true;
			this.#loaderTextContainer.innerText = Loc.getMessage('AI_COPILOT_INPUT_LOADER_TEXT');
		}
	}

	#getIconContainer(): HTMLElement
	{
		return this.#container.icon;
	}

	#renderLoader(): HTMLElement
	{
		const cancelBtn = Tag.render`
			<button class="ai__copilot_loader-cancel-btn">
				${Loc.getMessage('AI_COPILOT_INPUT_LOADER_CANCEL')}
			</button>
		`;

		Event.bind(cancelBtn, 'click', () => {
			this.emit(CopilotInputEvents.cancelLoading);
		});

		const loader = Tag.render`
			<div class="ai__copilot_loader">
				<div class="ai__copilot_loader-left">
					<div ref="loaderText" class="ai__copilot_loader-text">${Loc.getMessage('AI_COPILOT_INPUT_LOADER_TEXT')}</div>
					<div class="ai__copilot_loader-dot dot-flashing"></div>
				</div>
				${cancelBtn}
			</div>
		`;

		this.#loaderTextContainer = loader.loaderText;

		return loader.root;
	}

	#renderErrorContainer(): HTMLElement
	{
		this.#errorContainer = Tag.render`
			<div class="ai__copilot_input-field-error-container"></div>
		`;

		return this.#errorContainer;
	}

	#renderTextArea(): HTMLTextAreaElement
	{
		this.#textarea = new CopilotInputFieldTextarea({});

		this.#textarea.subscribe('focus', () => {
			this.emit(CopilotInputEvents.focus);
		});

		this.#textarea.subscribe('input', (e) => {
			const value = e.getData();

			this.#setTextareaValue(value);
			if (this.#speechConverter && this.#speechConverter.isRecording() === false)
			{
				this.#usedTextInput = true;
				if (value)
				{
					this.#voiceButton.disable();
				}
				else
				{
					this.#voiceButton.enable();
				}
			}
		});

		const textAreaContainer = this.#textarea.render();

		Event.bind(textAreaContainer, 'keydown', this.#handleKeyDownEvent.bind(this));

		const observer = new MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				if (mutation.type === 'attributes' && mutation.attributeName === 'disabled')
				{
					if (this.#textarea.disabled === true)
					{
						Dom.style(textAreaContainer, 'z-index', -1);
					}
					else
					{
						Dom.style(textAreaContainer, 'z-index', 1);
					}
				}
			});
		});

		observer.observe(textAreaContainer, {
			attributes: true,
		});

		return textAreaContainer;
	}

	#handleKeyDownEvent(e: KeyboardEvent): boolean
	{
		if ((e.key === 'Enter' || this.#isArrowKey(e.key)) && this.#disableEnterAndArrows)
		{
			e.preventDefault();

			return false;
		}

		if (e.key === 'Enter')
		{
			return this.#handleEnterKeyDownEvent(e);
		}

		if (this.#isArrowKey(e.key))
		{
			return this.#handleArrowKeyDownEvent(e);
		}

		this.#disableEnterAndArrows = false;

		return true;
	}

	#isArrowKey(key: string): boolean
	{
		return key === 'ArrowDown' || key === 'ArrowUp' || key === 'ArrowLeft' || key === 'ArrowRight';
	}

	#handleEnterKeyDownEvent(e: KeyboardEvent): boolean
	{
		if (e.key === 'Enter'
			&& !e.shiftKey
			&& !e.altKey
			&& !e.ctrlKey
			&& !e.repeat
			&& !this.#isLoading
			&& !this.#textarea.disabled
		)
		{
			this.emit(CopilotInputEvents.submit);
			e.preventDefault();

			return false;
		}

		return true;
	}

	#handleArrowKeyDownEvent(e: KeyboardEvent): boolean
	{
		if (e.key === 'ArrowDown' && this.#isCursorInTextareaEnd() && this.#isGoOutFromBottomEnabled)
		{
			this.#disableEnterAndArrows = true;
			this.emit(CopilotInputEvents.goOutFromBottom);

			return false;
		}

		return true;
	}

	#isCursorInTextareaEnd(): boolean
	{
		return this.#textarea.isCursorInTheEnd();
	}

	#renderPlaceholder(): HTMLElement
	{
		this.#placeholder = new CopilotInputPlaceholder({
			readonly: this.#readonly,
			useForImages: this.#useForImages,
		});

		Event.bind(this.#placeholder.getContainer(), 'click', () => {
			if (this.#readonly === false)
			{
				this.#textarea.focus();
			}
		});

		return this.#placeholder.render();
	}

	#updateContainerClassname(): void
	{
		if (this.#textarea.value.length === 0)
		{
			Dom.addClass(this.getContainer(), '--show-placeholder');
		}
		else
		{
			Dom.removeClass(this.getContainer(), '--show-placeholder');
		}
	}

	#renderInputIcon(): HTMLElement
	{
		return Tag.render`
			<div class="" style="width: 24px; height: 24px; position: relative;">
				<div class="ai__copilot_static-icon-wrapper">
					<div class="ai__copilot_static-icon"></div>
				</div>
				<div class="ai__copilot_loading-icon-wrapper">
					${this.#getLottieIconContainer()}
				</div>
			</div>
		`;
	}

	#getLottieIconContainer(): HTMLElement
	{
		if (!this.#lottieIconContainer)
		{
			const size = 21;

			this.#lottieIconContainer = Tag.render`
				<div class="" style="width: ${size}px; height: ${size}px;"></div>
			`;

			this.#copilotLottieAnimation = Lottie.loadAnimation({
				container: this.#lottieIconContainer,
				renderer: 'svg',
				animationData: copilotLottieIcon,
				autoplay: false,
			});
		}

		return this.#lottieIconContainer;
	}

	#renderErrorIcon(): HTMLElement
	{
		const icon = new Icon({
			icon: Main.WARNING,
			size: 24,
		});

		return icon.render();
	}

	#renderSubmitButton(): HTMLElement | null {
		if (this.#readonly)
		{
			return null;
		}

		this.#initVoiceButton();
		this.#initSubmitButton();

		return Tag.render`
			<div class="ai__copilot_input-submit-block">
				${this.#submitBtn.render()}
				<div class="ai__copilot_input-submit-block-voice-btn">
					${this.#voiceButton.render()}
				</div>
			</div>
		`;
	}

	#initVoiceButton(): void
	{
		this.#voiceButton = new CopilotVoiceInputBtn();

		if (SpeechConverter.isBrowserSupport() === false)
		{
			this.#initDisabledVoiceButton();
		}
		else
		{
			this.#initEnabledVoiceButton();
		}
	}

	#initDisabledVoiceButton(): void
	{
		this.#voiceButton.disable();
		CopilotHint.addHintOnTargetHover({
			target: this.#voiceButton.getContainer(),
			text: Loc.getMessage('AI_COPILOT_VOICE_INPUT_NOT_SUPPORT'),
		});
	}

	#initEnabledVoiceButton(): void
	{
		this.#initSpeechConverter();

		Event.bind(this.#voiceButton.getContainer(), 'click', () => {
			if (this.#voiceButton.isDisabled() || this.#speechConverter.isRecording())
			{
				return;
			}

			this.#speechConverter.start();
		});

		this.#voiceButton.subscribe('stop', () => {
			this.#speechConverter.stop();
			if (this.#textarea.value)
			{
				this.#voiceButton.disable();
			}

			this.#usedVoiceRecord = true;
		});
	}

	#initSubmitButton(): void
	{
		this.#submitBtn = new CopilotSubmitBtn();

		this.#submitBtn.subscribe(CopilotSubmitBtnEvents.submit, () => {
			this.emit(CopilotInputEvents.submit);
		});
	}

	#initSpeechConverter(): void
	{
		if (SpeechConverter.isBrowserSupport() === false)
		{
			return;
		}

		this.#speechConverter = new SpeechConverter();

		this.#speechConverter.subscribe(speechConverterEvents.start, this.#handleSpeechConverterStartEvent.bind(this));
		this.#speechConverter.subscribe(speechConverterEvents.error, this.#handleSpeechConverterErrorEvent.bind(this));
		this.#speechConverter.subscribe(speechConverterEvents.result, this.#handleSpeechConverterResultEvent.bind(this));
		this.#speechConverter.subscribe(speechConverterEvents.stop, this.#handleSpeechConverterStopEvent.bind(this));
	}

	#handleSpeechConverterStartEvent(): void
	{
		this.#voiceButton.start();
		this.#textarea.disabled = true;
		this.#textarea.addClass('--recording');
		this.emit(CopilotInputEvents.startRecording);
		this.#submitBtn.disable();
	}

	#handleSpeechConverterErrorEvent(e: BaseEvent<SpeechConverterErrorEventData>): void
	{
		const { error } = e.getData();

		if (error === 'aborted')
		{
			return;
		}

		if (error === 'not-allowed')
		{
			this.#showErrorHintForVoiceButton(Loc.getMessage('AI_COPILOT_VOICE_INPUT_MICRO_NOT_ALLOWED'));
		}
		else
		{
			this.#showErrorHintForVoiceButton(Loc.getMessage('AI_COPILOT_VOICE_INPUT_UNKNOWN_ERROR'));
		}
	}

	#showErrorHintForVoiceButton(text: string)
	{
		const errorHint = new CopilotHint({
			text,
			target: this.#voiceButton.getContainer(),
		});

		errorHint.show();

		setTimeout(() => {
			errorHint.hide();
		}, 1500);
	}

	#handleSpeechConverterResultEvent(e: BaseEvent<SpeechConverterResultEventData>): void
	{
		this.setValue(e.getData().text);
		this.#textarea.value = e.getData().text;
	}

	#handleSpeechConverterStopEvent(): void
	{
		this.#textarea.removeClass('--recording');
		this.#textarea.disabled = false;
		this.#voiceButton.stop();
		this.#textarea.focus();
		this.emit(CopilotInputEvents.stopRecording);
		this.#submitBtn.enable();
	}

	#setTextareaValue(value: string, emitEvent: boolean = true): void
	{
		this.#textareaOldValue = this.#textarea.value;
		this.#adjustTextareaHeight();
		this.#updateContainerClassname();
		if (emitEvent)
		{
			this.emit(CopilotInputEvents.input, new BaseEvent({
				data: value,
			}));
		}
	}

	setHtmlContent(html: string): void
	{
		let htmlWithReplaced = Text.encode(html);

		htmlWithReplaced = htmlWithReplaced.replaceAll('[', '<strong>[');
		htmlWithReplaced = htmlWithReplaced.replaceAll(']', ']</strong>');
		htmlWithReplaced = htmlWithReplaced.replaceAll('\n', '<br />');

		this.#textarea.setHtmlContent(htmlWithReplaced);
	}

	#adjustTextareaHeight(): void
	{
		this.#textarea.setStyle('height', 'auto');
		const textAreaPaddingBottom = parseInt(this.#textarea.getComputedStyle().getPropertyValue('padding-bottom'), 10);
		const errorFieldHeight = Dom.getPosition(this.#errorContainer).height;
		const placeholderHeight = Dom.getPosition(this.#placeholder.getContainer()).height;
		const textAreaHeight = this.#textarea.scrollHeight;
		const hasTextAreaMoreThanOneRow = textAreaHeight - textAreaPaddingBottom > 40;

		if (hasTextAreaMoreThanOneRow)
		{
			this.#textarea.addClass('--with-padding-bottom');
		}
		else
		{
			this.#textarea.removeClass('--with-padding-bottom');
		}

		if (this.#isLoading)
		{
			const loaderHeight = Dom.getPosition(this.#loaderTextContainer).height;

			this.#textarea.setStyle('height', `${loaderHeight}px`);
		}
		else
		{
			let newTextAreaHeight = this.#inputError && this.#inputError.getErrors().length > 0
				? errorFieldHeight
				: this.#textarea.scrollHeight;

			if (placeholderHeight > newTextAreaHeight && !this.#textarea.value)
			{
				newTextAreaHeight = placeholderHeight;
			}

			this.#textarea.setStyle('height', `${newTextAreaHeight}px`);
		}

		this.emit(CopilotInputEvents.adjustHeight);
	}
}
