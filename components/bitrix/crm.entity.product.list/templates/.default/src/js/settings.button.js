import {Popup} from 'main.popup';
import {ajax, Cache, Event, Loc, Tag, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Editor} from './product.list.editor';
import {DialogDisable, Slider, EventType} from 'catalog.store-use'

export default class SettingsPopup
{
	#target: HTMLElement;
	#settings: [];
	#editor: Editor;
	#cache = new Cache.MemoryCache();

	constructor(target: HTMLElement, settings = [], editor: Editor)
	{
		this.#target = target;
		this.#settings = settings;
		this.#editor = editor;
	}

	show()
	{
		this.getPopup().show();
	}

	getPopup()
	{
		return this.#cache.remember('settings-popup', () => {
			return new Popup(
				this.#editor.getId() + '_' + Math.random() * 100,
				this.#target,
				{
					autoHide: true,
					draggable: false,
					offsetLeft: 0,
					offsetTop: 0,
					angle: {position: 'top', offset: 43},
					noAllPaddings: true,
					bindOptions: {forceBindPosition: true},
					closeByEsc: true,
					content: this.#prepareSettingsContent()
				}
			);
		});
	}

	getSetting(id: string)
	{
		return this.#settings.filter(item => {
			return item.id === id;
		})[0];
	}

	#prepareSettingsContent(): HTMLElement
	{
		const content = Tag.render`
			<div class='ui-entity-editor-popup-create-field-list'></div>
		`;

		this.#settings.forEach(item => {
			content.append(this.#getSettingItem(item));
		});

		return content;
	}

	#getSettingItem(item): HTMLElement
	{
		const input = Tag.render`
			<input type="checkbox">
		`;
		input.checked = item.checked;
		input.disabled = item.disabled ?? false;
		input.dataset.settingId = item.id;

		const descriptionNode = (
			Type.isStringFilled(item.desc)
				? Tag.render`<span class="ui-entity-editor-popup-create-field-item-desc">${item.desc}</span>`
				: ''
		);

		const hintNode = (
			Type.isStringFilled(item.hint)
				? Tag.render`<span class="crm-entity-product-list-setting-hint" data-hint="${item.hint}"></span>`
				: ''
		);

		const setting = Tag.render`
			<label class="ui-ctl-block ui-entity-editor-popup-create-field-item ui-ctl-w100">
				<div class="ui-ctl-w10" style="text-align: center">${input}</div>
				<div class="ui-ctl-w75">
					<span class="ui-entity-editor-popup-create-field-item-title ${item.disabled ? 'crm-entity-product-list-disabled-setting' : ''}">${item.title}${hintNode}</span>
					${descriptionNode}
				</div>
			</label>
		`;

		BX.UI.Hint.init(setting);

		if (item.id === 'SLIDER')
		{
			Event.bind(setting, 'change', (event) =>
			{
				new Slider().open(item.url, {})
					.then(() => this.#editor.reloadGrid(false));
			})
		}
		else
		{
			Event.bind(setting, 'change', this.#setSetting.bind(this));
		}

		return setting;
	}

	#setSetting(event: BaseEvent): void
	{
		const settingItem = this.getSetting(event.target.dataset.settingId);
		if (!settingItem)
		{
			return;
		}

		const settingEnabled = event.target.checked;
		this.requestGridSettings(settingItem, settingEnabled);
	}

	requestGridSettings(setting, enabled)
	{
		const headers = [];
		const cells = this.#editor.getGrid().getRows().getHeadFirstChild().getCells();

		Array.from(cells).forEach((header) => {
			if ('name' in header.dataset)
			{
				headers.push(header.dataset.name);
			}
		});

		ajax.runComponentAction(
			this.#editor.getComponentName(),
			'setGridSetting',
			{
				mode: 'class',
				data: {
					signedParameters: this.#editor.getSignedParameters(),
					settingId: setting.id,
					selected: enabled,
					currentHeaders: headers
				}
			}
		).then(() => {
			let message;
			setting.checked = enabled;
			if (setting.id === 'ADD_NEW_ROW_TOP')
			{
				const panel = enabled ? 'top' : 'bottom';
				this.#editor.setSettingValue('newRowPosition', panel);
				const activePanel = this.#editor.changeActivePanelButtons(panel);
				const settingButton = activePanel.querySelector('[data-role="product-list-settings-button"]');
				this.getPopup().setBindElement(settingButton);

				message = enabled
					? Loc.getMessage('CRM_ENTITY_PL_SETTING_ENABLED')
					: Loc.getMessage('CRM_ENTITY_PL_SETTING_DISABLED');
				message = message.replace('#NAME#', setting.title);
			}
			else if(setting.id === 'WAREHOUSE')
			{
				this.#editor.reloadGrid(false);
				message = enabled
					? Loc.getMessage('CRM_ENTITY_CARD_WAREHOUSE_ENABLED')
					: Loc.getMessage('CRM_ENTITY_CARD_WAREHOUSE_DISABLED');
			}
			else
			{
				this.#editor.reloadGrid();

				message = enabled
					? Loc.getMessage('CRM_ENTITY_PL_SETTING_ENABLED')
					: Loc.getMessage('CRM_ENTITY_PL_SETTING_DISABLED');
				message = message.replace('#NAME#', setting.title)
			}
			this.getPopup().close();

			this.#showNotification(message, {
				category: 'popup-settings'
			});
		});
	}

	#showNotification(content: string, options)
	{
		options = options || {};

		BX.UI.Notification.Center.notify({
			content: content,
			stack: options.stack || null,
			position: 'top-right',
			width: 'auto',
			category: options.category || null,
			autoHideDelay: options.autoHideDelay || 3000
		});
	}

	updateCheckboxState(): void
	{
		const popupContainer = this.getPopup().getContentContainer();

		this.#settings
			.filter(item => item.action === 'grid' && Type.isArray(item.columns))
			.forEach(item => {
				let allColumnsExist = true;

				item.columns.forEach(columnName => {
					if (!this.#editor.getGrid().getColumnHeaderCellByName(columnName))
					{
						allColumnsExist = false;
					}
				})

				const checkbox = popupContainer.querySelector('input[data-setting-id="' + item.id + '"]');
				if (Type.isDomNode(checkbox))
				{
					checkbox.checked = allColumnsExist;
				}
			});
	}
}
