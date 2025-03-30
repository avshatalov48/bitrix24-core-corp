import { DatetimeConverter } from 'crm.timeline.tools';
import { Dom, Event, Loc, Runtime, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { MenuManager } from 'main.popup';
import { UI } from 'ui.notification';
import type { PingSelectorOptions } from './ping-selector-options';

import 'ui.design-tokens';
import './ping-selector.css';

const MENU_ITEM_CLASS_ACTIVE = 'menu-popup-item-accept';
const MENU_ITEM_CLASS_INACTIVE = 'menu-popup-item-none';
const MENU_ITEM_CLASS_ARROW = 'crm-field-ping-selector-arrow';

const MENU_ITEM_SHOW_CALENDAR_ID = 'item-selector-menu-id-custom-calendar';

type Item = {
	id: string | number;
	title: string;
}

export type { PingSelectorOptions };

export type MenuItem = {
	id: string;
	className: string;
	html: string;
	onclick: Function;
}

export const CompactIcons = {
	NONE: null,
	BELL: 'bell',
};

export const PingSelectorEvents = {
	EVENT_PINGSELECTOR_OPEN: 'crm.field.pingselector:open',
	EVENT_PINGSELECTOR_VALUE_CHANGE: 'crm.field.pingselector:change',
};

export class PingSelector
{
	#id: ?string;
	#target: ?HTMLElement = null;
	#valuesList: Item[] = [];
	#selectedValues: Set = new Set();
	#readonlyMode: boolean = false;
	#icon: ?string = null;
	#deadline: ?Date = null;

	#selectedValueWrapperEl: ?HTMLElement = null;
	#valuesMenuPopup: ?Menu = null;
	#addButtonCompact: ?HTMLElement = null;

	constructor(params: PingSelectorOptions)
	{
		this.#assertValidParams(params);

		this.#id = params.id || `ping-selector-${Text.getRandom()}`;
		this.#target = Type.isDomNode(params.target) ? params.target : null;
		this.#valuesList = Type.isArrayFilled(params.valuesList) ? params.valuesList.map((item) => {
			return {
				...item,
				id: item.id.toString(),
			};
		}) : [];

		if (Type.isArrayFilled(params.selectedValues))
		{
			params.selectedValues.forEach((selectedValue) => this.#selectedValues.add(selectedValue.toString()));
		}

		this.#readonlyMode = params.readonlyMode === true;
		this.#deadline = (Type.isDate(params?.deadline) ? params.deadline : new Date());
		this.#deadline.setSeconds(0);

		if (Type.isStringFilled(params.icon) && Object.values(CompactIcons).includes(params.icon))
		{
			this.#icon = params.icon;
		}

		this.#create();
		this.#bindEvents();
		this.#applyCurrentValue(100);
	}

	setDeadline(deadline: Date): void
	{
		this.#deadline = deadline;
	}

	getValue(): Array
	{
		return [...this.#selectedValues.values()];
	}

	setValue(values: Array, isEmitEvent: boolean = false): void
	{
		this.clearAll();

		values.forEach((value: string) => {
			this.#addValue(value, isEmitEvent);
		});
	}

	#addValue(value: mixed, isEmitEvent: boolean = false): void
	{
		const rawValue = this.#valuesList.find((element: Item) => {
			return element?.id?.toString() === value?.toString();
		});

		if (!rawValue)
		{
			return;
		}

		this.#selectedValues.add(rawValue.id);

		this.#adjustAddButtonCompact();

		if (isEmitEvent)
		{
			this.#emitEvent();
		}
	}

	#removeValue(value: string | number, isEmitEvent: boolean = false): void
	{
		this.#selectedValues.delete(value);

		this.#adjustAddButtonCompact();

		if (isEmitEvent)
		{
			this.#emitEvent();
		}
	}

	clearAll(): void
	{
		if (this.#selectedValues.size === 0)
		{
			return;
		}

		this.#selectedValues.forEach((value) => this.#removeValue(value));

		this.#selectedValues = new Set();

		this.#adjustAddButtonCompact();
	}

	#create(): void
	{
		if (!this.#target)
		{
			return;
		}

		if (!this.#readonlyMode)
		{
			this.#addButtonCompact = Tag.render`
				<span 
					class="crm-field-ping-selector-compact-icon ${Type.isStringFilled(this.#icon) ? `--${this.#icon}` : ''}"
				></span>
			`;

			this.#adjustAddButtonCompact();
		}

		Dom.append(this.#getAddButtonEl(), this.#target);
	}

	#adjustAddButtonCompact(): void
	{
		if (this.#selectedValues.size > 0)
		{
			Dom.removeClass(this.#addButtonCompact, '--empty');
		}
		else
		{
			Dom.addClass(this.#addButtonCompact, '--empty');
		}
	}

	#getAddButtonEl(): ?HTMLElement
	{
		return this.#addButtonCompact;
	}

	#bindEvents(): void
	{
		if (Type.isDomNode(this.#getAddButtonEl()))
		{
			Event.bind(this.#getAddButtonEl(), 'click', this.#onShowPopup.bind(this));
		}

		if (Type.isDomNode(this.#addButtonCompact))
		{
			Event.bind(this.#addButtonCompact, 'click', this.#onShowPopup.bind(this));
		}

		if (Type.isDomNode(this.#selectedValueWrapperEl))
		{
			Event.bind(this.#selectedValueWrapperEl, 'click', this.#onRemoveValue.bind(this));
		}

		Event.unbind(window, 'resize', this.#onWindowResize);
		Event.bind(window, 'resize', this.#onWindowResize.bind(this));
	}

	#onShowPopup(): void
	{
		const menuItems = this.#getPreparedMenuItems();

		// @todo temporary, need other fix
		const angle = { offset: 29, position: 'top' };

		const menuParams = {
			closeByEsc: true,
			autoHide: true,
			offsetLeft: this.#getAddButtonEl().offsetWidth - 16,
			angle,
			cacheable: false,
		};

		this.#valuesMenuPopup = MenuManager.create(this.#id, this.#getAddButtonEl(), menuItems, menuParams);
		this.#valuesMenuPopup.show();

		EventEmitter.emit(this, PingSelectorEvents.EVENT_PINGSELECTOR_OPEN);
	}

	#getPreparedMenuItems(): MenuItem[]
	{
		const items = this.#valuesList.map((item: Item) => this.#getPreparedMenuItem(item));
		items.push(this.#getCalendarMenuItem());

		return items;
	}

	#getPreparedMenuItem(item: Item): MenuItem
	{
		return {
			id: `ping-selector-menu-id-${item.id}`,
			className: this.#isValueSelected(item.id) ? MENU_ITEM_CLASS_ACTIVE : MENU_ITEM_CLASS_INACTIVE,
			onclick: this.#onMenuItemClick.bind(this, item.id),
			html: Text.encode(item.title),
		};
	}

	#getCalendarMenuItem(): MenuItem
	{
		return {
			id: MENU_ITEM_SHOW_CALENDAR_ID,
			className: MENU_ITEM_CLASS_ARROW,
			onclick: (event) => {
				this.#showCalendar(event.target);
			},
			html: Loc.getMessage('CRM_FIELD_PING_SELECTOR_OTHER_TIME'),
		};
	}

	#showCalendar(target: HTMLElement): void
	{
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		BX.calendar({
			node: target,
			bTime: true,
			bHideTime: false,
			bSetFocus: false,
			value: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), this.#deadline),
			callback: this.#addCustomValue.bind(this),
		});
	}

	#addCustomValue(date: Date): void
	{
		if (date.getTime() > this.#deadline.getTime())
		{
			this.#close();

			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_FIELD_PING_SELECTOR_WRONG_TIME'),
				autoHideDelay: 3000,
			});

			return;
		}

		const offset = Math.floor((this.#deadline.getTime() - date.getTime()) / 1000 / 60);
		this.#selectedValues.add(offset.toString());

		const customValue = {
			id: offset.toString(),
			title: this.#getOffsetTitle(offset),
		};

		this.#valuesList.push(customValue);

		this.#valuesList = this.#valuesList.sort((a, b) => {
			const offset1 = Number(a.id);
			const offset2 = Number(b.id);

			return ((offset1 < offset2) ? -1 : ((offset1 > offset2) ? 1 : 0));
		});

		this.#close();

		this.#adjustAddButtonCompact();

		this.#emitEvent();
	}

	#getOffsetTitle(offset: Number): String
	{
		const minutesInHour = 60;

		const days = Math.floor(offset / (minutesInHour * 24));
		let daysString = null;
		if (days > 0)
		{
			daysString = Loc.getMessagePlural(
				'CRM_FIELD_PING_SELECTOR_DAY',
				days,
				{
					'#COUNT#': days,
				},
			);
		}

		const hours = Math.floor((offset % (minutesInHour * 24)) / (minutesInHour));
		let hoursString = null;
		if (hours > 0)
		{
			hoursString = Loc.getMessagePlural(
				'CRM_FIELD_PING_SELECTOR_HOUR',
				hours,
				{
					'#COUNT#': hours,
				},
			);
		}

		const minutes = Math.floor(offset % (minutesInHour));
		let minutesString = null;
		if (minutes > 0)
		{
			minutesString = Loc.getMessagePlural(
				'CRM_FIELD_PING_SELECTOR_MINUTE',
				minutes,
				{
					'#COUNT#': minutes,
				},
			);
		}

		if (days > 0 && hours > 0 && minutes > 0)
		{
			return Loc.getMessage(
				'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_HOUR_MINUTE_TITLE',
				{
					'#DAYS#': daysString,
					'#HOURS#': hoursString,
					'#MINUTES#': minutesString,
				},
			);
		}

		if (days > 0 && hours > 0)
		{
			return Loc.getMessage(
				'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_HOUR_TITLE',
				{
					'#DAYS#': daysString,
					'#HOURS#': hoursString,
				},
			);
		}

		if (days > 0 && minutes > 0)
		{
			return Loc.getMessage(
				'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_MINUTE_TITLE',
				{
					'#DAYS#': daysString,
					'#MINUTES#': minutesString,
				},
			);
		}

		if (days > 0)
		{
			return Loc.getMessage(
				'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_DAY_TITLE',
				{
					'#DAYS#': daysString,
				},
			);
		}

		if (hours > 0 && minutes > 0)
		{
			return Loc.getMessage(
				'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_HOUR_MINUTE_TITLE',
				{
					'#HOURS#': hoursString,
					'#MINUTES#': minutesString,
				},
			);
		}

		if (hours > 0)
		{
			return Loc.getMessage(
				'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_HOUR_TITLE',
				{
					'#HOURS#': hoursString,
				},
			);
		}

		return Loc.getMessage(
			'CRM_FIELD_PING_SELECTOR_CUSTOM_OFFSET_MINUTE_TITLE',
			{
				'#MINUTES#': minutesString,
			},
		);
	}

	#close(): void
	{
		this.#valuesMenuPopup.close();
		MenuManager.destroy(this.#id);
	}

	#onRemoveValue(event: BaseEvent): void
	{
		const target = event.target || event.srcElement;
		const itemIdToRemove = target.getAttribute('data-ping-selector-id');
		if (Type.isNull(itemIdToRemove))
		{
			return; // nothing to do
		}

		if (this.#isValueSelected(itemIdToRemove))
		{
			this.#removeValue(itemIdToRemove, true);
		}
	}

	#onMenuItemClick(value: mixed, event: PointerEvent, item: MenuItem): void
	{
		if (this.#isValueSelected(value))
		{
			this.#removeValue(value, true);

			Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
			Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
		}
		else
		{
			this.#addValue(value, true);

			Dom.removeClass(item.getContainer(), MENU_ITEM_CLASS_INACTIVE);
			Dom.addClass(item.getContainer(), MENU_ITEM_CLASS_ACTIVE);
		}
	}

	#onWindowResize(): void
	{
		this.#applyCurrentValue(750);
	}

	#emitEvent(): void
	{
		EventEmitter.emit(this, PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE, {
			value: this.getValue(),
		});
	}

	#assertValidParams(params: PingSelectorOptions): void
	{
		if (!Type.isPlainObject(params))
		{
			throw new TypeError('BX.Crm.Field.PingSelector: The "params" argument must be object');
		}

		if (!Type.isDomNode(params.target))
		{
			throw new Error('BX.Crm.Field.PingSelector: The "target" argument must be DOM node');
		}

		if (!Type.isArrayFilled(params.valuesList))
		{
			throw new Error('BX.Crm.Field.PingSelector: The "valuesList" argument must be filled');
		}
	}

	#applyCurrentValue(delay: number): void
	{
		Runtime.debounce(
			() => {
				this.setValue([...this.#selectedValues] || []);
			},
			delay,
			this,
		)();
	}

	#isValueSelected(value: string | number): boolean
	{
		return this.#selectedValues.has(value);
	}
}
