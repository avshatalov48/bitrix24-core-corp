import { Dom, Loc, Tag, Type } from 'main.core';
import { TagSelector } from 'crm.entity-selector';

import './phone-number-input-field-configurator.css'

const DEFAULT_COUNTRY_CODE = 'XX';

/**
 * @memberOf BX.Crm
 */
export class PhoneNumberInputFieldConfigurator extends BX.UI.EntityEditorFieldConfigurator
{
	#countrySelector: ?TagSelector = null;

	destroy()
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
		this._wrapper.appendChild(this.getInputContainer());
		if (this._typeId === "list")
		{
			this.layoutInnerConfigurator(this._field.getInnerConfig(), this._field.getItems());
		}
		this._wrapper.appendChild(this.getOptionContainer());

		if (this._typeId === 'multifield' || this._typeId === 'client_light') {
			this._wrapper.appendChild(this.getCountrySelectContainer()); // NEW: country selector added
		}
		Dom.append(Tag.render`<hr class="ui-entity-editor-line">`, this._wrapper);
		this._wrapper.appendChild(this.getButtonContainer());
	};

	/**
	 * @param event
	 *
	 * @returns {Object}
	 *
	 * @override
	 */
	prepareSaveParams(event): Object
	{
		const params = super.prepareSaveParams(this, arguments);

		// add selected value
		if (this.#countrySelector)
		{
			const items = this.#countrySelector.getDialog().getSelectedItems();
			if (items.length <= 1)
			{
				params['defaultCountry'] = items.length === 0 ? DEFAULT_COUNTRY_CODE : items[0].id;

				this._field.getSchemeElement()._options['defaultCountry'] = params['defaultCountry'];
			}
		}

		return params;
	}
	// endregion -------------------------------------------------------------------------------------------------------

	getCountrySelectContainer()
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

		const selectContainer = Tag.render`
			<div class="ui-entity-editor-content-block crm-entity-country-tag-selector"></div>
		`;

		Dom.append(selectContainer, wrapper);

		let defaultCountry = DEFAULT_COUNTRY_CODE;

		if (
			this._field
			&& this._field.getSchemeElement()
			&& Type.isPlainObject(this._field.getSchemeElement()._options)
			&& Type.isStringFilled(this._field.getSchemeElement()._options.defaultCountry)
		)
		{
			defaultCountry = this._field.getSchemeElement()._options.defaultCountry;
		}

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
					['country', defaultCountry],
				],
				entities: [{
					id: 'country'
				}],
				events: {
					'onFirstShow': (event) => {
						const popupContainer = event.getTarget().getPopup().getContentContainer();
						if (Type.isDomNode(popupContainer))
						{
							Dom.addClass(popupContainer, 'crm-entity-country-tag-selector-popup')
						}
					}
				}
			}
		});

		this.#countrySelector.renderTo(selectContainer);

		return wrapper;
	}

	static create(id, settings): PhoneNumberInputFieldConfigurator
	{
		const self = new this;

		self.initialize(id, settings);

		return self;
	}
}
