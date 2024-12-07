import { EventEmitter } from 'main.core.events';
import { AnalyticSettingsEvent } from '../analytic';
import { Checker, Selector, TextInput, InlineChecker } from 'ui.form-elements.view';
import { Loc } from 'main.core';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import {Section, Row, SeparatorRow} from 'ui.section';
import 'ui.forms';
import { SettingsSection, SettingsField, SettingsRow, BaseSettingsPage } from 'ui.form-elements.field';

export class EmployeePage extends BaseSettingsPage
{
	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_EMPLOYEE');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_EMPLOYEE_BOX');
	}

	onSuccessDataFetched(response): void
	{
		super.onSuccessDataFetched(response);

		if (this.hasValue('IS_BITRIX_24') && this.getValue('IS_BITRIX_24'))
		{
			this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_EMPLOYEE');
			this.render().querySelector('.intranet-settings__page-header_desc').innerText = this.descriptionPage;
		}
	}

	getType(): string
	{
		return 'employee';
	}

	appendSections(contentNode: HTMLElement): void
	{
		let profileSection = this.#buildProfileSection();
		profileSection?.renderTo(contentNode);

		let inviteSection = this.#buildInviteSection();
		inviteSection?.renderTo(contentNode);

		let additionalSection = this.#buildAdditionalSection();
		additionalSection?.renderTo(contentNode);
	}

	#buildAdditionalSection(): ?SettingsSection
	{
		if (!this.hasValue('SECTION_ADDITIONAL'))
		{
			return;
		}

		let additionalSection = new Section(this.getValue('SECTION_ADDITIONAL'));

		let sectionSettings = new SettingsSection({
			section: additionalSection,
			parent: this
		});

		if (this.hasValue('allow_company_pulse'))
		{
			let companyPulseField = new Checker(this.getValue('allow_company_pulse'));

			EventEmitter.subscribe(
				companyPulseField.switcher,
				'toggled',
				() =>
				{
					this.getAnalytic()?.addEventConfigEmployee(
						AnalyticSettingsEvent.CHANGE_QUICK_REG,
						companyPulseField.isChecked()
					);
				}
			);

			EmployeePage.addToSectionHelper(companyPulseField, sectionSettings);
		}

		return sectionSettings;
	}
	#buildProfileSection(): ?SettingsSection
	{
		if (!this.hasValue('SECTION_PROFILE'))
		{
			return;
		}

		let profileSection = new Section(this.getValue('SECTION_PROFILE'));

		let sectionSettings = new SettingsSection({
			section: profileSection,
			parent: this
		});

		if (this.hasValue('fieldFormatName'))
		{
			let hasSelectValue = false;
			let currentValue = this.getValue('fieldFormatName').current;
			for (let value of this.getValue('fieldFormatName').values)
			{
				if (value.selected === true)
				{
					hasSelectValue = true;
				}
			}
			this.getValue('fieldFormatName').values.push({
				value: 'other',
				name: Loc.getMessage('INTRANET_SETTINGS_FIELD_OPTION_OTHER'),
				selected: !hasSelectValue,
			});

			let nameFormatField = new Selector({
				label: this.getValue('fieldFormatName').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NAME_FORMAT'),
				name: this.getValue('fieldFormatName').name + '_selector',
				items: this.getValue('fieldFormatName').values,
				hints: this.getValue('fieldFormatName').hints,
				current: this.getValue('fieldFormatName').current,
			});

			let settingsField = new SettingsField({
				fieldView: nameFormatField
			});

			new SettingsRow({
				child: settingsField,
				parent: sectionSettings
			});

			let customFormatNameField = new TextInput({
				inputName: this.getValue('fieldFormatName').name,
				label: '',
				value: currentValue,
			});

			settingsField = new SettingsField({
				fieldView: customFormatNameField,
			});

			let customFormatNameRow = new Row({
				isHidden: true,
			});

			new SettingsRow({
				row: customFormatNameRow,
				parent: sectionSettings,
				child: settingsField,
			});

			if (!hasSelectValue)
			{
				customFormatNameRow.show();
			}

			nameFormatField.getInputNode()
				.addEventListener('change', (event) => {
					if (event.target.value === 'other')
					{
						customFormatNameRow.show();
					}
					else
					{
						customFormatNameField.getInputNode().value = nameFormatField.getInputNode().value;
						customFormatNameRow.hide();
					}
				});
		}

		if (this.hasValue('fieldFormatPhoneNumber'))
		{
			let formatNumberField = new Selector(this.getValue('fieldFormatPhoneNumber'));

			EmployeePage.addToSectionHelper(formatNumberField, sectionSettings);
		}

		if (this.hasValue('fieldFormatAddress'))
		{
			let addressFormatField = new Selector(this.getValue('fieldFormatAddress'));

			let addressFormatRow = new Row({
				separator: this.hasValue('show_year_for_female') ? 'bottom' : null,
				className: '--block',
			});

			EmployeePage.addToSectionHelper(addressFormatField, sectionSettings, addressFormatRow);
		}

		if (this.hasValue('show_year_for_female'))
		{
			let showBirthYearField = new InlineChecker(this.getValue('show_year_for_female'));

			EmployeePage.addToSectionHelper(showBirthYearField, sectionSettings);
		}

		return sectionSettings;
	}

	#buildInviteSection(): ?SettingsSection
	{
		if (!this.hasValue('SECTION_INVITE'))
		{
			return;
		}

		let inviteSection = new Section(this.getValue('SECTION_INVITE'));

		let sectionSettings = new SettingsSection({
			section: inviteSection,
			parent: this
		});

		if (this.hasValue('allow_register'))
		{
			let fastReqField = new Checker(this.getValue('allow_register'));

			EventEmitter.subscribe(
				fastReqField.switcher,
				'toggled',
				() =>
				{
					this.getAnalytic()?.addEventConfigEmployee(
						AnalyticSettingsEvent.CHANGE_QUICK_REG,
						fastReqField.isChecked()
					);
				}
			);

			EmployeePage.addToSectionHelper(fastReqField, sectionSettings);
		}

		if (this.hasValue('allow_invite_users'))
		{
			let inviteToUserField = new Checker(this.getValue('allow_invite_users'));

			EventEmitter.subscribe(
				inviteToUserField.switcher,
				'toggled',
				() =>
				{
					this.getAnalytic()?.addEventConfigEmployee(
						AnalyticSettingsEvent.CHANGE_REG_ALL,
						inviteToUserField.isChecked()
					);
				}
			);
			EmployeePage.addToSectionHelper(inviteToUserField, sectionSettings);
		}

		if (this.hasValue('show_fired_employees'))
		{
			let showQuitField = new Checker(this.getValue('show_fired_employees'));
			EmployeePage.addToSectionHelper(showQuitField, sectionSettings);
		}

		if (this.hasValue('general_chat_message_join'))
		{
			let newUserField = new Checker(this.getValue('general_chat_message_join'));

			EmployeePage.addToSectionHelper(newUserField, sectionSettings);
		}

		if (this.hasValue('allow_new_user_lf'))
		{
			let newUserLfField = new Checker(this.getValue('allow_new_user_lf'));
			EmployeePage.addToSectionHelper(newUserLfField, sectionSettings);
		}

		if (this.hasValue('feature_extranet'))
		{
			let extranetField = new Checker(this.getValue('feature_extranet'));

			EventEmitter.subscribe(
				extranetField.switcher,
				'toggled',
				() =>
				{
					this.getAnalytic()?.addEventConfigEmployee(
						AnalyticSettingsEvent.CHANGE_EXTRANET_INVITE,
						extranetField.isChecked()
					);
				}
			);

			EmployeePage.addToSectionHelper(extranetField, sectionSettings);
		}

		return sectionSettings;
	}


}
