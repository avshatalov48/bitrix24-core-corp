type Params = {
	input: HTMLInputElement,
	min: ?number,
	max: ?number,
};

class BindFilterNumberInput
{
	#params: Params;

	constructor(params: Params)
	{
		this.#params = params;

		this.#bind(params.input);
	}

	#bind(input: HTMLInputElement): void
	{
		let dispatchedProgrammatically = false;
		input.addEventListener('input', () => {
			if (dispatchedProgrammatically)
			{
				dispatchedProgrammatically = false;

				return;
			}

			const textBeforeCursor = this.#getTextBeforeCursor(input);

			input.value = this.#normalizeNumber(input.value) || '';

			this.#setCursorToFormattedPosition(input, textBeforeCursor);

			dispatchedProgrammatically = true;
			input.dispatchEvent(new Event('input'));
		});
	}

	#getTextBeforeCursor(input: HTMLInputElement): string
	{
		const selectionStart = input.selectionStart;
		const text = input.value.slice(0, selectionStart);

		return this.#normalizeNumber(text);
	}

	#normalizeNumber(value: string): string
	{
		let normalizedValue = value.replace(/\D/g, '');
		normalizedValue = parseInt(normalizedValue, 10);
		normalizedValue = Math.max(this.#params.min ?? 1, normalizedValue);
		normalizedValue = Math.min(this.#params.max ?? 9999, normalizedValue);

		return `${normalizedValue || ''}`;
	}

	#setCursorToFormattedPosition(input: HTMLInputElement, textBeforeCursor: string): void
	{
		const firstPart = textBeforeCursor.slice(0, -1);
		const lastCharacter = textBeforeCursor.slice(-1);
		const matches = input.value.match(`${firstPart}.*?${lastCharacter}`);
		if (!matches)
		{
			return;
		}

		const match = matches[0];
		const formattedPosition = input.value.indexOf(match) + match.length;
		input.setSelectionRange(formattedPosition, formattedPosition);
	}
}

const bindFilterNumberInput = (params: Params) => new BindFilterNumberInput(params);

export { bindFilterNumberInput };