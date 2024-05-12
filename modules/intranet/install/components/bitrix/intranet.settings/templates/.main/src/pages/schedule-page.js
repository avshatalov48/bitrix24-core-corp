import { Loc, Tag, Dom, Event } from 'main.core';
import { Section, Row } from 'ui.section';
import { ItemPicker, TextInput, Selector } from 'ui.form-elements.view';
import {
	SettingsSection,
	SettingsRow,
	TabField,
	TabsField,
	BaseSettingsPage,
	SettingsField,
} from 'ui.form-elements.field';

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

	#buildScheduleTab(parent)
	{
		let workTimeRow = new Row({
			className: 'intranet-settings__work-time_container --no-padding',
		});
		let settingsRow = new SettingsRow({
			row: workTimeRow,
			parent: parent,
		});
		if (this.hasValue('WORK_TIME_START'))
		{
			const workTimeStartField = new Selector(this.getValue('WORK_TIME_START'));

			new SettingsRow({
				child: new SettingsField({
					fieldView: workTimeStartField,
				}),
				parent: settingsRow,
				row: new Row({
					className: 'intranet-settings__work-time_row',
				}),
			});
		}

		new SettingsRow({
			row: new Row({
				className: 'ui-section__field-inline-separator',
			}),
			parent: settingsRow,
		});

		if (this.hasValue('WORK_TIME_END'))
		{
			const workTimeEndField = new Selector(this.getValue('WORK_TIME_END'));

			new SettingsRow({
				child: new SettingsField({
					fieldView: workTimeEndField,
				}),
				parent: settingsRow,
				row: new Row({
					className: 'intranet-settings__work-time_row',
				}),
			});
		}

		let containerTab = Tag.render`<div><div>`;
		Dom.append(workTimeRow.render(), containerTab);

		if (this.hasValue('WEEK_DAYS'))
		{
			const itemPickerField = new ItemPicker({
				inputName: this.getValue('WEEK_DAYS').inputName,
				isMulti: true,
				label: this.getValue('WEEK_DAYS').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEKEND'),
				items: this.getValue('WEEK_DAYS').values,
				current: this.getValue('WEEK_DAYS').multiValue,
			});
			let settingsField = new SettingsField({
				fieldView: itemPickerField,
			});
			const itemPickerRow = new Row({
				content: itemPickerField.render(),
			});
			new SettingsRow({
				row: itemPickerRow,
				child: settingsField,
				parent: parent
			});
			Dom.append(itemPickerRow.render(), containerTab);
		}

		if (this.hasValue('WEEK_START'))
		{
			const weekStartField = new ItemPicker(this.getValue('WEEK_START'));
			let settingsField = new SettingsField({
				fieldView: weekStartField,
			});
			Dom.addClass(weekStartField.render(), '--row-frame_gray');
			const weekStartRow = new Row({
				content: weekStartField.render(),
			});
			new SettingsRow({
				row: weekStartRow,
				child: settingsField,
				parent: parent,
			});

			Dom.append(weekStartRow.render(), containerTab);
		}

		return containerTab;
	}

	#buildScheduleSection(): SettingsSection
	{
		if (!this.hasValue('sectionSchedule'))
		{
			return;
		}
		let scheduleSection = new Section(this.getValue('sectionSchedule'));

		const settingsSection = new SettingsSection({
			parent: this,
			section: scheduleSection
		})

		const tabsRow = new SettingsRow({
			parent: settingsSection,
		});

		const tabsField = new TabsField({
			parent: tabsRow,
		});

		const forCompanyTab = new TabField({
			parent: tabsField,
			tabsOptions: this.getValue('tabForCompany')
		});

		this.#buildScheduleTab(forCompanyTab)

		if (this.getValue('TIMEMAN').enabled)
		{
			const forDepartmentTab = new TabField({
				parent: tabsField,
				tabsOptions: this.getValue('tabForDepartment')
			});

			const forDepartmentRow = new Row({
				content: this.#forDepartmentsRender(),
			});

			new SettingsRow({
				row: forDepartmentRow,
				parent: forDepartmentTab,
			});
		}

		tabsField.activateTab(forCompanyTab);
		//endregion

		return settingsSection;
	}

	#buildHolidaysSection(): SettingsSection
	{
		if (!this.hasValue('sectionHoliday'))
		{
			return;
		}

		let holidaysSection = new Section(this.getValue('sectionHoliday'));

		const settingsSection = new SettingsSection({
			parent: this,
			section: holidaysSection
		})

		const countDays = this.getValue('year_holidays')?.value?.match(/\d{1,2}.\d{1,2}/gm)?.length ?? 0;
		let countDaysNode = Tag.render`<div class="ui-section__field-label">${Loc.getMessage('INTRANET_SETTINGS_FIELD_INFO', { '#COUNT_DAYS#': countDays })}</div>`;
		const holidaysRow = new Row({
			content: countDaysNode,
		});
		holidaysSection.append(holidaysRow.render());

		if (this.hasValue('year_holidays'))
		{
			const holidaysField = new TextInput(this.getValue('year_holidays'));
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
