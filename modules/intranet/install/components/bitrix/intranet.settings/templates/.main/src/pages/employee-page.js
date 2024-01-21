import { EventEmitter } from 'main.core.events';
import { AnalyticSettingsEvent } from '../analytic';
import { Checker, Selector, TextInput, InlineChecker } from 'ui.form-elements.view';
import { Loc } from 'main.core';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import { Section, Row } from 'ui.section';
import 'ui.forms';
import type { SectionSettings } from "crm.activity.todo-editor";
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
		profileSection.renderTo(contentNode);

		let inviteSection = this.#buildInviteSection();
		inviteSection.renderTo(contentNode);
	}

	#buildProfileSection(): SectionSettings
	{
		let profileSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_PROFILE'),
			titleIconClasses: 'ui-icon-set --person',
		});

		let sectionSettings = new SettingsSection({
			section: profileSection,
			parent: this
		});

		if (this.hasValue('NAME_FORMATS'))
		{
			let hasSelectValue = false;
			let currentValue = this.getValue('NAME_FORMATS').current;
			for (let value of this.getValue('NAME_FORMATS').values)
			{
				if (value.selected === true)
				{
					hasSelectValue = true;
				}
			}
			this.getValue('NAME_FORMATS').values.push({
				value: 'other',
				name: Loc.getMessage('INTRANET_SETTINGS_FIELD_OPTION_OTHER'),
				selected: !hasSelectValue,
			});

			let nameFormatField = new Selector({
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NAME_FORMAT'),
				name: this.getValue('NAME_FORMATS').name + '_selector',
				items: this.getValue('NAME_FORMATS').values,
				hints: this.getValue('NAME_FORMATS').hints,
				current: this.getValue('NAME_FORMATS').current,
			});

			let settingsField = new SettingsField({
				fieldView: nameFormatField
			});

			new SettingsRow({
				child: settingsField,
				parent: sectionSettings
			});

			let customFormatNameField = new TextInput({
				inputName: this.getValue('NAME_FORMATS').name,
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

		if (this.hasValue('PHONE_NUMBER_DEFAULT_COUNTRY'))
		{
			let formatNumberField = new Selector({
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COUNTRY_PHONE_NUMBER'),
				hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_NUMBER_FORMAT'),
				name: this.getValue('PHONE_NUMBER_DEFAULT_COUNTRY').name,
				items: this.getValue('PHONE_NUMBER_DEFAULT_COUNTRY').values,
				hints: this.getValue('PHONE_NUMBER_DEFAULT_COUNTRY').hints,
				current: this.getValue('PHONE_NUMBER_DEFAULT_COUNTRY').current,
			});

			EmployeePage.addToSectionHelper(formatNumberField, sectionSettings);
		}

		if (this.hasValue('LOCATION_ADDRESS_FORMAT_LIST'))
		{
			let addressFormatField = new Selector({
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ADDRESS_FORMAT'),
				hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_ADDRESS_FORMAT'),
				name: this.getValue('LOCATION_ADDRESS_FORMAT_LIST').name,
				items: this.getValue('LOCATION_ADDRESS_FORMAT_LIST').values,
				hints: this.getValue('LOCATION_ADDRESS_FORMAT_LIST').hints,
				current: this.getValue('LOCATION_ADDRESS_FORMAT_LIST').current,
			});

			let addressFormatRow = new Row({
				separator: this.hasValue('show_year_for_female') ? 'bottom' : null,
				className: '--block',
			});

			EmployeePage.addToSectionHelper(addressFormatField, sectionSettings, addressFormatRow);
		}

		if (this.hasValue('show_year_for_female'))
		{
			let showBirthYearField = new InlineChecker({
				inputName: 'show_year_for_female',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_BIRTH_YEAR'),
				hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_TITLE_BIRTH_YEAR'),
				hintOn: this.getValue('show_year_for_female').hintOn,
				hintOff: this.getValue('show_year_for_female').hintOff,
				checked: this.getValue('show_year_for_female').current === 'Y',
			});

			EmployeePage.addToSectionHelper(showBirthYearField, sectionSettings);
		}

		return sectionSettings;
	}

	#buildInviteSection(): SectionSettings
	{
		let inviteSection = new Section({
			title: Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_INVITE'),
			titleIconClasses: 'ui-icon-set --person-plus',
			isOpen: false,
		});

		let sectionSettings = new SettingsSection({
			section: inviteSection,
			parent: this
		});

		if (this.hasValue('allow_register'))
		{
			let fastReqField = new Checker({
				inputName: 'allow_register',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_FAST_REG'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_FAST_REQ_ON'),
				checked: this.getValue('allow_register') === 'Y',
				helpDesk: 'redirect=detail&code=17726876'
			});
			let fastReqRow = new Row({
				separator: 'bottom',
			});

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

			EmployeePage.addToSectionHelper(fastReqField, sectionSettings, fastReqRow);
		}

		if (this.hasValue('allow_invite_users'))
		{
			let inviteToUserField = new Checker({
				inputName: 'allow_invite_users',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_USERS_TO_INVITE'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_USERS_TO_INVITE_ON'),
				checked: this.getValue('allow_invite_users') === 'Y',
			});
			let inviteToUserRow = new Row({
				separator: 'bottom',
			});

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
			EmployeePage.addToSectionHelper(inviteToUserField, sectionSettings, inviteToUserRow);
		}

		if (this.hasValue('show_fired_employees'))
		{
			let showQuitField = new Checker({
				inputName: 'show_fired_employees',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_QUIT_EMPLOYEE'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_QUIT_EMPLOYEE_ON'),
				checked: this.getValue('show_fired_employees') === 'Y',
			});
			let showQuitRow = new Row({
				separator: 'bottom',
			});
			EmployeePage.addToSectionHelper(showQuitField, sectionSettings, showQuitRow);
		}

		if (this.hasValue('general_chat_message_join'))
		{
			let newUserField = new Checker({
				inputName: 'general_chat_message_join',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_NEW_EMPLOYEE'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_MESSAGE_NEW_EMPLOYEE_ON'),
				checked: this.getValue('general_chat_message_join') === 'Y',
			});
			let newUserRow = new Row({
				separator: 'bottom',
			});
			EmployeePage.addToSectionHelper(newUserField, sectionSettings, newUserRow);
		}

		if (this.hasValue('allow_new_user_lf'))
		{
			let newUserLfField = new Checker({
				inputName: 'allow_new_user_lf',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_MESSAGE_NEW_EMPLOYEE_LF'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_MESSAGE_NEW_EMPLOYEE_LF_ON'),
				checked: this.getValue('allow_new_user_lf') === 'Y',
			});
			let newUserLfRow = new Row({
				separator: 'bottom',
			});
			EmployeePage.addToSectionHelper(newUserLfField, sectionSettings, newUserLfRow);
		}

		if (this.hasValue('feature_extranet'))
		{
			let extranetField = new Checker({
				inputName: 'feature_extranet',
				title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_EXTRANET'),
				hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_EXTRANET_ON'),
				checked: this.getValue('feature_extranet') === 'Y',
				helpDesk: 'redirect=detail&code=17983050'
			});

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
