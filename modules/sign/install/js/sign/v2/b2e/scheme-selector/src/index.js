import { Loc, Tag, Text, Dom, Type } from 'main.core';
import './style.css';
import { Api } from 'sign.v2.api';

export type Scheme = 'order' | 'default';
export const SchemeType: Readonly<Record<string, Scheme | 'unset'>> = Object.freeze({
	Order: 'order',
	Default: 'default',
	Unset: 'unset',
});

export class SchemeSelector
{
	#ui = {
		container: HTMLElement = null,
		select: HTMLSelectElement = null,
	};

	#selectedType: string = SchemeType.Unset;
	#availableSchemes: Array<string> = [];

	#api = new Api();

	getLayout(): HTMLElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container
		}

		this.#ui.select = Tag.render`
			<select
				class="ui-ctl-element"
				onchange="${({ target: select }) => {
					this.#selectItem(select.options[select.selectedIndex].value);
				}}"
			>
		`;

		this.#ui.container = Tag.render`
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown sign-b2e-scheme-selector__dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				${this.#ui.select}
			</div>
		`;

		return this.#ui.container;
	}

	validate(): boolean
	{
		Dom.removeClass(this.#ui.container, '--invalid');
		const result = this.#selectedType !== SchemeType.Unset;
		if (!result)
		{
			Dom.addClass(this.#ui.container, '--invalid');
		}

		return this.#selectedType !== SchemeType.Unset;
	}

	async save(documentId: string): Promise<void>
	{
		await this.#api.modifyB2eDocumentScheme(documentId, this.#selectedType);
	}

	async load(documentId: string): Promise<void>
	{
		const { schemes } = await this.#api.loadB2eAvaialbleSchemes(documentId);
		const filteredSchemes = schemes.filter((scheme: string) => this.#isValidScheme(scheme));
		if (!Type.isArrayFilled(filteredSchemes))
		{
			this.#selectItem(SchemeType.Unset);
		}

		this.#availableSchemes = filteredSchemes;
		this.#renderSelectOptions();

		this.#selectItem(this.#availableSchemes[0]);
	}


	getSelectedType(): string
	{
		return this.#selectedType;
	}

	#renderSelectOptions(): void
	{
		this.#ui.select.innerHTML = '';

		const optionElements = this.#getAvailableSchemes().map(
			(option: { value: string, text: string }) =>
				Tag.render`
					<option value="${option.value}">
						${Text.encode(option.text)}
					</option>
				`
		);

		optionElements.forEach((element: HTMLElement) => Dom.append(element, this.#ui.select));
		this.#ui.select.selectedIndex = optionElements.length > 0 ? 1 : 0;
	}

	#getSchemeOptions(): [{ value: string, text: string }]
	{
		return [
			{ value: SchemeType.Unset, text: Loc.getMessage('SIGN_V2_B2E_SCHEME_SELECTOR_VALUE_TYPE_UNSET') },
			{ value: SchemeType.Default, text: Loc.getMessage('SIGN_V2_B2E_SCHEME_SELECTOR_VALUE_TYPE_DEFAULT') },
			{ value: SchemeType.Order, text: Loc.getMessage('SIGN_V2_B2E_SCHEME_SELECTOR_VALUE_TYPE_ORDER') },
		];
	}

	#getAvailableSchemes(): [{ value : string, text: string }]
	{
		return this.#getSchemeOptions().filter((schemeOption: { value: string, text: string }) => {
			return this.#availableSchemes.includes(schemeOption.value) || schemeOption.value === SchemeType.Unset;
		});
	}

	#isValidScheme(scheme: string): boolean
	{
		return Object.values(SchemeType).includes(scheme);
	}

	#selectItem(value: string): void
	{
		if (!this.#isValidScheme(value))
		{
			return;
		}

		this.#selectedType = value;
	}
}