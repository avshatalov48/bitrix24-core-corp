import { CrmFormTemplateId, CrmFormSettingsDataPropName } from 'booking.const';

import { defaultBookingDefaultForm, defaultBookingAutoSelectionForm } from '../const';
import type {
	BookingSettingsData,
	BookingSettingsDataProperty,
	BookingForm,
	BookingAutoSelectionForm,
	BookingDefaultForm,
	TemplateId,
} from '../types';

export class BookingSettingsDataModel
{
	#isAutoSelectionOn: boolean;
	#settingsData: BookingSettingsData;

	constructor(settingsData: BookingSettingsData, isAutoSelectionOn: boolean, templateId: TemplateId)
	{
		this.#isAutoSelectionOn = isAutoSelectionOn;

		const autoSelectionData = (
			settingsData[CrmFormSettingsDataPropName.autoSelection]
			|| this.#getAutoSelectionFormByTemplate(templateId)
		);
		const defaultData = (
			settingsData[CrmFormSettingsDataPropName.default]
			|| this.#getDefaultFormByTemplate(templateId)
		);
		this.#settingsData = {
			[CrmFormSettingsDataPropName.isAutoSelectionOn]: isAutoSelectionOn,
			[CrmFormSettingsDataPropName.autoSelection]: autoSelectionData,
			[CrmFormSettingsDataPropName.default]: defaultData,
		};
	}

	#getAutoSelectionFormByTemplate(): BookingAutoSelectionForm
	{
		return defaultBookingAutoSelectionForm;
	}

	#getDefaultFormByTemplate(templateId: TemplateId): BookingDefaultForm
	{
		if (templateId === CrmFormTemplateId.BookingAnyResource)
		{
			return {
				...defaultBookingDefaultForm,
				hasSlotsAllAvailableResources: true,
			};
		}

		return { ...defaultBookingDefaultForm };
	}

	get dataSettingsProperty(): BookingSettingsDataProperty
	{
		return this.#isAutoSelectionOn
			? CrmFormSettingsDataPropName.autoSelection
			: CrmFormSettingsDataPropName.default;
	}

	setSettingsData(patch: Partial<BookingForm> = {}): void
	{
		Object.assign(this.#settingsData[this.dataSettingsProperty], patch);
	}

	get settingsData(): BookingSettingsData
	{
		return this.#settingsData;
	}

	get form(): BookingForm
	{
		return this.#settingsData[this.dataSettingsProperty];
	}
}
