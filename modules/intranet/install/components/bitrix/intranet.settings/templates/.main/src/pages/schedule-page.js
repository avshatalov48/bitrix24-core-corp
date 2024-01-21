import { Loc, Tag, Dom, Event } from 'main.core';
import { Section, Row } from 'ui.section';
import { ItemPicker, TextInput, Selector } from 'ui.form-elements.view';
import { SettingsSection, SettingsRow, TabField, TabsField, BaseSettingsPage } from 'ui.form-elements.field';

export class SchedulePage extends BaseSettingsPage
{
	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_SCHEDULE');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_SCHEDULE');
	}

	getType(): string
	{
		return 'schedule';
	}

	appendSections(contentNode: HTMLElement): void
	{
		const scheduleSection = this.#buildScheduleSection();
		scheduleSection.renderTo(contentNode);

		const holidaysSection = this.#buildHolidaysSection();
		holidaysSection.renderTo(contentNode);
	}

	#buildScheduleSection(): SettingsSection
	{
		let scheduleSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SCHEDULE'),
			titleIconClasses: 'ui-icon-set --calendar-1',
		});

		const settingsSection = new SettingsSection({
			parent: this,
			section: scheduleSection
		})

		//region tab section
		const settingsRow = new SettingsRow({
			parent: settingsSection
		});
		const tabsField = new TabsField({
			parent: settingsRow,
		});
		const forCompanyTab = new TabField({
			parent: tabsField,
			tabsOptions: {
				head: Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_COMPANY'),
				body: () => {
					return new Promise((resolve) => {

						let workTimeRow = new Row({});
						let workTimeContainerNode = Tag.render`<div class="intranet-settings__work-time_container"><div>`;
						if (this.hasValue('WORK_TIME_START'))
						{
							const workTimeStartField = new Selector({
								label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WORK_TIME_START'),
								name: this.getValue('WORK_TIME_START').name,
								items: this.getValue('WORK_TIME_START').values,
								current: this.getValue('WORK_TIME_START').current,
							});
							Dom.append(workTimeStartField.render(), workTimeContainerNode);
						}
						Dom.append(Tag.render`<div class="ui-section__field-inline-separator"></div>`, workTimeContainerNode);
						if (this.hasValue('WORK_TIME_END'))
						{
							const workTimeEndField = new Selector({
								label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WORK_TIME_END'),
								name: this.getValue('WORK_TIME_END').name,
								items: this.getValue('WORK_TIME_END').values,
								current: this.getValue('WORK_TIME_END').current,
							});
							Dom.append(workTimeEndField.render(), workTimeContainerNode);

						}
						workTimeRow.append(workTimeContainerNode);

						let containerTab = Tag.render`<div><div>`;
						Dom.append(workTimeRow.render(), containerTab);


						if (this.hasValue('WEEK_DAYS'))
						{
							const itemPickerField = new ItemPicker({
								inputName: this.getValue('WEEK_DAYS').name,
								isMulti: true,
								label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEKEND'),
								items: this.getValue('WEEK_DAYS').values,
								current: this.getValue('WEEK_DAYS').current,
							});
							const itemPickerRow = new Row({
								content: itemPickerField.render(),
							});
							Dom.append(itemPickerRow.render(), containerTab);
						}

						if (this.hasValue('WEEK_START'))
						{
							const weekStartField = new ItemPicker({
								inputName: this.getValue('WEEK_START').name,
								label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEK_START'),
								items: this.getValue('WEEK_START').values,
								current: this.getValue('WEEK_START').current,
							});
							const weekStartRow = new Row({
								content: weekStartField.render(),
								className: '--row-frame_gray',
							});
							this.fields[this.getValue('WEEK_START').name] = weekStartField;
							Dom.append(weekStartRow.render(), containerTab);
						}

						resolve(containerTab);

					});
				}
			}
		});

		if (this.getValue('TIMEMAN').enabled)
		{
			new TabField({
				parent: tabsField,
				tabsOptions: {
					restricted: this.getValue('TIMEMAN').restricted,
					bannerCode: 'limit_office_shift_scheduling',
					head: Loc.getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_DEPARTMENT'),
					body: this.#forDepartmentsRender(),
				}
			});
		}

		tabsField.activateTab(forCompanyTab);
		//endregion

		return settingsSection;
	}

	#buildHolidaysSection(): SettingsSection
	{
		let holidaysSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_HOLIDAYS'),
			titleIconClasses: 'ui-icon-set --flag-2',
			isOpen: false
		});

		const settingsSection = new SettingsSection({
			parent: this,
			section: holidaysSection
		})

		const countDays = this.getValue('year_holidays')?.match(/\d{1,2}.\d{1,2}/gm)?.length ?? 0;
		let countDaysNode = Tag.render`<div class="ui-section__field-label --mb-13">${Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', { '#COUNT_DAYS#': countDays })}</div>`;
		const holidaysRow = new Row({
			content: countDaysNode,
		});
		holidaysSection.append(holidaysRow.render());

		if (this.hasValue('year_holidays'))
		{
			const holidaysField = new TextInput({
				inputName: 'year_holidays',
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_HOLIDAYS'),
				value: this.getValue('year_holidays'),
			});
			SchedulePage.addToSectionHelper(holidaysField, settingsSection);

			Event.bind(holidaysField.getInputNode(), 'keyup', () => {
				const count = holidaysField?.getInputNode().value?.match(/\d{1,2}.\d{1,2}/gm)?.length ?? 0;
				countDaysNode.innerHTML = Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', { '#COUNT_DAYS#': count });
			});
		}

		return settingsSection;
	}

	#forDepartmentsRender(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-settings__tab-info_container">
				<div class="intranet-settings__tab-info_text">${Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_FOR_DEPARTMENTS')}</div>
				<a href="/timeman/schedules/" class="ui-section__link" target="_blank">${Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_FOR_DEPARTMENTS_CONFIG')}</a>
			</div>
		`;
	}
}
