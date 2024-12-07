import { Dom, Loc, Tag, Text, Type } from 'main.core';

import './styles/requisite-address.css';

const DEFAULT_ADDRESS_TYPE_ID = '11'; // \Bitrix\Crm\EntityAddressType::Delivery

declare type AddressType = {
	ID: string,
	DESCRIPTION: string,
};

export default class RequisiteAddressFieldConfigurator extends BX.UI.EntityEditorFieldConfigurator
{
	#addressTypeSelect: HTMLElement = null;

	#allAddressTypes: Map<string, AddressType> = null;
	#suitableAddressTypes: Map<string, AddressType> = null;

	static create(id, settings): RequisiteAddressFieldConfigurator
	{
		const self = new this();
		self.initialize(id, settings);

		return self;
	}

	layoutInternal(): void
	{
		super.layoutInternal();

		// eslint-disable-next-line no-underscore-dangle
		const wrapper = this._wrapper;
		const hr = wrapper.querySelector('hr');

		Dom.insertBefore(this.#getDefaultAddressTypeSetterContainer(), hr);
	}

	prepareSaveParams(...args): Object
	{
		const params = super.prepareSaveParams(this, args);

		const newDefaultAddressTypeId = this.#getAddressTypeSelectValue();
		if (!this.#isValidAddressType(newDefaultAddressTypeId))
		{
			return params;
		}

		this.#setDefaultAddressTypeToSchemeOptions(newDefaultAddressTypeId);

		params.defaultAddressType = newDefaultAddressTypeId;

		return params;
	}

	#setDefaultAddressTypeToSchemeOptions(defaultAddressTypeId: string): void
	{
		const schemeOptions = this.#getSchemeOptions();
		if (schemeOptions)
		{
			schemeOptions.defaultAddressType = defaultAddressTypeId;
		}
	}

	#getDefaultAddressTypeSetterContainer(): HTMLElement
	{
		const title = Loc.getMessage('CRM_REQUISITE_DEFAULT_ADDRESS_TYPE_TITLE');

		const wrapper = Tag.render`
			<div class="ui-entity-editor-content-block">
				<div class="ui-entity-editor-block-title">
					<span class="ui-entity-editor-block-title-text">
						${Text.encode(title)}
					</span>
				</div>
			</div>
		`;

		const selectContainer = Tag.render`<div class="ui-entity-editor-content-block crm-default-requisite-address-type"></div>`;

		Dom.append(this.#getAddressTypeSelect(), selectContainer);
		Dom.append(selectContainer, wrapper);

		return wrapper;
	}

	#getAddressTypeSelect(): HTMLSelectElement
	{
		if (!this.#addressTypeSelect)
		{
			this.#addressTypeSelect = Tag.render`<select class="main-ui-control main-enum-dialog-input" name="display"></select>`;
			this.#getPreparedAddressTypesForOptions().forEach((addressType) => {
				const option = Tag.render`
					<option value="${Text.encode(addressType.value)}">
						${Text.encode(addressType.label)}
					</option>
				`;

				Dom.append(option, this.#addressTypeSelect);
			});

			this.#addressTypeSelect.value = this.#getDefaultAddressType();
		}

		return this.#addressTypeSelect;
	}

	#getDefaultAddressType(): string
	{
		const { defaultAddressType: optionAddressTypeId } = this.#getSchemeOptions() ?? {};
		if (this.#isValidAddressType(optionAddressTypeId))
		{
			return optionAddressTypeId;
		}

		const { defaultAddressType: schemeDefaultAddressTypeId } = this.#getAddressZoneConfig() ?? {};
		if (this.#isValidAddressType(schemeDefaultAddressTypeId))
		{
			return schemeDefaultAddressTypeId;
		}

		return DEFAULT_ADDRESS_TYPE_ID;
	}

	#getPreparedAddressTypesForOptions(): Array
	{
		const options = [];

		const suitableAddressTypes = this.#getSuitableAddressTypes();
		suitableAddressTypes.forEach((addressType) => {
			options.push({
				value: addressType.ID,
				label: addressType.DESCRIPTION,
			});
		});

		return options;
	}

	#getAddressTypeSelectValue(): ?string
	{
		return this.#getAddressTypeSelect().value;
	}

	#isValidAddressType(addressTypeId: string | null): boolean
	{
		return Type.isStringFilled(addressTypeId)
			&& this.#getSuitableAddressTypes().has(addressTypeId)
		;
	}

	#getAllAddressTypes(): Map<string, AddressType>
	{
		if (!this.#allAddressTypes)
		{
			this.#allAddressTypes = new Map();

			const { types: allAddressTypes } = this.#getSchemeData() ?? {};
			if (allAddressTypes)
			{
				Object.values(allAddressTypes).forEach((addressType) => {
					this.#allAddressTypes.set(addressType.ID, addressType);
				});
			}
		}

		return this.#allAddressTypes;
	}

	#getSuitableAddressTypes(): Map<string, AddressType>
	{
		if (!this.#suitableAddressTypes)
		{
			this.#suitableAddressTypes = new Map();

			const { currentZoneAddressTypes } = this.#getAddressZoneConfig() ?? {};
			if (currentZoneAddressTypes)
			{
				currentZoneAddressTypes.forEach((addressTypeId) => {
					const addressType = this.#getAllAddressTypes().get(addressTypeId);
					if (addressType)
					{
						this.#suitableAddressTypes.set(addressType.ID, addressType);
					}
				});
			}
		}

		return this.#suitableAddressTypes;
	}

	#getSchemeData(): Object | null
	{
		return this.#getSchemeElement()?.getData();
	}

	#getSchemeOptions(): Object | null
	{
		return this.#getSchemeElement()?.getOptions();
	}

	#getSchemeElement(): Object | null
	{
		return this.getField()?.getSchemeElement();
	}

	#getAddressZoneConfig(): Object | null
	{
		return this.#getSchemeData()?.addressZoneConfig;
	}
}
