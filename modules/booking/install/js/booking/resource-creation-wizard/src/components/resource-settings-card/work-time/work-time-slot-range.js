import { Dom } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { MenuManager, Menu, MenuItem } from 'main.popup';
import type { SlotRange } from 'booking.model.resources';
import { WorkTimeMixin } from './work-time-mixin';
import { WeekDaysPopup } from './week-days-popup';

import './work-time-slot-range.css';

export const WorkTimeSlotRange = {
	name: 'ResourceSettingsCardWorkTimeSlotRange',
	emits: ['add', 'update', 'remove'],
	props: {
		id: {
			type: Number,
			required: true,
		},
		isLastRange: {
			type: Boolean,
			required: true,
		},
		initialSlotRange: {
			type: Object,
			required: true,
		},
	},
	components: {
		WeekDaysPopup,
	},
	mixins: [WorkTimeMixin],
	data(): Object
	{
		return {
			fromId: 'from',
			toId: 'to',
			fromTs: 0,
			toTs: 0,
			duration: 0,
			isShownDays: false,
			arrow: {
				days: false,
				from: false,
				to: false,
			},
			removeSlot: !this.isLastRange,
			localSlotRange: { ...this.initialSlotRange },
			initialDays: [...this.initialSlotRange.weekDays],
		};
	},
	created(): void
	{
		this.fromTs = this.slotRange.from;
		this.toTs = this.slotRange.to;
		this.duration = this.toTs - this.fromTs;
	},
	watch: {
		'slotRange.from': function(value) {
			this.fromTs = value;

			this.toTs = this.fromTs + this.duration;

			this.update();
		},
		'slotRange.to': function(value) {
			this.toTs = value;

			if (this.toTs <= this.fromTs)
			{
				this.fromTs = this.toTs - this.duration;
			}

			this.duration = this.toTs - this.fromTs;

			this.update();
		},
	},
	methods: {
		showDays(): void
		{
			this.isShownDays = true;
			this.arrow.days = true;
		},
		hideDays(): void
		{
			this.isShownDays = false;
			this.arrow.days = false;
		},
		selectDays(weekDays: Array, daysLabel: string): void
		{
			this.slotRange.weekDays = weekDays;

			this.daysLabel = daysLabel;

			this.update();
		},
		show(bindElement: HTMLElement, id: string): void
		{
			if (this.arrow[id] === true)
			{
				return;
			}

			const menuId = `booking-work-time-popup-${this.id}-${id}`;
			MenuManager.destroy(menuId);

			const timeList: Menu = MenuManager.create({
				id: menuId,
				className: 'resource-creation-wizard__form-work-time-menu',
				bindElement,
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
				autoHide: true,
				closeByEsc: true,
				events: {
					onShow: () => {
						this.arrow[id] = true;

						const selectedItem: MenuItem = timeList.getMenuItems()
							.find((item: MenuItem) => Dom.hasClass(item.getLayout().item, '--selected'))
						;

						const itemHeight = 36;
						const offset = 2 * itemHeight;

						const scrollContainer = timeList.getPopupWindow().getContentContainer();
						const topPosition = Dom.getRelativePosition(selectedItem.getContainer(), scrollContainer).top;

						scrollContainer.scrollTo({
							top: scrollContainer.scrollTop + topPosition - offset,
							behavior: 'instant',
						});
					},
					onClose: () => {
						this.arrow[id] = false;
					},
				},
				maxHeight: 200,
				minWidth: bindElement.offsetWidth,
			});

			for (const [key, value] of Object.entries(this.generateTimeMap(id)))
			{
				const minutes = parseInt(key, 10);
				const defaultClass = 'menu-popup-item menu-popup-no-icon';

				timeList.addMenuItem({
					text: value,
					className: this.slotRange[id] === minutes ? `${defaultClass} --selected` : defaultClass,
					dataset: {
						id,
						minutes,
					},
					onclick: () => {
						this.slotRange[id] = parseInt(minutes, 10);
						timeList.close();
					},
				});
			}

			timeList.show();
		},
		generateTimeMap(id: string): Object
		{
			const timeMap = {};

			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			const interval = 30;
			const from = (id === this.toId ? this.fromTs + interval : 0);
			const to = (id === this.fromId ? this.toTs : 1440);
			for (let minutes = from; minutes < to; minutes += interval)
			{
				const timestamp = new Date().setHours(0, minutes, 0, 0) / 1000;
				timeMap[minutes] = DateTimeFormat.format(timeFormat, timestamp);
			}

			if (id === this.toId)
			{
				const midnightTimestamp = new Date().setHours(0, 0, 0, 0) / 1000;
				timeMap[to] = DateTimeFormat.format(timeFormat, midnightTimestamp);
			}

			return timeMap;
		},
		clickBtn(): void
		{
			if (this.removeSlot === false)
			{
				this.removeSlot = true;

				this.$emit('add');
			}
			else
			{
				this.removeSlot = false;

				this.$emit('remove', this.id);
			}
		},
		update(): void
		{
			this.$emit('update', this.id, this.slotRange);
		},
	},
	computed: {
		daysId(): string
		{
			return `days-${this.id}`;
		},
		days(): HTMLElement
		{
			return this.$refs.days;
		},
		slotRange(): SlotRange
		{
			return this.localSlotRange;
		},
		fromLabel(): string
		{
			const timeMap = this.generateTimeMap(this.fromId);

			return timeMap[this.slotRange.from];
		},
		toLabel(): string
		{
			const timeMap = this.generateTimeMap(this.toId);

			return timeMap[this.slotRange.to];
		},
	},
	template: `
		<div class="ui-form-content resource-creation-wizard__form-work-time-selector-widget">
			<div class="ui-form-row resource-creation-wizard__form-work-time-selector-widget-days">
				<div
					ref="days"
					:data-id="'brcw-resource-work-time-slot-range-days-' + id"
					class="rcw-ui-ctl ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-sm ui-ctl-round"
					@click="showDays"
				>
					<div
						class="ui-ctl-after ui-ctl-icon-angle"
						:class="{ '--active': arrow.days }"
					></div>
					<div class="ui-ctl-element">
						{{ daysLabel }}
					</div>
					<WeekDaysPopup
						v-if="isShownDays"
						:id="daysId"
						:bindElement="days"
						:initialDays="slotRange.weekDays"
						@select="selectDays"
						@close="hideDays"
					/>
				</div>
			</div>
			<div class="ui-form-row resource-creation-wizard__form-work-time-selector-widget-time">
				<div class="ui-form-content">
					<div class="ui-form-row">
						<div
							ref="from"
							:data-id="'brcw-resource-work-time-slot-range-from-' + id"
							class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-sm ui-ctl-round"
							@click="() => show($refs.from, fromId)"
						>
							<div
								class="ui-ctl-after ui-ctl-icon-angle"
								:class="{ '--active': arrow.from }"
							></div>
							<div class="ui-ctl-element">{{ fromLabel }}</div>
						</div>
					</div>
					<div class="ui-form-row">
						<div class="resource-creation-wizard__form-work-time-selector-dash"></div>
					</div>
					<div class="ui-form-row">
						<div
							ref="to"
							:data-id="'brcw-resource-work-time-slot-range-to-' + id"
							class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-sm ui-ctl-round"
							@click="() => show($refs.to, toId)"
						>
							<div
								class="ui-ctl-after ui-ctl-icon-angle"
								:class="{ '--active': arrow.to }"
							></div>
							<div class="ui-ctl-element">{{ toLabel }}</div>
						</div>
					</div>
				</div>
			</div>
			<div class="ui-form-row resource-creation-wizard__form-work-time-selector-btn-row">
				<div
					:data-id="'brcw-resource-work-time-slot-range-add-' + id"
					class="resource-creation-wizard__form-work-time-selector-btn"
					:class="{ '--remove': removeSlot }"
					@click="clickBtn"
				></div>
			</div>
		</div>
	`,
};
