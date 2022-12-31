export default class MoneyControl
{
	node: Element;
	hint: String|null;

	constructor(options)
	{
		this.node = options.node;
		this.hint = options.hint;
	}

	enable(): void
	{
		this.node.removeAttribute('disabled');
		this.node.removeAttribute('data-hint-no-icon');
		this.node.removeAttribute('data-hint');
		this.node.classList.remove('ui-ctl-element');

		const currencyBlock = this.node.querySelector('.main-grid-editor-money-currency');
		if (currencyBlock)
		{
			currencyBlock.classList.add('main-dropdown');
			currencyBlock.dataset.disabled = false;
		}

		this.node.querySelector('.main-grid-editor-money-price')?.removeAttribute('disabled');
	}

	disable(): void
	{
		this.node.setAttribute('disabled', '');
		this.node.classList.add('ui-ctl-element');
		this.node.querySelector('.main-grid-editor-money-price')?.setAttribute('disabled', '');

		const currencyBlock = this.node.querySelector('.main-grid-editor-money-currency');
		if (currencyBlock)
		{
			currencyBlock.classList.remove('main-dropdown');
			currencyBlock.dataset.disabled = true;
		}

		if (this.hint)
		{
			this.node.setAttribute('data-hint-no-icon', '');
			this.node.setAttribute('data-hint', this.hint);

			BX.UI.Hint.init(this.node.parentNode);
		}
	}
}
