import { Tag, Loc } from 'main.core';
import { TextMessageSubmitButtonIcon } from './ui/text-message';
import { HistoryImage } from './ui/history-image';
import { PickerBase } from './picker-base';
import type { PickerBaseProps } from './picker-base';

export type PickerImageProps = PickerBaseProps | {};

export class PickerImage extends PickerBase
{
	constructor(props: PickerImageProps = {}) {
		super(props);

		this.pickerType = 'image';
		this.setEventNamespace('AI:PickerImage');
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker-image">
				${this.renderTextMessage()}
				${this.#renderHistory()}
			</div>
		`;
	}

	getTextMessageSubmitButtonIcon(): string
	{
		return TextMessageSubmitButtonIcon.BRUSH;
	}

	initHistory(): void
	{
		const generate = (prompt) => {
			const engine = this.engines.find((e) => e.selected);
			const engineCode = engine?.code ?? this.engines[0].code;

			return this.onGenerate(prompt, engineCode);
		};

		this.history = new HistoryImage({
			items: this.items,
			capacity: this.capacity,
			onGenerate: generate,
			onSelect: this.onSelect,
		});
	}

	#renderHistory(): HTMLElement
	{
		this.initHistory();

		this.historyContainer = Tag.render`
			<div class="ai__picker-image_history">
				${this.isToolingLoading ? this.#renderHistoryLoadingState() : this.history.render()}
			</div>
		`;

		return this.historyContainer;
	}

	#renderHistoryLoadingState(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker-text_history-loader">${Loc.getMessage('AI_JS_PICKER_HISTORY_LOADING')}</div>
		`;
	}

	getHint(): Object | null
	{
		if (Loc.getMessage('LANGUAGE_ID') !== 'en')
		{
			return {
				title: Loc.getMessage('AI_JS_PICKER_IMAGE_HINT_TITLE'),
				text: Loc.getMessage('AI_JS_PICKER_IMAGE_HINT_TEXT'),
			};
		}

		return null;
	}

	acceptAgreement(engineCode: string): Promise {
		return this.engine.acceptImageAgreement(engineCode);
	}

	isResultUsed(): boolean {
		return this.isResultSelected;
	}
}
