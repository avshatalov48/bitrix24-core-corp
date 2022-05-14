"use strict";
import {Loc, Runtime, Type} from "main.core";
export class SliderLoader
{
	constructor(entryId, options = {})
	{
		this.extensionName = (
			(
				Type.isString(entryId)
				&& (
					entryId === 'NEW'
					|| entryId.substr(0, 4) === 'EDIT'
				)
			)
			|| !parseInt(entryId)
		)
			? 'EventEditForm'
			: 'EventViewForm';

		this.sliderId = options.sliderId || "calendar:slider-" + Math.random();

		entryId = (Type.isString(entryId) && entryId.substr(0, 4) === 'EDIT')
			? parseInt(entryId.substr(4))
			: parseInt(entryId);

		this.extensionParams = {
			entryId: entryId,
			entry: options.entry || null,
			type: options.type || null,
			ownerId: parseInt(options.ownerId) || null,
			userId: parseInt(options.userId) || null,
		};

		if (Type.isArray(options.participantsEntityList))
		{
			this.extensionParams.participantsEntityList = options.participantsEntityList;
		}

		if (Type.isArray(options.participantsSelectorEntityList))
		{
			this.extensionParams.participantsSelectorEntityList = options.participantsSelectorEntityList;
		}

		if (options.formDataValue)
		{
			this.extensionParams.formDataValue = options.formDataValue;
		}

		if (options.calendarContext)
		{
			this.extensionParams.calendarContext = options.calendarContext;
		}

		if (options.isLocationCalendar)
		{
			this.extensionParams.isLocationCalendar = options.isLocationCalendar;
		}

		if (options.roomsManager)
		{
			this.extensionParams.roomsManager = options.roomsManager;
		}

		if (options.locationAccess)
		{
			this.extensionParams.locationAccess = options.locationAccess;
		}

		if (options.locationCapacity)
		{
			this.extensionParams.locationCapacity = options.locationCapacity;
		}

		if (options.dayOfWeekMonthFormat)
		{
			this.extensionParams.dayOfWeekMonthFormat = options.dayOfWeekMonthFormat;
		}

		if (Type.isDate(options.entryDateFrom))
		{
			this.extensionParams.entryDateFrom = options.entryDateFrom;
		}

		if (options.timezoneOffset)
		{
			this.extensionParams.timezoneOffset = options.timezoneOffset;
		}

		if (Type.isString(options.entryName))
		{
			this.extensionParams.entryName = options.entryName;
		}

		if (Type.isString(options.entryDescription))
		{
			this.extensionParams.entryDescription = options.entryDescription;
		}
	}

	show()
	{
		BX.SidePanel.Instance.open(this.sliderId, {
			contentCallback: this.loadExtension.bind(this),
			label: {
				text: Loc.getMessage('CALENDAR_EVENT'),
				bgColor: "#55D0E0"
			},
			type: 'calendar:slider'
		});
	}

	loadExtension(slider)
	{
		return new Promise((resolve) => {
			const extensionName = 'calendar.' + this.extensionName.toLowerCase();
			Runtime.loadExtension(extensionName).then((exports) => {
				if (exports && exports[this.extensionName])
				{
					const calendarForm = new exports[this.extensionName](this.extensionParams);
					if (typeof calendarForm.initInSlider)
					{
						calendarForm.initInSlider(slider, resolve);
					}
				}
				else
				{
					console.error(`Extension "calendar.${extensionName}" not found`);
				}
			});
		});
	}
}