import { Dom, Loc, Tag } from 'main.core';
import 'ui.icon-set.main';
import { Section, Row } from 'ui.section';
import { TextInput } from 'ui.form-elements.view';
import { SettingsSection, SettingsField, SettingsRow, BaseSettingsPage } from 'ui.form-elements.field';

export class GdprPage extends BaseSettingsPage
{
	constructor() {
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_GDPR');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_GDPR');
	}

	getType(): string
	{
		return 'gdpr';
	}

	appendSections(contentNode: HTMLElement)
	{
		let gdprSection = this.#buildGdprSection();
		gdprSection?.renderTo(contentNode);
	}

	#buildGdprSection(): SettingsSection
	{
		if (!this.hasValue('sectionGdpr'))
		{
			return;
		}
		let gdprSection = new Section(this.getValue('sectionGdpr'));

		let sectionSettings = new SettingsSection({
			section: gdprSection,
			parent: this
		});

		const description = new BX.UI.Alert({
			text: `
				${Loc.getMessage('INTRANET_SETTINGS_SECTION_GDPR_DESCRIPTION')}
				<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=7608199')">
					${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
				</a>
				</br>
				<a class="ui-section__link" href="${this.getValue('dpaLink')}" target="_blank">
					${Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_AGREEMENT')}
				</a>
			`,
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		});

		const descriptionRow = new Row({
			content: description.getContainer(),
		});

		new SettingsRow({
			row: descriptionRow,
			parent: sectionSettings
		});

		if (this.hasValue('companyTitle'))
		{
			const titleField = new TextInput({
				inputName: 'companyTitle',
				label: this.getValue('companyTitle').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COMPANY_TITLE'),
				value: this.getValue('companyTitle').value,
				placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_COMPANY_TITLE'),
			});

			let settingsField = new SettingsField({
				fieldView: titleField
			});
			new SettingsRow({
				parent: sectionSettings,
				child: settingsField
			});
		}

		if (this.hasValue('contactName'))
		{
			const contactNameField = new TextInput({
				inputName: 'contactName',
				label: this.getValue('contactName').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CONTACT_NAME'),
				value: this.getValue('contactName').value,
				placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_CONTACT_NAME'),
			});

			let settingsField = new SettingsField({
				fieldView: contactNameField
			});
			new SettingsRow({
				parent: sectionSettings,
				child: settingsField
			});
		}

		if (this.hasValue('notificationEmail'))
		{
			const emailField = new TextInput({
				inputName: 'notificationEmail',
				label: this.getValue('notificationEmail').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_NOTIFICATION_EMAIL'),
				value: this.getValue('notificationEmail').value,
				placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_PLACEHOLDER_NOTIFICATION_EMAIL'),
			});

			let settingsField = new SettingsField({
				fieldView: emailField,
			});

			new SettingsRow({
				parent: sectionSettings,
				child: settingsField
			});
		}

		if (this.hasValue('date'))
		{
			const dateField = new TextInput({
				inputName: 'date',
				label: this.getValue('date').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_DATE'),
				value: this.getValue('date').value,
			});

			Dom.adjust(dateField.render(), {
				events: {
					click: (event) => {
						BX.calendar({
							node: event.target,
							field: 'date',
							form: '',
							bTime: false,
							bHideTime: true,
						});
					},
				},
			});

			let settingsField = new SettingsField({
				fieldView: dateField,
			});

			new SettingsRow({
				parent: sectionSettings,
				child: settingsField
			});
		}

		new SettingsRow({
			row: new Row({
				content: this.addApplicationsRender()
			}),
			parent: sectionSettings,
		});

		return sectionSettings;
	}

	addApplicationsRender(): HTMLElement
	{
		if (this.hasValue('marketDirectory'))
		{
			const marketDirectory = this.getValue('marketDirectory');

			return Tag.render`
				<div class="ui-text-right">
					<a class="ui-section__link" href="${marketDirectory}detail/integrations24.gdprstaff/">
						${Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_APPLICATION_EMPLOYEE')}
					</a>
					<a class="ui-section__link" style="margin-left: 12px;" href="${marketDirectory}detail/integrations24.gdpr/">
						${Loc.getMessage('INTRANET_SETTINGS_BUTTON_GDPR_APPLICATION_CRM')}
					</a>
				</div>
			`;
		}

		return null;
	}
}