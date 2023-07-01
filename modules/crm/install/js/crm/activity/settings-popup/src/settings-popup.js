import { ajax as Ajax, Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';
import { Button, ButtonColor, ButtonState, CancelButton, SaveButton } from 'ui.buttons';
import { UI } from 'ui.notification';
import { Wrapper as SettingsPopupWrapper } from './components/wrapper/wrapper';
import { BitrixVue } from 'ui.vue3';
import { Calendar } from './components/calendar/calendar';
import { Ping } from './components/ping/ping';

import type { SettingsPopupOptions } from './settings-popup-options';

import 'ui.design-tokens';
import './settings-popup.css';

const SAVE_BUTTON_ID = 'save';
const CANCEL_BUTTON_ID = 'cancel';

const Events = {
	EVENT_SETTINGS_CHANGE: 'crm:settings-popup:settings-change',
	EVENT_SETTINGS_VALIDATION: 'crm:settings-popup:settings-validation',
};

class SettingsPopup
{
	container: HTMLElement = null;
	layoutApp = null;
	layoutComponent = null;
	popup: ?Popup = null;
	#onSettingsChange: ?Function = null;
	#onSave: ?Function = null;
	#settingsSections: Object[] = [];
	#fetchSettingsPath: ?String = null;
	#ownerTypeId: ?Number = null;
	#ownerId: ?Number = null;
	#id: ?Number = null;
	#currentSettings: ?Object = null;

	constructor(options: SettingsPopupOptions)
	{
		this.#settingsSections = options.sections || [];
		this.#fetchSettingsPath = options.fetchSettingsPath || null;
		this.#ownerTypeId = options.ownerTypeId || null;
		this.#ownerId = options.ownerId || null;
		this.#id = options.id || null;
		this.#onSettingsChange = options.onSettingsChange || null;
		this.#currentSettings = options.settings || null;

		if (options.onSave)
		{
			this.#onSave = options.onSave;
		}
	}

	#onSettingsValidation(data)
	{
		if (this.popup)
		{
			this.popup.buttons[0].setDisabled(!data.isValid);
		}
	}

	show(): void
	{
		if (!this.popup || this.popup.isDestroyed())
		{
			const htmlStyles = getComputedStyle(document.documentElement);
			const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
			const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
			const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';

			const content = Tag.render`
				<div class="crm-activity__settings">
					<div class="crm-activity__settings_title">${this.#getPopupTitle()}</div>
					<div class="crm-activity__todo-settings_content"></div>
				</div>
			`;

			this.popup = new Popup({
				closeIcon: true,
				closeByEsc: true,
				padding: popupPaddingNumberValue,
				overlay: {
					opacity: 40,
					backgroundColor: popupOverlayColor,
				},
				content,
				buttons: this.#getPopupButtons(),
				minWidth: 850,
				width: 850,
				className: 'crm-activity__settings-popup'
			});

			this.popup.subscribeOnce('onFirstShow', () => {
				this.loadSettings()
					.then(
						() => {
							this.#getPopupContent();
							this.popup.adjustPosition();
						},
						() => {
							UI.Notification.Center.notify({
								content: Loc.getMessage('CRM_SETTINGS_POPUP_ERROR'),
								autoHideDelay: 5000,
							});
						}
					);
			});

			this.popup.subscribe('onClose', this.#initLayoutComponent.bind(this));
		}

		this.popup.show();
	}

	#getPopupContent(): HTMLElement
	{
		this.container = this.popup
			.getContentContainer()
			.getElementsByClassName('crm-activity__todo-settings_content')
			.item(0)
		;

		this.#initLayoutComponent();

		return this.layoutComponent;
	}

	loadSettings(): Promise
	{
		if (!this.#fetchSettingsPath)
		{
			return Promise.resolve();
		}

		const data = {
			id: this.#id,
			ownerTypeId: this.#ownerTypeId,
			ownerId: this.#ownerId,
		}

		return new Promise((resolve, reject) => {
			Ajax
				.runAction(this.#fetchSettingsPath, { data })
				.then(({ data }) => {
					data.forEach(item => {
						const section = this.#settingsSections.find(settingsSection => settingsSection.id === item.id);
						if (!section)
						{
							return;
						}

						section.active = item.active;
						section.params = item.settings;
					});

					resolve();
				})
				.catch(reject)
			;
		});
	}

	#getPopupTitle(): string
	{
		return Loc.getMessage('CRM_SETTINGS_POPUP_TITLE');
	}

	#getPopupButtons(): ReadonlyArray<Button>
	{
		return [
			new SaveButton({
				id: SAVE_BUTTON_ID,
				round: true,
				state: ButtonState.ACTIVE,
				events: {
					click: this.save.bind(this),
				},
			}),
			new CancelButton({
				id: CANCEL_BUTTON_ID,
				round: true,
				events: {
					click: this.#cancel.bind(this),
				},
				text: Loc.getMessage('CRM_SETTINGS_POPUP_CANCEL'),
				color: ButtonColor.LIGHT_BORDER,
			}),
		]
	}

	save(): void
	{
		this.#currentSettings = this.getSettings();

		if (this.#onSettingsChange)
		{
			this.#onSettingsChange(this.getSettings());
		}

		this.#closePopup();

		if (this.#onSave)
		{
			this.#onSave(this.#ownerTypeId, this.#ownerId, this.#id, this.getSettings());
		}
	}

	#cancel(): void
	{
		this.#closePopup();
		this.#initLayoutComponent();
	}

	#initLayoutComponent(): void
	{
		if (this.layoutApp && this.layoutComponent)
		{
			this.layoutApp.unmount(this.container);
		}

		if (this.#currentSettings)
		{
			this.#settingsSections.forEach(section => {
				if (this.#currentSettings[section.id])
				{
					section.active = true;
					section.params = this.#currentSettings[section.id];
				}
			});
		}

		this.layoutApp = BitrixVue.createApp(SettingsPopupWrapper, {
			onSettingsChangeCallback: this.#adjustPopup.bind(this),
			onSettingsValidationCallback: this.#onSettingsValidation.bind(this),
			sections: this.#settingsSections,
		});

		this.layoutComponent = this.layoutApp.mount(this.container);
	}

	#adjustPopup()
	{
		this.popup.adjustPosition();
		this.popup.buttons[0].setDisabled(false);
	}

	#closePopup(): void
	{
		this.popup?.close();
	}

	getSettings(): Object
	{
		return this.layoutComponent?.exportParams();
	}

	syncSettings(data: Object | null = null): void
	{
		if (data && this.layoutComponent)
		{
			this.layoutComponent.updateSettings(data);
		}
	}
}

export {
	Calendar,
	Ping,
	SettingsPopup,
	Events,
};
