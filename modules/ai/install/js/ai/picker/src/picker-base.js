import { Dom, bindOnce, Loc, Tag } from 'main.core';
import { Engine } from 'ai.engine';
import { AjaxErrorHandler } from 'ai.ajax-error-handler';
import { Base } from './ui/base';
import UI from './ui/index';
import { HistoryBase } from './ui/history-base';
import { TextMessage, TextMessageSubmitButtonIcon } from './ui/text-message';
import { Agreement } from 'ai.agreement';
import type { EngineAgreement } from 'ai.agreement';
import type { EngineInfo } from '../../copilot/src/types/engine-info';

export type PickerBaseProps = {
	onGenerate: Function;
	onLoadHistory: Function;
	onTariffRestriction: Function;
	onSelect: Function;
	startMessage?: string;
	engine: Engine;
	context: HTMLElement;
	engines: Array;
}

export class PickerBase extends Base
{
	onGenerate: Function | null;
	onLoadHistory: Function | null;
	onTariffRestriction: Function | null;
	onSelect: Function;
	startMessage: string;
	engine: Engine;
	context: HTMLElement;
	engines: Array;

	isResultSelected: boolean;
	isResultCopied: boolean;
	items: Array;
	capacity: number;
	history: HistoryBase;
	textMessage: TextMessage;
	isToolingLoading: boolean;
	historyContainer: HTMLElement;
	pickerType: 'text' | 'image';

	constructor(props: PickerBaseProps = {}) {
		super(props);

		this.onGenerate = props.onGenerate;
		this.onLoadHistory = props.onLoadHistory;
		this.onTariffRestriction = props.onTariffRestriction;
		this.startMessage = props.startMessage;
		this.engines = props.engines;
		this.items = [];
		this.capacity = 30;
		this.historyContainer = null;

		this.isToolingLoading = false;
		this.engine = props.engine;
		this.context = props.context;
		this.isResultCopied = false;
		this.isResultSelected = false;
		this.onSelect = props.onSelect;
		this.pickerType = '';

		this.setEventNamespace('AI:PickerBase');
	}

	render()
	{
		throw new Error('You must implement render method');
	}

	setEngineParameters(parameters)
	{
		if (this.engine)
		{
			this.engine.setParameters(parameters);
		}
	}

	setOnGenerate(onGenerate)
	{
		this.onGenerate = onGenerate;
	}

	setEngine(engine)
	{
		this.engine = engine;
	}

	setOnLoadHistory(onLoadHistory)
	{
		this.onLoadHistory = onLoadHistory;
	}

	setStartMessage(startMessage: string)
	{
		this.startMessage = startMessage;
	}

	setContext(context)
	{
		this.context = context;
	}

	isResultUsed(): boolean
	{
		return this.isResultCopied || this.isResultSelected;
	}

	resetResultUsedFlag(): void
	{
		this.isResultCopied = false;
		this.isResultSelected = false;
	}

	closeAllMenus(): void
	{
		this.textMessage.closeMenu();
	}

	async initTooling(category: string): void
	{
		this.isToolingLoading = true;

		if (this.textMessage)
		{
			this.textMessage.startLoading();
		}

		try
		{
			const res = await this.engine.getImagePickerTooling();

			this.engines = res.data.engines;
			this.items = res.data.history.items;
			this.capacity = res.data.history.capacity;

			if (this.textMessage)
			{
				this.textMessage.finishLoading();
				this.textMessage.focus();
			}
		}
		catch (err)
		{
			console.error(err);
			BX.UI.Notification.Center.notify({
				id: 'AI_JS_PICKER_INIT_ERROR',
				content: Loc.getMessage('AI_JS_PICKER_INIT_ERROR'),
				showOnTopWindow: true,
			});
		}
		finally
		{
			if (this.history)
			{
				this.history.items = this.items;
				Dom.style(this.historyContainer, 'opacity', 0);
				bindOnce(this.historyContainer, 'transitionend', () => {
					Dom.clean(this.historyContainer);
					Dom.append(this.history.render(), this.historyContainer);
					Dom.style(this.historyContainer, 'opacity', 1);
				});
			}

			if (this.textMessage)
			{
				this.textMessage.finishLoading();
			}

			this.isToolingLoading = false;
		}
	}

	renderTextMessage(): HTMLElement
	{
		this.initTextMessage();

		return Tag.render`
			<div class="ai__picker-text_message-field">
				${this.textMessage.render()}
			</div>
		`;
	}

	initTextMessage()
	{
		this.textMessage = new UI.TextMessage({
			message: this.startMessage,
			engines: this.engines,
			submitButtonIcon: this.getTextMessageSubmitButtonIcon(),
			hint: this.getHint(),
			context: this.context,
			isLoading: this.isToolingLoading,
		});

		this.textMessage.subscribe('submit', this.handleTextMessageSubmit.bind(this));
	}

	handleSelect(event)
	{
		this.isResultSelected = true;
		this.emit('select', { item: event.data.item });
	}

	handleCopy(event): void
	{
		this.isResultCopied = true;
		this.emit('copy', { item: event.data.item });
	}

	getTextMessageSubmitButtonIcon(): string
	{
		return TextMessageSubmitButtonIcon.PENCIL;
	}

	getHint(): Object | null
	{
		return null;
	}

	handleTextMessageSubmit(event)
	{
		const prompt = event.data.text;

		this.generate(prompt);
	}

	generate(prompt: string): void
	{
		this.textMessage.startLoading();

		this.history.generate(prompt)
			.then(() => {
				this.textMessage.finishLoading();
			})
			.catch((err) => {
				this.textMessage.finishLoading();
				const firstError = err.errors?.[0];

				if (this.#isAgreementError(firstError))
				{
					this.#handleAgreementError(firstError, prompt);
				}
				else if (
					firstError?.code === 'LIMIT_IS_EXCEEDED_MONTHLY'
					|| firstError?.code === 'LIMIT_IS_EXCEEDED_DAILY'
					|| firstError?.code === 'LIMIT_IS_EXCEEDED_BAAS'
					|| firstError?.code === 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF'
				)
				{
					AjaxErrorHandler.handleImageGenerateError({
						errorCode: firstError?.code,
						baasOptions: {
							bindElement: null,
							useSlider: true,
							context: 'notSet',
						},
					});

					this.textMessage.finishLoading();
				}
				else
				{
					this.handleGenerateFail();
				}
			});
	}

	#isAgreementError(err): boolean
	{
		return err?.code === 'AGREEMENT_IS_NOT_ACCEPTED';
	}

	#handleAgreementError(err, prompt: string): void
	{
		const agreementData: EngineAgreement = err.customData;
		const currentEngine = this.engines.find((e: EngineInfo) => e.selected);

		const agreement = new Agreement({
			agreement: {
				title: agreementData.title,
				text: agreementData.text,
				accepted: agreementData.accepted,
			},
			engineCode: currentEngine.code,
			engine: this.engine,
			type: this.pickerType,
		});

		agreement.showAgreementPopup(() => {
			this.generate(prompt);
		});
	}

	handleGenerateFail(): void
	{
		BX.UI.Notification.Center.notify({
			id: 'AI_JS_PICKER_TEXT_GENERATE_FAILED',
			content: Loc.getMessage('AI_JS_PICKER_TEXT_GENERATE_FAILED'),
			showOnTopWindow: true,
		});

		this.textMessage.finishLoading();
	}
}
