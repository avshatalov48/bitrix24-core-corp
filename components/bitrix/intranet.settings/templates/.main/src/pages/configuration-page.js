import { Event, Loc, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BaseSettingsPage, SettingsRow, SettingsSection } from 'ui.form-elements.field';
import { Checker, InlineChecker, Selector, TextInput, TextInputInline } from 'ui.form-elements.view';
import { Row, Section, SeparatorRow } from 'ui.section';
import { AnalyticSettingsEvent } from '../analytic';
import { PortalDeleteSection } from  '../configuration/portal-delete-section';

export class ConfigurationPage extends BaseSettingsPage
{
	#header;
	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_CONFIGURATION');
	}

	getType(): string
	{
		return 'configuration';
	}

	headerWidgetRender(): HTMLElement
	{
		let timeFormat = '';
		if (this.getValue('isFormat24Hour')?.current === 'Y')
		{
			timeFormat = this.getValue('format24HourTime');
		}
		else
		{
			timeFormat = this.getValue('format12HourTime');
		}

		this.#header = Tag.render`
		<div class="intranet-settings__date-widget_box">
			<span class="ui-icon-set --earth-language"></span>
			<div class="intranet-settings__date-widget_content">
				<div class="intranet-settings__date-widget_inner">
					<span data-role="time" class="intranet-settings__date-widget_title">${timeFormat}</span>
					<span class="intranet-settings__date-widget_subtitle">${this.getValue('offsetUTC')}</span>
				</div>
				<div data-role="date" class="intranet-settings__date-widget_subtitle">${this.getValue('currentDate')}</div>
			</div>
		</div>`;

		return this.#header;
	}

	appendSections(contentNode: HTMLElement): void
	{
		let dateTimeSection = this.#buildDateTimeSection();
		dateTimeSection?.renderTo(contentNode);

		let mailsSection = this.#buildMailsSection();
		mailsSection?.renderTo(contentNode);

		if (this.hasValue('mapsProviderCRM') && this.getValue('mapsProviderCRM'))
		{
			let mapsSection = this.#buildCRMMapsSection();
			mapsSection?.renderTo(contentNode);
		}

		let cardsProductPropertiesSection = this.#buildCardsProductPropertiesSection();
		cardsProductPropertiesSection?.renderTo(contentNode);

		let additionalSettingsSection = this.#buildAdditionalSettingsSection();
		additionalSettingsSection?.renderTo(contentNode);

		if (this.hasValue('deletePortalOptions') && this.hasValue('sectionDeletePortal'))
		{
			const deletePortalSection = new Section(this.getValue('sectionDeletePortal'));

			const settingsSection = new PortalDeleteSection({
				section: deletePortalSection,
				parent: this,
				options: this.getValue('deletePortalOptions'),
			});
			settingsSection.renderTo(contentNode);
		}
	}

	#buildDateTimeSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionDateFormat'))
		{
			return;
		}
		let dateTimeSection = new Section(this.getValue('sectionDateFormat'));

		const settingsSection = new SettingsSection({
			section: dateTimeSection,
			parent: this
		});

		if (this.hasValue('culture'))
		{
			let regionField = new Selector(this.getValue('culture'));
			ConfigurationPage.addToSectionHelper(regionField, settingsSection, new Row( { className: '--intranet-settings__mb-20' } ));

			Event.bind(regionField.getInputNode(), 'change', (event) => {
				this.#header.querySelector('[data-role="date"]').innerHTML = this.getValue('longDates')[event.target.value];
			});
		}

		if (this.hasValue('isFormat24Hour'))
		{
			let format24Time = new InlineChecker(this.getValue('isFormat24Hour'));
			ConfigurationPage.addToSectionHelper(format24Time, settingsSection);

			EventEmitter.subscribe(format24Time, 'change', (event) => {
				this.#header.querySelector('[data-role="time"]').innerHTML = format24Time.isChecked()
					? this.getValue('format24HourTime')
					: this.getValue('format12HourTime');
			});
		}

		return settingsSection;
	}

	#buildMailsSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionLetters'))
		{
			return;
		}

		let mailsSection = new Section(this.getValue('sectionLetters'));

		const settingsSection = new SettingsSection({
			section: mailsSection,
			parent: this
		});

		if (this.hasValue('trackOutMailsRead'))
		{
			let trackOutLettersRead = new Checker(this.getValue('trackOutMailsRead'));
			let showQuitRow = new Row({});
			ConfigurationPage.addToSectionHelper(trackOutLettersRead, settingsSection, showQuitRow);
		}

		if (this.hasValue('trackOutMailsClick'))
		{
			let trackOutMailsClick = new Checker(this.getValue('trackOutMailsClick'));
			let showQuitRow = new Row({});

			ConfigurationPage.addToSectionHelper(trackOutMailsClick, settingsSection, showQuitRow);
		}

		if (this.hasValue('defaultEmailFrom'))
		{
			let defaultEmailFrom = new TextInput(this.getValue('defaultEmailFrom'));
			let showQuitRow = new Row({});

			ConfigurationPage.addToSectionHelper(defaultEmailFrom, settingsSection, showQuitRow);
		}

		return settingsSection;
	}

	#buildCRMMapsSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionMapsInCrm'))
		{
			return;
		}
		let mapsSection = new Section(this.getValue('sectionMapsInCrm'));

		const settingsSection = new SettingsSection({
			section: mapsSection,
			parent: this
		});

		let cardsProvider = new Selector({
			label: this.getValue('mapsProviderCRM').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
			name: this.getValue('mapsProviderCRM').name,
			items: this.getValue('mapsProviderCRM').values,
			current: this.getValue('mapsProviderCRM').current,
		});
		let cardsProviderRow = new Row({
			separator: 'bottom',
			className: '--block',
		});
		ConfigurationPage.addToSectionHelper(cardsProvider, settingsSection, cardsProviderRow);

		const separatorRow = new SeparatorRow({
			isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
		});
		new SettingsRow({
			row: separatorRow,
			parent: settingsSection,
		});

		const description = new BX.UI.Alert({
			text: Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_DESCRIPTION', {'#GOOGLE_API_URL#': this.getValue('googleApiUrl')}),
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		});

		const descriptionRow = new Row({
			separator: 'top',
			content: description.getContainer(),
			isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
		});
		new SettingsRow({
			row: descriptionRow,
			parent: settingsSection
		});

		const googleKeyFrontend = new TextInputInline({
			inputName: 'API_KEY_FRONTEND',
			label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_PUBLIC'),
			hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_PUBLIC_HINT'),
			value: this.getValue('API_KEY_FRONTEND').value,
			placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER'),
		});
		const googleKeyFrontendRow = new Row({
			isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
		});

		ConfigurationPage.addToSectionHelper(googleKeyFrontend, settingsSection, googleKeyFrontendRow);

		const mapApiKeyBackend = new TextInputInline({
			inputName: 'API_KEY_BACKEND',
			label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_SERVER'),
			hintTitle: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_GOOGLE_KEY_SERVER_HINT'),
			value: this.getValue('API_KEY_BACKEND').value,
			placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER'),
		});

		const googleKeyBackendRow = new Row({
			content: mapApiKeyBackend.render(),
			isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
			separator: 'bottom',
			className: '--block',
		});
		ConfigurationPage.addToSectionHelper(mapApiKeyBackend, settingsSection, googleKeyBackendRow);

		const separatorRow1 = new SeparatorRow({

		});
		new SettingsRow({
			row: separatorRow1,
			parent: settingsSection,
		});

		let showPhotoPlacesMaps = new Checker({
			inputName: 'SHOW_PHOTOS_ON_MAP',
			title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_PHOTO_PLACES_MAPS'),
			hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
			hintOff: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
			checked: this.getValue('SHOW_PHOTOS_ON_MAP').value === '1'
		});
		let showPhotoPlacesMapsRow = new Row({
			separator: 'top',
			className: '--block',
			content: showPhotoPlacesMaps.render(),
			isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
		});
		ConfigurationPage.addToSectionHelper(showPhotoPlacesMaps, settingsSection, showPhotoPlacesMapsRow);

		let useGeocodingService = new Checker({
			inputName: 'USE_GEOCODING_SERVICE',
			title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_GEOCODING_SERVICE'),
			hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
			hintOff: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_PHOTO_PLACES_MAPS_CLICK_ON'),
			checked: this.getValue('USE_GEOCODING_SERVICE').value === '1',
		});
		let useGeocodingServiceRow = new Row({
			content: useGeocodingService.render(),
			isHidden: this.getValue('mapsProviderCRM').current === 'OSM',
		});

		ConfigurationPage.addToSectionHelper(useGeocodingService, settingsSection, useGeocodingServiceRow);

		cardsProvider.getInputNode()
			.addEventListener('change', (event) => {
				if (event.target.value === 'OSM')
				{
					separatorRow.hide();
					descriptionRow.hide();
					googleKeyFrontendRow.hide();
					googleKeyBackendRow.hide();
					useGeocodingServiceRow.hide();
					showPhotoPlacesMapsRow.hide();
				}
				else
				{
					separatorRow.show();
					descriptionRow.show();
					googleKeyFrontendRow.show();
					googleKeyBackendRow.show();
					useGeocodingServiceRow.show();
					showPhotoPlacesMapsRow.show();
				}
			});

		return settingsSection;
	}

	#buildCardsProductPropertiesSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionMapsInProduct'))
		{
			return;
		}
		let productPropertiesSection = new Section(this.getValue('sectionMapsInProduct'));

		const settingsSection = new SettingsSection({
			section: productPropertiesSection,
			parent: this
		})

		if (this.hasValue('cardsProviderProductProperties'))
		{
			let cardsProviderProductProperties = new Selector({
				label: this.getValue('cardsProviderProductProperties').label ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_CHOOSE_REGION_CRM_MAPS'),
				name: this.getValue('cardsProviderProductProperties').name,
				items: this.getValue('cardsProviderProductProperties').values,
				current: this.getValue('cardsProviderProductProperties').current,
			});
			let cardsProviderProductPropertiesRow = new Row({
				separator: 'bottom'
			});
			ConfigurationPage.addToSectionHelper(cardsProviderProductProperties, settingsSection, cardsProviderProductPropertiesRow);

			new SettingsRow({
				row: new SeparatorRow(),
				parent: settingsSection,
			});

			const descriptionYandex = new BX.UI.Alert({
				text: Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_YANDEX_DESCRIPTION', {'#YANDEX_API_URL#': this.getValue('yandexApiUrl')}),
				inline: true,
				size: BX.UI.Alert.Size.SMALL,
				color: BX.UI.Alert.Color.PRIMARY,
				animated: true,
			});
			const descriptionYandexRow = new Row({
				content: descriptionYandex.getContainer(),
				isHidden: this.getValue('cardsProviderProductProperties').current !== 'yandex',
			});
			new SettingsRow({
				row: descriptionYandexRow,
				parent: settingsSection
			});

			const yandexKeyProductProperties = new TextInput({
				inputName: 'yandexKeyProductProperties',
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAP_PRODUCT_PROPERTIES_YANDEX_KEY'),
				value: this.getValue('yandexKeyProductProperties'),
				placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER'),
			});
			const yandexKeyProductPropertiesRow = new Row({
				separator: 'bottom',
				className: '--block',
				content: yandexKeyProductProperties.render(),
				isHidden: this.getValue('cardsProviderProductProperties').current !== 'yandex',
			});

			ConfigurationPage.addToSectionHelper(yandexKeyProductProperties, settingsSection, yandexKeyProductPropertiesRow);

			const descriptionGoogle = new BX.UI.Alert({
				text: Loc.getMessage('INTRANET_SETTINGS_SECTION_CRM_MAPS_DESCRIPTION', {'#GOOGLE_API_URL#': this.getValue('googleApiUrl')}),
				inline: true,
				size: BX.UI.Alert.Size.SMALL,
				color: BX.UI.Alert.Color.PRIMARY,
				animated: true,
			});

			const descriptionGoogleRow = new Row({
				content: descriptionGoogle.getContainer(),
				isHidden: this.getValue('cardsProviderProductProperties').current !== 'google',
			});

			new SettingsRow({
				row: descriptionGoogleRow,
				parent: settingsSection
			});

			const googleKeyProductProperties = new TextInput({
				inputName: 'googleKeyProductProperties',
				label: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_MAP_PRODUCT_PROPERTIES_GOOGLE_KEY'),
				value: this.getValue('googleKeyProductProperties'),
				placeholder: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_TEXT_KEY_PLACEHOLDER'),
			});
			const googleKeyProductPropertiesRow = new Row({
				content: googleKeyProductProperties.render(),
				isHidden: this.getValue('cardsProviderProductProperties').current !== 'google',
			});
			ConfigurationPage.addToSectionHelper(googleKeyProductProperties, settingsSection, googleKeyProductPropertiesRow);

			cardsProviderProductProperties.getInputNode()
				.addEventListener('change', (event) => {
					if (event.target.value === 'yandex')
					{
						descriptionYandexRow.show();
						yandexKeyProductPropertiesRow.show();
						descriptionGoogleRow.hide();
						googleKeyProductPropertiesRow.hide();
					}
					else
					{
						descriptionYandexRow.hide();
						yandexKeyProductPropertiesRow.hide();
						descriptionGoogleRow.show();
						googleKeyProductPropertiesRow.show();
					}
				});
		}

		return settingsSection;
	}

	#buildAdditionalSettingsSection(): ?SettingsSection
	{
		if (!this.hasValue('sectionOther'))
		{
			return;
		}
		let additionalSettingsSection = new Section(this.getValue('sectionOther'));

		const settingsSection = new SettingsSection({
			section: additionalSettingsSection,
			parent: this
		});

		if (this.hasValue('allowUserInstallApplication'))
		{
			let allInstallMarketApplication = new Checker(this.getValue('allowUserInstallApplication'));
			let allInstallMarketApplicationRow = new Row({});

			EventEmitter.subscribe(
				allInstallMarketApplication.switcher,
				'toggled',
				() =>
				{
					this.getAnalytic()?.addEventConfigConfiguration(
						AnalyticSettingsEvent.CHANGE_MARKET,
						allInstallMarketApplication.isChecked()
					);
				}
			);

			ConfigurationPage.addToSectionHelper(allInstallMarketApplication, settingsSection, allInstallMarketApplicationRow);
		}

		if (this.hasValue('allCanBuyTariff'))
		{
			const messageNode = Tag.render`<span>${Loc.getMessage(
				'INTRANET_SETTINGS_FIELD_HELP_MESSAGE'
			)}</span>`;
			let allCanBuyTariff = new Checker({
				inputName: this.getValue('allCanBuyTariff').inputName,
				title: this.getValue('allCanBuyTariff').title ?? Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_ALL_CAN_BUY_TARIFF'),
				hintOn: this.getValue('allCanBuyTariff').hintOn,
				checked: this.getValue('allCanBuyTariff').checked,
				isEnable: this.getValue('allCanBuyTariff').isEnable,
				bannerCode: 'limit_why_pay_tariff_everyone',
				helpMessageProvider: this.helpMessageProviderFactory(messageNode),
			});
			let allCanBuyTariffRow = new Row({});

			EventEmitter.subscribe(
				allCanBuyTariff.switcher,
				'toggled',
				() =>
				{
					this.getAnalytic()?.addEventConfigConfiguration(
						AnalyticSettingsEvent.CHANGE_PAY_TARIFF,
						allCanBuyTariff.isChecked()
					);
				}
			);

			ConfigurationPage.addToSectionHelper(allCanBuyTariff, settingsSection, allCanBuyTariffRow);
		}

		if (this.hasValue('allowMeasureStressLevel'))
		{
			let allowMeasureStressLevel = new Checker(this.getValue('allowMeasureStressLevel'));
			let allowMeasureStressLevelRow = new Row({});
			ConfigurationPage.addToSectionHelper(allowMeasureStressLevel, settingsSection, allowMeasureStressLevelRow);
		}

		if (this.hasValue('collectGeoData'))
		{
			let collectGeoData = new Checker(this.getValue('collectGeoData'));

			EventEmitter.subscribe(
				collectGeoData.switcher,
				'toggled',
				() =>
				{
					this.#geoDataSwitch(collectGeoData);
				}
			);

			ConfigurationPage.addToSectionHelper(collectGeoData, settingsSection);
		}

		// This is hidden
		// if (this.hasValue('showSettingsAllUsers'))
		// {
		// 	let showSettingsAllUsers = new Checker({
		// 		inputName: 'showSettingsAllUsers',
		// 		title: Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_SHOW_SETTINGS_ALL_USER'),
		// 		hintOn: Loc.getMessage('INTRANET_SETTINGS_FIELD_HINT_SHOW_SETTINGS_ALL_USER_CLICK_ON'),
		// 		checked: this.getValue('showSettingsAllUsers') === 'Y'
		// 	});
		// 	let showSettingsAllUsersRow = new Row({
		// 		content: showSettingsAllUsers.render(),
		// 		isHidden: true
		// 	});
		// 	ConfigurationPage.addToSectionHelper(showSettingsAllUsers, settingsSection, showSettingsAllUsersRow);
		// }

		return settingsSection;
	}

	#geoDataSwitch(element)
	{
		if (element.isChecked())
		{
			BX.UI.Dialogs.MessageBox.show({
				'modal': true,
				'minWidth': 640,
				'title': Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA'),
				'message': Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA_CONFIRM'),
				'buttons': BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
				'okCaption': Loc.getMessage('INTRANET_SETTINGS_FIELD_LABEL_COLLECT_GEO_DATA_OK'),
				'onCancel': function ()
				{
					element.switcher.check(false);
					return true;
				},
				'onOk': function ()
				{
					return true;
				}

			});
		}
	}
}
