/* eslint-disable no-underscore-dangle, @bitrix24/bitrix24-rules/no-pseudo-private */

import { Dom, Loc, Tag, Type } from 'main.core';
import { TagSelector } from 'crm.entity-selector';
import { BaseEvent } from 'main.core.events';

import './styles/phone-number-input.css';

const DEFAULT_COUNTRY_CODE = 'XX';

/**
 * @memberOf BX.Crm
 */
export class PhoneNumberInputFieldConfigurator extends BX.UI.EntityEditorFieldConfigurator
{
	#countrySelector: ?TagSelector = null;

	destroy(): void
	{
		if (this.#countrySelector)
		{
			this.#countrySelector.destroy();
		}
	}

	// region overridden methods from BX.UI.EntityEditorFieldConfigurator ----------------------------------------------
	/**
	 * @override
	 */
	layoutInternal(): void
	{
		Dom.append(this.getInputContainer(), this._wrapper);
		if (this._typeId === 'list')
		{
			this.layoutInnerConfigurator(this._field.getInnerConfig(), this._field.getItems());
		}
		Dom.append(this.getOptionContainer(), this._wrapper);

		if (this._typeId === 'multifield' || this._typeId === 'client_light')
		{
			Dom.append(this.getCountrySelectContent(), this._wrapper); // NEW: country selector added
		}

		Dom.append(Tag.render`<hr class="ui-entity-editor-line">`, this._wrapper);
		Dom.append(this.getButtonContainer(), this._wrapper);
	}

	prepareSaveParams(...args): Object
	{
		const params = super.prepareSaveParams(this, args);

		// add selected value
		if (this.#countrySelector)
		{
			const items = this.#countrySelector.getDialog().getSelectedItems();
			if (items.length <= 1)
			{
				params.defaultCountry = Type.isArrayFilled(items)
					? items[0].id
					: DEFAULT_COUNTRY_CODE
				;

				this.#getSchemeElementOptions().defaultCountry = params.defaultCountry;
			}
		}

		return params;
	}
	// endregion -------------------------------------------------------------------------------------------------------

	getCountrySelectContent(): HTMLElement
	{
		const wrapper = Tag.render`
			<div class="ui-entity-editor-content-block">
				<div class="ui-entity-editor-block-title">
					<span class="ui-entity-editor-block-title-text">
						${Loc.getMessage('CRM_PHONE_NUMBER_INPUT_FIELD_CONFIGURATOR_TITLE')}
					</span>
				</div>
			</div>
		`;

		Dom.append(this.#getSelectContainer(), wrapper);

		return wrapper;
	}

	#getSelectContainer(): HTMLElement
	{
		const selectContainer = Tag.render`
			<div class="ui-entity-editor-content-block crm-entity-country-tag-selector"></div>
		`;

		this.#getCountrySelector().renderTo(selectContainer);

		return selectContainer;
	}

	#getCountrySelector(): TagSelector
	{
		if (!this.#countrySelector)
		{
			this.#countrySelector = new TagSelector({
				textBoxWidth: '100%',
				tagMaxWidth: 270,
				placeholder: Loc.getMessage('CRM_PHONE_NUMBER_INPUT_FIELD_CONFIGURATOR_PLACEHOLDER'),
				multiple: false,
				dialogOptions: {
					width: 425,
					multiple: false,
					showAvatars: true,
					dropdownMode: true,
					preselectedItems: [
						['country', this.#getDefaultCountry()],
					],
					entities: [{
						id: 'country',
					}],
					events: {
						onFirstShow: (event: BaseEvent): void => {
							const popupContainer = event.getTarget().getPopup().getContentContainer();
							if (Type.isDomNode(popupContainer))
							{
								Dom.addClass(popupContainer, 'crm-entity-country-tag-selector-popup');
							}
						},
					},
				},
			});
		}

		return this.#countrySelector;
	}

	#getDefaultCountry(): string
	{
		const { defaultCountry } = this.#getSchemeElementOptions();
		if (Type.isStringFilled(defaultCountry))
		{
			return defaultCountry;
		}

		return DEFAULT_COUNTRY_CODE;
	}

	#getSchemeElementOptions(): ?Object
	{
		return this?._field?.getSchemeElement()?._options;
	}

	static create(id, settings): PhoneNumberInputFieldConfigurator
	{
		const self = new this();
		self.initialize(id, settings);

		return self;
	}
}
