import { Extension, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';
import type { AnalyticsOptions } from 'ui.analytics';
import { sendData as sendAnalyticsData } from 'ui.analytics';

export const EventIds = Object.freeze({
	activityView: 'activity_view',
	activityCreate: 'activity_create',
	activityEdit: 'activity_edit',
	activityCancel: 'activity_cancel',
	activityComplete: 'activity_complete',
});

type EventIdsType =
	EventIds.activityView
	| EventIds.activityCreate
	| EventIds.activityEdit
	| EventIds.activityCancel
	| EventIds.activityComplete
;

export const Section = Object.freeze({
	lead: 'lead_section',
	deal: 'deal_section',
	quote: 'quote_section',
	contact: 'contact_section',
	company: 'company_section',
	dynamic: 'dynamic_section',
	smartInvoice: 'smart_invoice',
	custom: 'custom_section',
	myCompany: 'my_company_section',
	smartDocument: 'smart_document_contact_section',
	catalogContact: 'catalog_contractor_contact_section',
	catalogCompany: 'catalog_contractor_company_section',
});

export type SectionIdType = Section.lead | Section.deal | Section.contact
	| Section.company | Section.dynamic | Section.custom
	| Section.myCompany | Section.smartDocument | Section.catalogContact
	| Section.catalogCompany
;

export const SubSection = Object.freeze({
	list: 'list',
	kanban: 'kanban',
	activities: 'activities',
	deadlines: 'deadlines',
	details: 'details',
	notificationPopup: 'notification_popup',
	kanbanDropzone: 'kanban_dropzone',
});

export type SubSectionIdType = SubSection.list | SubSection.kanban | SubSection.activities
	| SubSection.deadlines | SubSection.details | SubSection.notificationPopup
;

export const CrmMode = Object.freeze({
	simple: 'crmMode_simple',
	classic: 'crmMode_classic',
});

export type CrmModeIdType = CrmMode.simple | CrmMode.classic;

export const ElementIds = Object.freeze({
	colorSettings: 'color_settings',
	description: 'description',
	title: 'title',
	responsibleUserId: 'responsible_user_id',
	deadline: 'deadline',
	pingSettings: 'ping_settings',
	addBlock: 'add_block',
	createButton: 'create_button',
	editButton: 'edit_button',
	cancelButton: 'cancel_button',
	skipPeriodButton: 'skip_period_button',
	autoFromActivityViewMode: 'auto_from_activity_view_mode',
	complexButton: 'complete_button',
	checkbox: 'checkbox',
	calendarSection: 'calendar_section',
});

export type ElementIdsType = ElementIds.colorSettings | ElementIds.description | ElementIds.title
	| ElementIds.responsibleUserId | ElementIds.deadlines | ElementIds.pingSettings | ElementIds.addBlock
	| ElementIds.createButton | ElementIds.editButton | ElementIds.cancelButton | ElementIds.skipPeriodButton
	| ElementIds.autoFromActivityViewMode | ElementIds.complexButton | ElementIds.checkbox
;

export type AnalyticParams = {
	event: EventIdsType;
	section: SectionIdType;
	subSection: SubSectionIdType;
	element: ElementIdsType;

	crmMode: string;
	pingSettings?: string;
	colorId?: string;
	blockTypes?: string;
	notificationSkipPeriod?: string;
};

export type ToDoEditorData = {
	analyticSection?: string;
	analyticSubSection: string;
}

export class Analytics
{
	#tool: string = 'crm';
	#category: string = 'activity_operations';
	#event: EventIdsType;
	#type: string = 'todo_activity';
	#section: SectionIdType;
	#subSection: SubSectionIdType;
	#element: ElementIdsType;

	#crmMode: string;
	#pingSettings: string;
	#colorId: string;
	#blockTypes: string;
	#notificationSkipPeriod: string;
	#isTitleChanged: boolean = false;
	#isDescriptionChanged: boolean = false;

	#extensionSettings: ?SettingsCollection = null;

	static createFromToDoEditorData(data: ToDoEditorData): Analytics
	{
		const { analyticSection: section, analyticSubSection: subSection } = data;

		return new Analytics(section, subSection);
	}

	constructor(section: SectionIdType, subSection: SubSectionIdType)
	{
		this.#extensionSettings = Extension.getSettings('crm.activity.todo-editor-v2');

		this.#section = section;
		this.#subSection = subSection;
		this.#crmMode = this.#getCrmMode();
	}

	#getCrmMode(): string
	{
		return `crmMode_${this.#extensionSettings.get('crmMode', '').toLowerCase()}`;
	}

	setEvent(event: EventIdsType): Analytics
	{
		this.#event = event;

		return this;
	}

	setSubSection(subSection: SubSectionIdType): Analytics
	{
		this.#subSection = subSection;

		return this;
	}

	setPingSettings(pingSettings: ?string): Analytics
	{
		this.#pingSettings = pingSettings;

		return this;
	}

	setColorId(colorId: ?string): Analytics
	{
		this.#colorId = colorId;

		return this;
	}

	setBlockTypes(blockTypes: ?string[]): Analytics
	{
		this.#blockTypes = blockTypes;

		return this;
	}

	setNotificationSkipPeriod(notificationSkipPeriod: ?string): Analytics
	{
		this.#notificationSkipPeriod = notificationSkipPeriod;

		return this;
	}

	setElement(element: ElementIdsType): Analytics
	{
		this.#element = element;

		return this;
	}

	setIsTitleChanged(value: boolean = true): Analytics
	{
		this.#isTitleChanged = value;

		return this;
	}

	setIsDescriptionChanged(value: boolean = true): Analytics
	{
		this.#isDescriptionChanged = value;

		return this;
	}

	send(): void
	{
		const data = this.getData();
		if (this.#validate(data))
		{
			sendAnalyticsData(data);
		}
	}

	getData(): AnalyticsOptions
	{
		const data = {
			tool: this.#tool,
			category: this.#category,
			event: this.#event,
			type: this.#type,
			c_section: this.#section,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: this.#crmMode,
		};

		if (Type.isStringFilled(this.#notificationSkipPeriod))
		{
			if (this.#notificationSkipPeriod === 'forever')
			{
				data.p2 = 'skipPeriod_custom';
			}
			else
			{
				data.p2 = 'skipPeriod_forever';
			}
		}
		else if (Type.isStringFilled(this.#pingSettings))
		{
			data.p2 = 'ping_custom';
		}

		if (Type.isStringFilled(this.#colorId))
		{
			data.p3 = 'color_custom';
		}

		if (Type.isArrayFilled(this.#blockTypes))
		{
			const p4Items = [];

			if (this.#blockTypes.includes('section_calendar'))
			{
				p4Items.push('calendarCustom');
			}

			p4Items.push(`addBlock_${this.#blockTypes.length}`);

			data.p4 = p4Items.join(',');
		}

		const p5Items = [];
		if (this.#isTitleChanged)
		{
			p5Items.push('title');
		}

		if (this.#isDescriptionChanged)
		{
			p5Items.push('description');
		}

		if (Type.isArrayFilled(p5Items))
		{
			data.p5 = p5Items.join(',');
		}

		return data;
	}

	#validate(data: AnalyticsOptions): boolean
	{
		let isValid = true;
		Object.keys(data).forEach((key) => {
			if (Type.isNil(data[key]))
			{
				isValid = false;
			}
		});

		return isValid;
	}
}
