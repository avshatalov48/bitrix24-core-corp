/* eslint-disable no-underscore-dangle, @bitrix24/bitrix24-rules/no-pseudo-private */

import Wait from '../wait';
import { Loc, Tag, Dom, Text, Runtime } from 'main.core';

/** @memberof BX.Crm.Timeline.Tools */
export default class WaitConfigurationDialog
{
	_id: string;
	_settings: Object;
	_type: number;
	_duration: number;
	_target: string;
	_targetDates: Array;
	_container: HTMLElement;
	_durationInput: HTMLInputElement;
	_targetDateNode: HTMLElement;
	_popup = null;
	_menuId = null;

	constructor()
	{
		this._id = '';
		this._settings = {};
		this._type = Wait.WaitingType.undefined;
		this._duration = 0;
		this._target = '';
		this._targetDates = [];
		this._container = null;
		this._durationInput = null;
		this._targetDateNode = null;
		this._popup = null;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings || {};
		this._type = BX.prop.getInteger(this._settings, 'type', Wait.WaitingType.after);
		this._duration = BX.prop.getInteger(this._settings, 'duration', 1);
		this._target = BX.prop.getString(this._settings, 'target', '');
		this._targetDates = BX.prop.getArray(this._settings, 'targetDates', []);

		this._menuId = `${this._id}_target_date_sel`;
	}

	getId(): string
	{
		return this._id;
	}

	getType(): number
	{
		return this._type;
	}

	setType(type: number): void
	{
		this._type = type;
	}

	getDuration(): number
	{
		return this._duration;
	}

	setDuration(duration: number): void
	{
		this._duration = duration;
	}

	getTarget(): string
	{
		return this._target;
	}

	setTarget(target: string): void
	{
		this._target = target;
	}

	getMessage(name)
	{
		const messages = WaitConfigurationDialog.messages;

		return Object.prototype.hasOwnProperty.call(messages, name)
			? messages[name]
			: name
		;
	}

	getTargetDateCaption(): string
	{
		return this._targetDates.find((targetDate) => targetDate.name === this._target)?.caption ?? '';
	}

	isBeforeWaitingType(): boolean
	{
		return this.getType() === Wait.WaitingType.before;
	}

	open()
	{
		this._popup = new BX.PopupWindow(
			this._id,
			null, // this._configSelector,
			{
				autoHide: true,
				draggable: false,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				zIndex: 0,
				content: this.renderDialogContent(),
				events:
					{
						onPopupShow: this.onPopupShow.bind(this),
						onPopupClose: this.onPopupClose.bind(this),
						onPopupDestroy: this.onPopupDestroy.bind(this),
					},
				buttons:
					[
						new BX.PopupWindowButton(
							{
								text: Loc.getMessage('CRM_TIMELINE_CHOOSE'),
								className: 'popup-window-button-accept',
								events: { click: this.onSaveButtonClick.bind(this) },
							},
						),
						new BX.PopupWindowButtonLink(
							{
								text: Loc.getMessage('JS_CORE_WINDOW_CANCEL'),
								events: { click: this.onCancelButtonClick.bind(this) },
							},
						),
					],
			},
		);

		this._popup.show();
	}

	close()
	{
		if (this._popup)
		{
			this._popup.close();
		}
	}

	renderDialogContent(): HTMLElement
	{
		const container = this.getContainer();
		container.innerHTML = '';

		const wrapper = Tag.render`<div class="crm-wait-popup-select-wrapper"></div>`;

		const contentTextNode = this.getContentTextNode();
		this.appendDurationInput(contentTextNode);
		this.appendTargetDateNode(contentTextNode);

		Dom.append(contentTextNode, wrapper);
		Dom.append(wrapper, container);

		return container;
	}

	getContainer(): HTMLElement
	{
		if (!this._container)
		{
			this._container = Tag.render`<div class="crm-wait-popup-select-block"></div>`;
		}

		return this._container;
	}

	getContentTextNode(): HTMLElement
	{
		const phraseCode = this.isBeforeWaitingType()
			? 'CRM_TIMELINE_WAIT_CONFIG_DIALOG_BEFORE_CONTENT_TEXT'
			: 'CRM_TIMELINE_WAIT_CONFIG_DIALOG_AFTER_CONTENT_TEXT'
		;

		// put a container so that in the future you can put the input there
		const replacement = {
			'#DAY_INPUT#': `<span class="crm-wait-duration-input-container" id="${this.getDurationInputContainerId()}"></span>`,
			'#TARGET_DATE#': this.isBeforeWaitingType()
				? `<span class="crm-wait-target-date-container" id="${this.getTargetDateNodeContainerId()}"></span>`
				: null
			,
		};

		return Tag.render`
			<span class="crm-wait-text-wrapper crm-wait-popup-settings-title">
				${Loc.getMessagePlural(phraseCode, this.getDuration(), replacement)}
			</span>
		`;
	}

	getDurationInputContainerId(): string
	{
		return `crm-wait-duration-input-container-${this.getId()}`;
	}

	getDurationInput(): HTMLInputElement
	{
		if (!this._durationInput)
		{
			this._durationInput = Tag.render`
				<input type="text" class="crm-wait-popup-settings-input" value="${this.getDuration()}">
			`;

			this._durationInput.onkeyup = Runtime.debounce(this.onDurationChange.bind(this), 300);
		}

		return this._durationInput;
	}

	appendDurationInput(contentTextNode: HTMLElement): void
	{
		const containerId = this.getDurationInputContainerId();
		const container = contentTextNode.querySelector(`#${containerId}`);

		Dom.append(this.getDurationInput(), container);
	}

	onDurationChange(): void
	{
		let duration = parseInt(this.getDurationInput().value, 10);
		if (Number.isNaN(duration) || duration <= 0)
		{
			duration = 1;
		}

		this._duration = duration;

		this.renderDialogContent();
		this.getDurationInput().focus();
	}

	getTargetDateNodeContainerId(): string
	{
		return `crm-wait-configuration-dialog-target-date-container-${this.getId()}`;
	}

	getTargetDateNode(): HTMLElement
	{
		if (!this._targetDateNode)
		{
			this._targetDateNode = Tag.render`
				<span class="crm-automation-popup-settings-link">
					${Text.encode(this.getTargetDateCaption(this._target))}
				</span>
			`;

			this._targetDateNode.onclick = this.toggleTargetMenu.bind(this);
		}

		return this._targetDateNode;
	}

	appendTargetDateNode(contentTextNode: HTMLElement): void
	{
		if (!this.isBeforeWaitingType())
		{
			return;
		}

		const containerId = this.getTargetDateNodeContainerId();
		const container = contentTextNode.querySelector(`#${containerId}`);

		Dom.append(this.getTargetDateNode(), container);
	}

	toggleTargetMenu(): void
	{
		if (this.isTargetMenuOpened())
		{
			this.closeTargetMenu();
		}
		else
		{
			this.openTargetMenu();
		}
	}

	isTargetMenuOpened(): boolean
	{
		return Boolean(BX.PopupMenu.getMenuById(this._menuId));
	}

	openTargetMenu(): void
	{
		const menuItems = [];
		let i = 0;
		const length = this._targetDates.length;
		for (; i < length; i++)
		{
			const info = this._targetDates[i];

			menuItems.push(
				{
					text: info.caption,
					title: info.caption,
					value: info.name,
					onclick: this.onTargetSelect.bind(this),
				},
			);
		}

		BX.PopupMenu.show(
			this._menuId,
			this._targetDateNode,
			menuItems,
			{
				zIndex: 200,
				autoHide: true,
				offsetLeft: Dom.getPosition(this.getTargetDateNode()).width / 2,
				angle: { position: 'top', offset: 0 },
			},
		);
	}

	closeTargetMenu(): void
	{
		BX.PopupMenu.destroy(this._menuId);
	}

	onPopupShow(e, item): void
	{}

	onPopupClose(): void
	{
		if (this._popup)
		{
			this._popup.destroy();
		}

		this.closeTargetMenu();
	}

	onPopupDestroy(): void
	{
		if (this._popup)
		{
			this._popup = null;
		}
	}

	onSaveButtonClick(e): void
	{
		this.onDurationChange();
		const callback = BX.prop.getFunction(this._settings, 'onSave', null);
		if (!callback)
		{
			return;
		}

		callback(this, {
			type: this.getType(),
			duration: this.getDuration(),
			target: this.isBeforeWaitingType() ? this.getTarget() : '',
		});
	}

	onCancelButtonClick(e): void
	{
		const callback = BX.prop.getFunction(this._settings, 'onCancel', null);
		if (callback)
		{
			callback(this);
		}
	}

	onTargetSelect(e, item): void
	{
		const fieldName = BX.prop.getString(item, 'value', '');
		if (fieldName !== '')
		{
			this._target = fieldName;
			this._targetDateNode.innerHTML = BX.util.htmlspecialchars(this.getTargetDateCaption(fieldName));
		}

		this.closeTargetMenu();
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	}

	static create(id: string, settings: Object): WaitConfigurationDialog
	{
		const self = new WaitConfigurationDialog();
		self.initialize(id, settings);

		return self;
	}

	static messages = {};
}
