import { Dom, Loc, Tag, Text, Type } from 'main.core';
import { ProgressBar, ProgressBarOptions } from 'ui.progressbar';
import { Messages } from './deletion-manager';
import 'ui.design-tokens';

type Params = {
	entityIds: number[];
	containerId: string;
	onAfterTextClick?: Function;
	messages: Messages;
};

export class Panel
{
	#entityIds: number[] = [];
	#containerId: string;
	#progressBar: ProgressBar;
	#wrapper: ?HTMLElement;
	#messages: Messages;

	constructor(params: Params)
	{
		const { entityIds, containerId, onAfterTextClick, messages } = params;
		this.#entityIds = entityIds;
		this.#containerId = containerId;
		this.#messages = messages;

		const options = this.#getProgressBarOptions();
		if (Type.isFunction(onAfterTextClick))
		{
			options.clickAfterCallback = onAfterTextClick;
		}

		this.#progressBar = new ProgressBar(options);
	}

	#getProgressBarOptions(): ProgressBarOptions
	{
		const options = {
			value: 0,
			maxValue: 0,
			statusType: ProgressBar.Status.COUNTER,
			textBefore: Loc.getMessage(this.#messages.textBefore),
			textAfter: Loc.getMessage(this.#messages.textAfter),
			colorBar: '#ebcd2c',
			colorTrack: '#f2e59e',
		};

		if (Type.isArrayFilled(this.#entityIds))
		{
			options.maxValue = this.#entityIds.length;
		}

		return options;
	}

	render(): void
	{
		Dom.append(this.#getWrapperElement(), this.#getContainerElement());

		this.#progressBar.renderTo(this.#wrapper);
	}

	#getWrapperElement(): HTMLElement
	{
		if (!this.#wrapper)
		{
			this.#wrapper = Tag.render`<div class="recyclebin-list-grid-panel"></div>`;
		}

		return this.#wrapper;
	}

	setProgress(value: number, maxValue: ?number = null): Panel
	{
		if (maxValue !== null)
		{
			this.#progressBar.setMaxValue(maxValue);
		}

		this.#progressBar.update(value);

		return this;
	}

	showResult(errors: []): Panel
	{
		let textBefore = '';

		const successCount = this.#progressBar.getValue() - errors.length;

		if (successCount > 0)
		{
			textBefore += Loc.getMessage(
				this.#messages.successCount,
				{ '#COUNT#': successCount },
			);
		}

		const failedCount = errors.length;

		if (failedCount > 0)
		{
			if (textBefore !== '')
			{
				textBefore += '. ';
			}

			textBefore += Loc.getMessage(
				this.#messages.failedCount,
				{ '#COUNT#': failedCount },
			);

			this.#showErrors(errors);
		}

		this.#progressBar
			.setTextBefore(textBefore)
			.setClickAfterCallback(() => this.close())
			.setTextAfter(Loc.getMessage('RECYCLEBIN_DM_PROGRESSBAR_CLOSE'))
		;

		return this;
	}

	#showErrors(errors: []): void
	{
		errors.forEach((error) => {
			Dom.append(this.#createErrorElement(error), this.#wrapper);
		});
	}

	#createErrorElement(error: Object): HTMLElement
	{
		const { title } = error.customData.info;
		const { message } = error;

		return Tag.render`<div class="recyclebin-list-grid-panel-row">${Text.encode(title)}: ${Text.encode(message)}</div>`;
	}

	close(force: boolean = false): void
	{
		this.hide();

		if (force)
		{
			this.remove();

			return;
		}

		setTimeout(() => this.remove(), 400);
	}

	remove(): void
	{
		Dom.remove(this.#wrapper);
	}

	hide(): void
	{
		Dom.addClass(this.#wrapper, '--hidden');
	}

	#getContainerElement(): HTMLElement
	{
		return document.getElementById(this.#containerId);
	}
}
