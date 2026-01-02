export type FormOptions = {
	bookingResourceAutoSelection?: BookingResourceAutoSelectionFormOptions,
	id: number,
	name: string,
	templateId: string,
	[key: string]: mixed,
}

export type BookingResourceAutoSelectionFormOptions = {
	use: boolean;
}

export type TemplateId = 'booking_auto_selection' | 'booking_any_resource' | 'booking_manual_settings' | string;

export type BookingSettingsDataProperty = 'default' | 'autoSelection';

export type BookingSettingsData = {
	isAutoSelectionOn: boolean,
	default: BookingDefaultForm,
	autoSelection: BookingAutoSelectionForm,
}

export type BookingForm = {
	resourceIds: number[],
	label: string,
	textHeader: string,
	hint: string,
	isVisibleHint: boolean,
	hasSlotsAllAvailableResources: boolean,
}

export type BookingAutoSelectionForm = BookingForm & {
	hasSlotsAllAvailableResources: false
}

export type BookingDefaultForm = BookingForm;
