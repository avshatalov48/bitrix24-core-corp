import { Dom, Loc, Runtime, Type } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { DateTimeFormat } from 'main.date';
import { Draggable, DragMoveEvent, DragStartEvent, DragEndEvent } from 'ui.draganddrop.draggable';

import { Model, DraggedElementKind } from 'booking.const';
import { Core } from 'booking.core';
import { busySlots } from 'booking.lib.busy-slots';
import { BookingAnalytics } from 'booking.lib.analytics';
import { bookingService } from 'booking.provider.service.booking-service';
import { NonDraggableBookingPopup } from 'booking.component.non-draggable-booking-popup';
import type { BookingModel } from 'booking.model.bookings';
import type { DraggedDataTransfer } from 'booking.model.interface';
import type { ResourceModel } from 'booking.model.resources';

type Params = {
	draggable: string,
	container: HTMLElement,
	element: 'booking-booking' | 'booking-waitlist-item',
};

export class Drag
{
	#params: Params;
	#dragManager: Draggable;
	#draggedId: ?number;
	#draggedKind: ?$Values<typeof DraggedElementKind>;

	constructor(params: Params)
	{
		this.#params = {
			element: 'booking-booking',
			...params,
		};

		this.#dragManager = new Draggable({
			container: this.#params.container,
			draggable: this.#params.draggable,
			elementsPreventingDrag: ['.booking-booking-resize'],
			delay: 200,
		});

		this.#dragManager.subscribe('start', this.#onDragStart.bind(this));
		this.#dragManager.subscribe('move', this.#onDragMove.bind(this));
		this.#dragManager.subscribe('end', this.#onDragEnd.bind(this));
	}

	destroy(): void
	{
		this.#dragManager.destroy();
	}

	async #onDragStart(event: DragStartEvent): Promise<void>
	{
		const { draggable, source: { dataset }, clientX, clientY } = event.getData();

		this.#params.element = `booking-${dataset.kind}`;
		this.#draggedKind = dataset.kind;
		this.#draggedId = parseInt(dataset.id, 10);

		await Core.getStore().dispatch(`${Model.Interface}/setDraggedDataTransfer`, {
			kind: this.#draggedKind,
			id: this.#draggedId,
			resourceId: parseInt(dataset.resourceId, 10) ?? 0,
		});

		Dom.style(draggable, 'pointer-events', 'none');
		this.#getAdditionalBookingElements(this.#params.container).forEach((element) => {
			const clone = Runtime.clone(element);
			draggable.append(clone);

			const translateX = element.getBoundingClientRect().left - draggable.getBoundingClientRect().left;
			const translateY = element.getBoundingClientRect().top - draggable.getBoundingClientRect().top;
			Dom.style(clone, 'transition', 'none');
			Dom.style(clone, 'transform', `translate(${translateX}px, ${translateY}px)`);
			Dom.style(clone, 'animation', 'none');
		});

		this.#getDraggedElements(this.#params.container).forEach((element) => {
			if (draggable.contains(element))
			{
				return;
			}

			Dom.addClass(element, '--drag-source');
			Dom.style(element, 'visibility', 'visible');
		});

		const transformOriginX = clientX - draggable.getBoundingClientRect().left;
		const transformOriginY = clientY - draggable.getBoundingClientRect().top;
		this.#getDraggedElements(draggable).forEach((clone) => {
			Dom.style(clone, 'transform-origin', `${transformOriginX}px ${transformOriginY}px`);
		});

		PopupManager.getPopups().forEach((popup: Popup) => popup.close());

		void busySlots.loadBusySlots();
	}

	#onDragMove(event: DragMoveEvent): void
	{
		if (Type.isNull(this.#draggedKind))
		{
			return;
		}

		const { draggable, clientX, clientY } = event.getData();

		this.#getAdditionalBookingElements(draggable).forEach((clone, index) => {
			Dom.style(clone, 'transition', '');
			Dom.style(clone, 'transform', `rotate(${index === 1 ? 4 : 0}deg)`);
			Dom.style(clone, 'zIndex', `-${index + 1}`);
		});

		if (this.#isDragDeleteHovered(clientX, clientY))
		{
			Dom.addClass(draggable, '--deleting');
		}
		else
		{
			Dom.removeClass(draggable, '--deleting');
		}

		if (this.#draggedKind === DraggedElementKind.Booking)
		{
			draggable.querySelectorAll('[data-element="booking-booking-time"]').forEach((time) => {
				time.innerText = this.#timeFormatted;
			});
		}

		this.#updateScroll(draggable, clientX, clientY);
	}

	async #onDragEnd(event: DragEndEvent): Promise<void>
	{
		clearInterval(this.scrollTimeout);

		this.#getDraggedElements(this.#params.container).forEach((element) => {
			Dom.removeClass(element, '--drag-source');
			Dom.style(element, 'visibility', '');
		});

		if (this.#hoveredCell && !this.#getResourceById(this.#hoveredCell.resourceId)?.isDeleted)
		{
			if (this.#draggedKind === DraggedElementKind.Booking)
			{
				void this.#moveBooking({
					booking: this.#draggedBooking,
					resourceId: this.#draggedBookingResourceId,
					cell: this.#hoveredCell,
				});
			}
			else if (this.#draggedKind === DraggedElementKind.WaitListItem)
			{
				void this.#moveWaitListItem({
					cell: this.#hoveredCell,
				});
			}
		}

		if (this.#getResourceById(this.#draggedBookingResourceId)?.isDeleted)
		{
			const bookingId = this.#draggedBooking.id;

			if (Core.getStore().getters[`${Model.Interface}/deletingBookings`][bookingId])
			{
				return;
			}

			this.#showPopupOnNonDraggableBookingFromDeletedResource(event.data.source, bookingId);
		}

		this.#draggedKind = null;
		this.#draggedId = null;
		await Core.getStore().dispatch(`${Model.Interface}/clearDraggedDataTransfer`);

		void busySlots.loadBusySlots();
	}

	#getDraggedElements(container: HTMLElement): HTMLElement[]
	{
		const id = this.#draggedDataTransfer.id || this.#draggedId;
		const items = [
			...container
				.querySelectorAll(`[data-element="${this.#params.element}"][data-id="${id}"]`),
		];

		if (!id && items.length === 0)
		{
			return this.#tryGetLostDraggedElements(container);
		}

		return items;
	}

	#tryGetLostDraggedElements(container): HTMLElement[]
	{
		return [...container.querySelectorAll(`[data-element="${this.#params.element}"].--drag-source`)];
	}

	#getAdditionalBookingElements(container: HTMLElement): HTMLElement[]
	{
		const id = this.#draggedBookingId;
		const resourceId = this.#draggedBookingResourceId;

		return [
			...container
				.querySelectorAll(`[data-element="${this.#params.element}"][data-id="${id}"]:not([data-resource-id="${resourceId}"])`),
		];
	}

	#updateScroll(draggable: HTMLElement, x: number, y: number): void
	{
		clearTimeout(this.scrollTimeout);
		if (this.#isDragDeleteHovered(x, y))
		{
			return;
		}

		const gridRect = this.#gridWrap.getBoundingClientRect();
		const draggableRect = draggable.getBoundingClientRect();

		this.scrollTimeout = setTimeout(() => this.#updateScroll(draggable), 16);

		if (draggableRect.left < gridRect.left)
		{
			this.#gridColumns.scrollLeft -= this.#getSpeed(draggableRect.left, gridRect.left);
		}
		else if (draggableRect.right > gridRect.right)
		{
			this.#gridColumns.scrollLeft += this.#getSpeed(draggableRect.right, gridRect.right);
		}
		else if (draggableRect.top < gridRect.top)
		{
			this.#gridWrap.scrollTop -= this.#getSpeed(draggableRect.top, gridRect.top);
		}
		else if (draggableRect.bottom > gridRect.bottom)
		{
			this.#gridWrap.scrollTop += 2 * this.#getSpeed(draggableRect.bottom, gridRect.bottom);
		}
		else
		{
			clearTimeout(this.scrollTimeout);
		}
	}

	#getSpeed(a: number, b: number): number
	{
		return (Math.floor(Math.sqrt(Math.abs(a - b))) + 1) / 2;
	}

	#isDragDeleteHovered(x: number, y: number): boolean
	{
		if (!x || !y)
		{
			return false;
		}

		return document.elementFromPoint(x, y)?.closest('[data-element="booking-drag-delete"]');
	}

	async #moveBooking({ booking, resourceId, cell }: {
		booking: BookingModel,
		resourceId: number,
		cell: CellDto
	}): void
	{
		if (cell.fromTs === booking.dateFromTs && cell.toTs === booking.dateToTs && cell.resourceId === resourceId)
		{
			return;
		}

		const resourceIds = booking.resourcesIds.includes(cell.resourceId)
			? booking.resourcesIds
			: [
				cell.resourceId,
				...booking.resourcesIds.filter((id: number) => id !== resourceId),
			]
		;

		await bookingService.update({
			id: booking.id,
			dateFromTs: cell.fromTs,
			dateToTs: cell.toTs,
			resourcesIds: [...new Set(resourceIds)],
			timezoneFrom: booking.timezoneFrom,
			timezoneTo: booking.timezoneTo,
		});
	}

	async #moveWaitListItem({ cell }: { cell: CellDto }): void
	{
		const $store = Core.getStore();
		const waitListItemId = this.#draggedDataTransfer.id;
		const waitListItem = $store.getters[`${Model.WaitList}/getById`](waitListItemId);
		const resourceId = cell.resourceId;
		const resource = $store.getters[`${Model.Resources}/getById`](resourceId);
		const timezone = resource?.slotRanges?.[0]?.timezone;
		const clients = [...waitListItem.clients];
		const intersections = $store.getters[`${Model.Interface}/intersections`];

		if ($store.getters[`${Model.Interface}/editingWaitListItemId`] === waitListItemId)
		{
			await this.#setEditingBookingId(waitListItemId);
		}

		const result = await bookingService.createFromWaitListItem(waitListItemId, {
			id: `wl${waitListItemId}`,
			clients,
			primaryClient: clients.length > 0 ? clients[0] : undefined,
			externalData: [...waitListItem.externalData],
			name: waitListItem.name,
			note: waitListItem.note,
			resourcesIds: [
				...new Set([
					cell.resourceId,
					...(intersections[0] ?? []),
					...(intersections[cell.resourceId] ?? []),
				]),
			],
			dateFromTs: cell.fromTs,
			dateToTs: cell.toTs,
			timezoneFrom: timezone,
			timezoneTo: timezone,
		});

		if (result.success && result.booking)
		{
			BookingAnalytics.sendAddBooking({ isOverbooking: false });

			if ($store.getters[`${Model.Interface}/editingBookingId`] === waitListItemId)
			{
				await this.#setEditingBookingId(result.booking.id);
			}
		}
	}

	async #setEditingBookingId(id: number | string): Promise<void>
	{
		const $store = Core.getStore();

		await Promise.all([
			$store.dispatch(`${Model.Interface}/setEditingBookingId`, id),
			$store.dispatch(`${Model.Interface}/setEditingWaitListItemId`, null),
		]);
	}

	get #timeFormatted(): string
	{
		const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
		const from = this.#hoveredCell?.fromTs ?? this.#draggedBooking.dateFromTs;
		const to = this.#hoveredCell?.toTs ?? this.#draggedBooking.dateToTs;

		return Loc.getMessage('BOOKING_BOOKING_TIME_RANGE', {
			'#FROM#': DateTimeFormat.format(timeFormat, (from + this.#offset) / 1000),
			'#TO#': DateTimeFormat.format(timeFormat, (to + this.#offset) / 1000),
		});
	}

	#showPopupOnNonDraggableBookingFromDeletedResource(bookingEl: HTMLElement, bookingId: number): void
	{
		const popupId = `booking-non-draggable-booking-${bookingId}`;
		const popup = new NonDraggableBookingPopup({
			id: popupId,
			bindElement: bookingEl,
		});
		popup.show();

		setTimeout(() => {
			popup.destroy(popupId);
		}, 5000);
	}

	#getResourceById(resourceId: number): ResourceModel
	{
		return Core.getStore().getters[`${Model.Resources}/getById`](resourceId);
	}

	get #draggedBooking(): BookingModel | null
	{
		return Core.getStore().getters[`${Model.Bookings}/getById`](this.#draggedBookingId) ?? null;
	}

	get #draggedDataTransfer(): DraggedDataTransfer
	{
		return Core.getStore().getters[`${Model.Interface}/draggedDataTransfer`];
	}

	get #draggedBookingId(): number
	{
		return Core.getStore().getters[`${Model.Interface}/draggedBookingId`];
	}

	get #draggedBookingResourceId(): number
	{
		return Core.getStore().getters[`${Model.Interface}/draggedBookingResourceId`];
	}

	get #hoveredCell(): CellDto
	{
		return Core.getStore().getters[`${Model.Interface}/hoveredCell`];
	}

	get #offset(): number
	{
		return Core.getStore().getters[`${Model.Interface}/offset`];
	}

	get #gridWrap(): HTMLElement
	{
		return BX('booking-booking-grid-wrap');
	}

	get #gridColumns(): HTMLElement
	{
		return BX('booking-booking-grid-columns');
	}
}
