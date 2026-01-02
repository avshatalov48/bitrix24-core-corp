import { Loc } from 'main.core';
import type { BookingAutoSelectionForm, BookingDefaultForm } from '../types';

const defaultBookingForm = {
	resourceIds: [],
	label: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_LABEL_DEFAULT'),
	isVisibleHint: true,
	hint: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_HINT_DEFAULT_VALUE'),
	hasSlotsAllAvailableResources: false,
};

export const defaultBookingDefaultForm: BookingDefaultForm = {
	...defaultBookingForm,
	resourceIds: [],
	textHeader: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_PLACEHOLDER_DEFAULT_VALUE'),
};

export const defaultBookingAutoSelectionForm: BookingAutoSelectionForm = {
	...defaultBookingForm,
	resourceIds: [],
	hasSlotsAllAvailableResources: false,
	textHeader: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_FIELD_PLACEHOLDER_AUTO_SELECT_DEFAULT_VALUE'),
};
