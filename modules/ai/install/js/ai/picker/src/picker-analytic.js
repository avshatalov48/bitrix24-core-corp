import { ajax } from 'main.core';

type PickerAnalyticProps = {
	analyticLabel: string;
}
export class PickerAnalytic
{
	#analyticLabel: string;

	constructor(props: PickerAnalyticProps) {
		this.#analyticLabel = props.analyticLabel;
	}

	labels = Object.freeze({
		open: () => this.#putOpenLabel(),
		generate: (text: string) => this.#putGenerateLabel(text),
		copy: () => this.#putCopyLabel(),
		paste: () => this.#putPasteLabel(),
		cancel: () => this.#putCancelLabel(),
	});

	getAnalyticLabel(): string
	{
		return this.#analyticLabel;
	}

	setAnalyticLabel(analyticLabel: string)
	{
		this.#analyticLabel = analyticLabel;
	}

	#putOpenLabel()
	{
		this.#putLabel('open');
	}

	#putGenerateLabel(text: string)
	{
		const croppedText = text ? text.slice(0, 50) : '';

		this.#putLabel('generate', { text: croppedText });
	}

	#putCopyLabel(): void
	{
		return this.#putLabel('copy');
	}

	#putPasteLabel(): void
	{
		return this.#putLabel('past');
	}

	#putCancelLabel(): void
	{
		return this.#putLabel('cancel');
	}

	#putLabel(action: string, params: Object = {}): void
	{
		let url = '/bitrix/images/1.gif';

		const timestamp = Date.now();

		const data = {
			module: 'ai',
			context: this.#analyticLabel,
			action: `picker.${action}`,
			ts: timestamp,
			...params,
		};

		const preparedData = ajax.prepareData(data);

		if (preparedData)
		{
			url += (url.includes('?') ? '&' : '?') + preparedData;
		}

		ajax({
			method: 'GET',
			url,
		});
	}
}
