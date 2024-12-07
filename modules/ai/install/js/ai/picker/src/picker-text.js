import { Tag, Loc } from 'main.core';
import UI from './ui/index';
import { PickerBase } from './picker-base';
import type { PickerBaseProps } from './picker-base';
import './css/picker-text.css';

export type Agreement = {
	title: string;
	text: string;
	accepted: boolean;
}

type PickerTextProps = PickerBaseProps | {
	onCopy: Function;
};

export class PickerText extends PickerBase
{
	#onCopy: Function;

	constructor(props: PickerTextProps = {}) {
		super(props);

		this.#onCopy = props.onCopy;
		this.pickerType = 'text';

		this.setEventNamespace('AI:PickerText');
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker-text">
				${this.renderTextMessage()}
				${this.#renderHistory()}
			</div>
		`;
	}

	#renderHistory(): HTMLElement {
		this.initHistory();

		this.historyContainer = Tag.render`
			<div class="ai__picker-text_history">
				${this.isToolingLoading ? this.#renderHistoryLoadingState() : this.history.render()}
			</div>
		`;

		return this.historyContainer;
	}

	initHistory(): void
	{
		const generate = (prompt) => {
			const engine = this.engines.find((e) => e.selected);
			const engineCode = engine?.code ?? this.engines[0].code;

			return this.onGenerate(prompt, engineCode);
		};

		this.history = new UI.HistoryText({
			items: this.items,
			capacity: this.capacity,
			onGenerate: generate,
			onSelect: this.onSelect,
			onCopy: this.#onCopy,
		});
	}

	#renderHistoryLoadingState(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker-text_history-loader">${Loc.getMessage('AI_JS_PICKER_HISTORY_LOADING')}</div>
		`;
	}

	async acceptAgreement(engineCode: string): Promise {
		return this.engine.acceptTextAgreement(engineCode);
	}
}
