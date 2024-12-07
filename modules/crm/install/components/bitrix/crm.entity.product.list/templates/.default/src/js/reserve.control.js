import {Tag, Text, Loc, Event, Cache, Runtime, Dom} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import { Row } from './product.list.row';
import { ModeList } from 'catalog.store-enable-wizard';

export default class ReserveControl
{
	static INPUT_NAME = 'INPUT_RESERVE_QUANTITY';
	static VIEW_NAME = 'VIEW_RESERVE_QUANTITY';
	static DATE_NAME = 'DATE_RESERVE_END';
	static QUANTITY_NAME = 'QUANTITY';
	static DEDUCTED_QUANTITY_NAME = 'DEDUCTED_QUANTITY';

	#row:Row = null;
	#cache = new Cache.MemoryCache();
	isReserveEqualProductQuantity = true;
	wrapper = null;
	measureName;

	constructor(options)
	{
		this.#row = options.row;
		this.inputFieldName = options.inputName || ReserveControl.INPUT_NAME;
		this.viewName = ReserveControl.VIEW_NAME;
		this.dateFieldName = options.dateFieldName || ReserveControl.DATE_NAME;
		this.quantityFieldName = options.quantityFieldName || ReserveControl.QUANTITY_NAME;
		this.deductedQuantityFieldName = options.deductedQuantityFieldName || ReserveControl.DEDUCTED_QUANTITY_NAME;
		this.defaultDateReservation = options.defaultDateReservation || null;
		this.isBlocked = options.isBlocked || false;
		this.isInventoryManagementToolEnabled = options.isInventoryManagementToolEnabled || false;
		this.inventoryManagementMode = options.inventoryManagementMode || '';
		this.measureName = options.measureName;

		this.isReserveEqualProductQuantity =
			options.isReserveEqualProductQuantity
			&& (
				this.getReservedQuantity() === this.getQuantity()
				|| this.#row.isNewRow()
			)
		;
	}

	renderTo(node: HTMLElement): void
	{
		this.wrapper = node;

		Dom.append(Tag.render`<div>${this.#getReserveInputNode()}</div>`, this.wrapper);
		Event.bind(this.#getReserveInputNode().querySelector('input'), 'input', Runtime.debounce(this.onReserveInputChange, 800, this));

		if (!this.#isInventoryManagementMode1C())
		{
			if (this.getReservedQuantity() > 0 || this.isReserveEqualProductQuantity)
			{
				this.#layoutDateReservation(this.getDateReservation());
			}

			Dom.append(this.#getDateNode(), this.wrapper);

			Event.bind(this.#getDateNode(), 'click', ReserveControl.#onDateInputClick.bind(this));
			Event.bind(this.#getDateNode().querySelector('input'), 'change', this.onDateChange.bind(this));
		}
	}

	setReservedQuantity(value: Number, isTriggerEvent: ?Boolean)
	{
		const input = this.#getReserveInputNode().querySelector('input');
		if (input)
		{
			input.value = value;

			if (isTriggerEvent)
			{
				input.dispatchEvent(new window.Event('input'));
			}
		}
	}

	getReservedQuantity()
	{
		return Text.toNumber(this.#row.getField(this.inputFieldName));
	}

	getDateReservation()
	{
		return this.#row.getField(this.dateFieldName) || '';
	}

	getQuantity()
	{
		return Text.toNumber(this.#row.getField(this.quantityFieldName));
	}

	getDeductedQuantity()
	{
		return Text.toNumber(this.#row.getField(this.deductedQuantityFieldName));
	}

	getAvailableQuantity()
	{
		return this.getQuantity() - this.getDeductedQuantity();
	}

	onReserveInputChange(event: BaseEvent)
	{
		const value = Text.toNumber(event.target.value);

		this.changeInputValue(value);
	}

	changeInputValue(value): void
	{
		if (value > this.getAvailableQuantity())
		{
			const errorNotifyId = 'reserveCountError';
			let notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
			if (!notify)
			{
				const notificationOptions = {
					id: errorNotifyId,
					closeButton: true,
					autoHideDelay: 3000,
					content: Tag.render`<div>${Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_WITH_DEDUCTED_THEN_RESERVED')}</div>`,
				};

				notify = BX.UI.Notification.Center.notify(notificationOptions);
			}

			notify.show();

			value = this.getAvailableQuantity();
			this.setReservedQuantity(value);
		}

		if (value > 0)
		{
			const dateReservation = this.getDateReservation();
			if (dateReservation === '')
			{
				this.changeDateReservation(this.defaultDateReservation);
			}
			else
			{
				this.#layoutDateReservation(dateReservation);
			}
		}
		else if (value <= 0)
		{
			this.changeDateReservation();
		}

		this.setReservedQuantity(value, false);
		this.#row.updateField(this.inputFieldName, value);
	}

	clearCache()
	{
		this.#cache.delete('dateInput');
		this.#cache.delete('reserveInput');
	}

	isInputDisabled(): boolean
	{
		if (
			this.isBlocked
			|| !this.isInventoryManagementToolEnabled
		)
		{
			return true;
		}

		const model = this.#row.getModel();
		if (model)
		{
			return model.isSimple() || model.isService();
		}

		return false;
	}

	static #onDateInputClick(event: BaseEvent)
	{
		BX.calendar({node: event.target, field: event.target.parentNode.querySelector('input'), bTime: false});
	}

	onDateChange(event: BaseEvent)
	{
		const value = event.target.value;
		const newDate = BX.parseDate(value);
		const current = new Date();
		current.setHours(0,0,0,0);
		if (newDate >= current)
		{
			this.changeDateReservation(value);
		}
		else
		{
			const errorNotifyId = 'reserveDateError';
			let notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
			if (!notify)
			{
				const notificationOptions = {
					id: errorNotifyId,
					closeButton: true,
					autoHideDelay: 3000,
					content: Tag.render`<div>${Loc.getMessage('CRM_ENTITY_PL_DATE_IN_PAST')}</div>`,
				};

				notify = BX.UI.Notification.Center.notify(notificationOptions);
			}

			notify.show();
			this.changeDateReservation(this.defaultDateReservation);
		}
	}

	#getDateNode(): HTMLElement
	{
		return this.#cache.remember('dateInput', () => {
			return Tag.render`
				<div>
					<a class="crm-entity-product-list-reserve-date"></a>
					<input
						data-name="${this.dateFieldName}"
						name="${this.dateFieldName}"
						type="hidden"
						value="${this.getDateReservation()}"
					>
				</div>
			`;
		});
	}

	#getReserveInputNode(): HTMLElement
	{
		return this.#cache.remember('reserveInput', () => {
			const viewReserveNode =
				this.#isInventoryManagementMode1C()
					? Tag.render`
						<span>
							<span data-name="${this.viewName}">
								${this.getReservedQuantity()}
							</span>
							&nbsp;
							${Text.encode(this.#row.getMeasureName())}
						</span>
					`
					: null
			;

			const tag = Tag.render`
				<div ${this.isInputDisabled() ? 'class="crm-entity-product-list-locked-field-wrapper"' : ''}>
					${viewReserveNode}
					<input type="${this.#isInventoryManagementMode1C() ? 'hidden' : 'text'}"
						data-name="${this.inputFieldName}"
						name="${this.inputFieldName}"
						class="ui-ctl-element ui-ctl-textbox ${this.isInputDisabled() ? 'crm-entity-product-list-locked-field' : ''}"
						autoComplete="off"
						value="${this.getReservedQuantity()}"
						placeholder="0"
						title="${this.getReservedQuantity()}"
						${this.isInputDisabled() ? 'disabled' : ''}
					/>
				</div>
			`;
			if (this.isBlocked || !this.isInventoryManagementToolEnabled)
			{
				tag.onclick = () => EventEmitter.emit(this, 'onNodeClick');
			}

			return tag;
		});
	}

	changeDateReservation(date: string = '')
	{
		if (date !== this.getDateReservation())
		{
			this.#row.updateField(this.dateFieldName, date);
		}

		this.#layoutDateReservation(date);
	}

	#layoutDateReservation(date: string = ''): void
	{
		const linkText =
			(date === '')
				? ''
				: Loc.getMessage(
					'CRM_ENTITY_PL_RESERVED_DATE',
					{
						'#FINAL_RESERVATION_DATE#': date
					}
				)
		;
		const link = this.#getDateNode().querySelector('a');
		if (link)
		{
			link.innerText = linkText;
		}

		const hiddenInput = this.#getDateNode().querySelector('input');
		if (hiddenInput)
		{
			hiddenInput.value = date;
		}
	}

	disable(wrapper: Element|null): void
	{
		const node = wrapper || this.wrapper;
		if (node)
		{
			node.innerHTML = this.getReservedQuantity() + ' ' + Text.encode(this.measureName);
		}
	}

	#isInventoryManagementMode1C(): boolean
	{
		return this.inventoryManagementMode === ModeList.MODE_1C;
	}
}
