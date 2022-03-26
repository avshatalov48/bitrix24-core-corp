import {Tag, Text, Loc, Event, Cache, Runtime} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';

export default class ReserveControl
{
	static INPUT_NAME = 'RESERVE_QUANTITY';
	static DATE_NAME = 'DATE_RESERVE_END';
	static QUANTITY_NAME = 'QUANTITY';

	#model = null;
	#cache = new Cache.MemoryCache();

	constructor(options)
	{
		this.#model = options.model;
		this.inputFieldName = options.inputName || ReserveControl.INPUT_NAME;
		this.dateFieldName = options.dateFieldName || ReserveControl.DATE_NAME;
		this.quantityFieldName = options.quantityFieldName || ReserveControl.QUANTITY_NAME;
		this.defaultDateReservation = options.defaultDateReservation || null;
		this.isInputDisabled = options.isInputDisabled || false;
	}

	renderTo(node: HTMLElement): void
	{
		node.appendChild(
			Tag.render`<div>${this.#getReserveInputNode()}</div>`
		);
		Event.bind(this.#getReserveInputNode().querySelector('input'), 'input', Runtime.debounce(this.onReserveInputChange, 800, this));

		if (this.getReservedQuantity() > 0)
		{
			this.#layoutDateReservation(this.getDateReservation());
		}

		node.appendChild(
			Tag.render`${this.#getDateNode()}`
		);

		Event.bind(this.#getDateNode(), 'click', this.#onDateInputClick.bind(this));
		Event.bind(this.#getDateNode().querySelector('input'), 'change', this.onDateChange.bind(this));
	}

	getReservedQuantity()
	{
		return Text.toNumber(this.#model.getField(this.inputFieldName));
	}

	getDateReservation()
	{
		return this.#model.getField(this.dateFieldName) || null;
	}

	getQuantity()
	{
		return Text.toNumber(this.#model.getField(this.quantityFieldName));
	}

	onReserveInputChange(event: BaseEvent)
	{
		let value = Text.toNumber(event.target.value);

		this.changeInputValue(value);
	}

	changeInputValue(value): void
	{
		if (value > this.getQuantity())
		{
			const errorNotifyId = 'reserveCountError';
			let notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
			if (!notify)
			{
				const notificationOptions = {
					id: errorNotifyId,
					closeButton: true,
					autoHideDelay: 3000,
					content: Tag.render`<div>${Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_THEN_RESERVED')}</div>`,
				};

				notify = BX.UI.Notification.Center.notify(notificationOptions);
			}

			notify.show();

			const input = this.#getReserveInputNode().querySelector('input');
			value = this.getQuantity();
			input.value = value;
		}

		if (value > 0)
		{
			if (this.getDateReservation() === null)
			{
				this.changeDateReservation(this.defaultDateReservation);
			}
			else
			{
				this.#layoutDateReservation(this.#model.getField(this.dateFieldName));
			}
		}
		else if (value <= 0)
		{
			this.changeDateReservation();
		}

		this.#model.setField(this.inputFieldName, value);

		EventEmitter.emit(this, 'onChange', {
			NAME: this.inputFieldName,
			VALUE: value,
		})
	}

	#onDateInputClick(event: BaseEvent)
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
			let tag = Tag.render`
				<div>
					<input type="text" 
						data-name="${this.inputFieldName}"
						name="${this.inputFieldName}"
						class="ui-ctl-element ui-ctl-textbox ${this.isInputDisabled ? "crm-entity-product-list-locked-field" : ""}" 
						autoComplete="off" 
						value="${this.getReservedQuantity()}"
						placeholder="0" 
						title="${this.getReservedQuantity()}"
						${this.isInputDisabled ? "disabled" : ""}
					/>
				</div>
			`;
			if (this.isInputDisabled)
			{
				tag.onclick = () => top.BX.UI.InfoHelper.show('limit_store_crm_integration');
			}
			return tag;
		});
	}

	changeDateReservation(date: ?string = null)
	{
		EventEmitter.emit(this, 'onChange', {
			NAME: this.dateFieldName,
			VALUE: date,
		})

		this.#model.setField(this.dateFieldName, date);

		this.#layoutDateReservation(date);
	}

	#layoutDateReservation(date: ?string = null): void
	{
		const linkText =
			(date === null)
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
}