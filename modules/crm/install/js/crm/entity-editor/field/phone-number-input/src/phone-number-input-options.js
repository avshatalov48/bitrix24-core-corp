export type PhoneNumberInputOptions = {
	// BX.PhoneNumber.Input parameters
	node: ?HTMLElement,
	flagNode: ?HTMLElement,
	defaultCountry: ?String,
	userDefaultCountry: ?String,
	forceLeadingPlus: ?Boolean,
	countryPopupHeight: ?Number,
	countryPopupClassName: ?String,
	countryTopList: ?Array,
	onInitialize: ?Function,
	onChange: ?Function,
	onCountryChange: ?Function,
	// PhoneNumberInput parameters
	isSelectionIndicatorEnabled: ?Boolean,
	searchDialogContextCode: ?String,
};
