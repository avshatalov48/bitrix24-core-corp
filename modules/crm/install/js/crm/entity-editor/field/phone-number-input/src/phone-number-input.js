import {Dom, Loc, Reflection, Tag, Type} from 'main.core';
import {Dialog, Item} from 'crm.entity-selector';

import type {PhoneNumberInputOptions} from './phone-number-input-options';

import 'ui.design-tokens';
import './phone-number-input.css';

const NAMESPACE = Reflection.namespace('BX.Crm');
const FLAG_ICON_PATH = '/bitrix/js/crm/entity-selector/src/images/';
const FLAG_ICON_EXT = 'png';
const FLAG_SIZE = 24;
const PLUS_CHAR = '+';
const GLOBAL_COUNTRY_CODE = 'XX';
const LAST_RECENT_ITEMS_TITLE_COLOR = '#00789E';

export default class PhoneNumberInput extends BX.PhoneNumber.Input
{
	#searchDialogContextCode: String;
	#isSelectionIndicatorEnabled: Boolean;
	#countryDialog: ?Dialog = null;
	#countryFlagTickNode: ?HTMLElement = null;

	constructor(params: PhoneNumberInputOptions)
	{
		// set permanent options
		params.flagSize = FLAG_SIZE;

		// show global icon when empty country code
		if (params.savedCountryCode === '')
		{
			params.savedCountryCode = GLOBAL_COUNTRY_CODE;
		}

		super(params);

		this.#searchDialogContextCode = Type.isStringFilled(params.searchDialogContextCode)
			? params.searchDialogContextCode
			: '';

		this.#isSelectionIndicatorEnabled = Type.isBoolean(params.isSelectionIndicatorEnabled)
			? params.isSelectionIndicatorEnabled
			: false;

		if (this.#isSelectionIndicatorEnabled)
		{
			this.#initSelectionIndicator();
		}
	}

	destroy()
	{
		if (this.#countryDialog)
		{
			this.#countryDialog.destroy();
		}
	}

	// region overridden methods from BX.PhoneNumber.Input ------------------------------------------------------------
	/**
	 * Override default behavior with PopupWindow. EntitySelectorEx.Dialog component used.
	 *
	 * @param event
	 *
	 * @override (parent method BX.PhoneNumber.Input.prototype._onFlagClick)
	 */
	_onFlagClick(event)
	{
		if (!Type.isDomNode(this.flagNode))
		{
			return;
		}

		if (this.#countryDialog)
		{
			this.#countryDialog.show();

			return;
		}

		// new popup dialog
		this.#countryDialog = new Dialog({
			targetNode: this.flagNode,
			context: this.#searchDialogContextCode,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			width: 350,
			tagSelectorOptions: {
				placeholder: Loc.getMessage('CRM_PHONE_INPUT_FIELD_TAG_SELECTOR_SEARCH_PLACEHOLDER'),
				textBoxWidth: '100%'
			},
			entities: [{
				id: 'country',
				options: {
					isEmptyCountryEnabled: false
				}
			}],
			events: this.#initCountryDialogEvents()
		});

		this.#countryDialog.show();
	}

	/**
	 * New icons to display country flag added.
	 *
	 * @override (parent method BX.PhoneNumber.Input.prototype.drawCountryFlag)
	 */
	drawCountryFlag(): void
	{
		if (!Type.isDomNode(this.flagNode))
		{
			return;
		}

		let country = this.getCountry();
		if (!Type.isStringFilled(country)) {
			return;
		}

		this.adjustFlag(country);
	};

	/**
	 * Add 'global' flag functionality when countryCode is undefined.
	 *
	 * @override
	 */
	tryRedrawCountryFlag(): void
	{
		const useGlobalCode = !Type.isStringFilled(this.inputNode.value)
			|| Type.isNull(this.formatter.country)
			|| !this.formatter.isInternational
		;

		if (useGlobalCode)
		{
			this.formatter.replaceCountry(GLOBAL_COUNTRY_CODE);
			this.adjustFlag(GLOBAL_COUNTRY_CODE);
		}
		else
		{
			this.drawCountryFlag();
		}

		this.callbacks.countryChange({
			country: this.getCountry(),
			countryCode: this.getCountryCode()
		});
	}

	/**
	 * @param {String} newValue
	 * @param {String} savedCountryCode
	 *
	 * @override
	 */
	setValue(newValue: String, savedCountryCode: String): void
	{
		this.waitForInitialization().then(function()
		{
			this.inputNode.value = this.formatter.format(newValue.toString());

			this.callbacks.change({
				value: this.getValue(),
				formattedValue: this.getFormattedValue(),
				country: this.getCountry(),
				countryCode: this.getCountryCode()
			});

			if (this._countryBefore !== this.getCountry())
			{
				this.drawCountryFlag();
				this.callbacks.countryChange({
					country: this.getCountry(),
					countryCode: this.getCountryCode()
				});
			}

			// NEW: redraw country flag if saved country code exists and does not match with formatter code
			if (Type.isStringFilled(savedCountryCode) && this.formatter.country !== savedCountryCode)
			{
				this.formatter.replaceCountry(savedCountryCode);
				this.tryRedrawCountryFlag()
			}
		}.bind(this));
	}

	/**
	 * Handler when user select the country from list
	 * (userOptions saving not used).
	 *
	 * @param event
	 *
	 * @override
	 */
	onCountrySelect(event): void
	{
		const item = event.getData().item;
		if (item)
		{
			const country = item.getId();
			if (country === this.getCountry())
			{
				return; // nothing to do
			}

			this.formatter.replaceCountry(country);
			this.inputNode.value = this.formatter.getFormattedNumber();
			this.drawCountryFlag();
			this.callbacks.change({
				value: this.getValue(),
				formattedValue: this.getFormattedValue(),
				country: this.getCountry(),
				countryCode: this.getCountryCode()
			});
			this.callbacks.countryChange({
				country: this.getCountry(),
				countryCode: this.getCountryCode()
			});
		}
	};
	// endregion -------------------------------------------------------------------------------------------------------

	adjustFlag(country: String): void
	{
		const countryFlagIconUrl = FLAG_ICON_PATH + country.toLowerCase() + '.' + FLAG_ICON_EXT;

		Dom.adjust(
			this.flagNode,
			{
				props: {
					className: this.flagNodeInitialClass + ' crm-entity-phone-number-input-flag-' + this.flagSize
				}
			}
		);

		Dom.style(this.flagNode, {
			'border': '1px solid rgba(82, 92, 105, 0.2)',
			'background-image': 'url("' + countryFlagIconUrl + '")',
		});

		if (country === GLOBAL_COUNTRY_CODE)
		{
			Dom.style(this.flagNode, {
				'border': 0,
				'background-position': 'center',
				'background-size': 'contain',
				'background-repeat': 'no-repeat'
			});
		}
	}

	// region PRIVATE methods ------------------------------------------------------------------------------------------
	#initSelectionIndicator(): void
	{
		if (Type.isDomNode(this.flagNode))
		{
			this.#countryFlagTickNode = Tag.render`<span class="crm-entity-widget-content-country-flag-tick"></span>`;

			Dom.append(this.#countryFlagTickNode, this.flagNode);
		}
	}

	#initCountryDialogEvents(): Object
	{
		let me = this;

		let events = {
			'Item:onSelect': (event) =>
			{
				me.onCountrySelect(event)
			}
		};

		events.onLoad = (event) =>
		{
			const dialogItems = event.getTarget().getItems();

			let filtered = dialogItems.filter(row => row.contextSort);
			filtered.forEach(item => item.setTextColor(LAST_RECENT_ITEMS_TITLE_COLOR));

			const country = me.formatter.country;
			if (country)
			{
				let selectedIdem: Item = dialogItems.find((item: Item) => item.getId() === country);
				if (selectedIdem)
				{
					selectedIdem.select();
				}
			}
		};

		events.onFirstShow = (event) => {
			const popupContainer = event.getTarget().getPopup().getContentContainer();
			if (Type.isDomNode(popupContainer))
			{
				Dom.addClass(popupContainer, 'crm-entity-country-selector-popup')
			}
		}

		if (this.#isSelectionIndicatorEnabled)
		{
			events.onShow = (event) =>
			{
				if (me.#countryFlagTickNode)
				{
					Dom.addClass(me.#countryFlagTickNode, '--flipped');
				}

				const country = me.formatter.country;
				if (country)
				{
					let dialog: Dialog = event.getTarget();
					let selectedIdem: Item = dialog.getItems().find(item => item.getId() === country);
					if (selectedIdem)
					{
						selectedIdem.select();
					}
				}

			};

			events.onHide = () =>
			{
				if (me.#countryFlagTickNode)
				{
					Dom.removeClass(me.#countryFlagTickNode, '--flipped');
				}
			};
		}

		return events;
	}
	// endregion -------------------------------------------------------------------------------------------------------

	static isCountryCodeOnly(input: String, countryCode: ?String): Boolean
	{
		return input === PLUS_CHAR
			|| input === countryCode
			|| input === PLUS_CHAR + countryCode
		;
	}
}

NAMESPACE.PhoneNumberInput = PhoneNumberInput;
