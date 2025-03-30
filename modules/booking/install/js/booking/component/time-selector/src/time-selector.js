import { Event } from 'main.core';
import { Menu, MenuManager, MenuItem, type MenuItemOptions } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { Duration } from 'booking.lib.duration';
import { Model } from 'booking.const';
import { timeFormatter } from './lib/time-formatter';
import './time-selector.css';

const hour = Duration.getUnitDurations().H;
const halfHour = hour / 2;

type TimeSelectorData = {
	isMenuShow: boolean,
	focusByMouseDown: boolean,
	menuShownOnFocus: boolean,
};

export const TimeSelector = {
	emits: ['update:modelValue', 'freeze', 'unfreeze', 'enterSave'],
	props: {
		modelValue: {
			type: Number,
			required: true,
		},
		minTs: {
			type: Number,
			default: 0,
		},
		hasError: {
			type: Boolean,
			default: false,
		},
	},
	data(): TimeSelectorData
	{
		return {
			isMenuShown: false,
			focusByMouseDown: false,
			menuShownOnFocus: false,
		};
	},
	mounted(): void
	{
		this.$refs.input.value = this.formatTime(this.timestamp);
	},
	computed: {
		...mapGetters({
			offset: `${Model.Interface}/offset`,
		}),
		id(): string
		{
			return 'booking-time-selector-menu';
		},
		timestamp(): number
		{
			return this.modelValue;
		},
	},
	methods: {
		onFocus(): void
		{
			if (this.isMenuShown)
			{
				return;
			}

			setTimeout(() => {
				if (!this.isMenuShown)
				{
					this.menuShownOnFocus = true;
					this.showMenu();

					if (!this.focusByMouseDown)
					{
						this.onAfterMenuShown();
					}
				}
			}, 200);
		},
		onMouseDown(): void
		{
			if (!this.isMenuShown)
			{
				this.focusByMouseDown = true;

				Event.bind(window, 'dragend', this.onAfterMenuShown);
				Event.bind(window, 'click', this.onAfterMenuShown);
			}
		},
		onClick(): void
		{
			if (this.isMenuShown)
			{
				if (!this.menuShownOnFocus)
				{
					this.hideMenu();
				}
			}
			else
			{
				this.showMenu();
			}
		},
		onAfterMenuShown(): void
		{
			setTimeout(() => {
				this.menuShownOnFocus = false;
				this.focusByMouseDown = false;
				Event.unbind(window, 'dragend', this.onAfterMenuShown);
				Event.unbind(window, 'click', this.onAfterMenuShown);
			}, 0);
		},
		onKeyDown(event: KeyboardEvent): void
		{
			if (event.key === 'Enter' && this.timestamp === this.parseTime(this.$refs.input.value))
			{
				this.$emit('enterSave');
			}

			this.hideMenu();
		},
		showMenu(): void
		{
			MenuManager.show({
				id: this.id,
				className: 'booking-time-selector-menu',
				bindElement: this.$refs.input,
				items: this.getMenuItems(),
				autoHide: true,
				maxHeight: 300,
				minWidth: this.$refs.input.offsetWidth,
				events: {
					onShow: this.onShow,
					onClose: this.hideMenu,
					onDestroy: this.hideMenu,
				},
			});

			this.getMenu().getPopupWindow().autoHideHandler = ({ target }) => {
				const popup = this.getMenu().getPopupWindow();
				const shouldHide = target !== popup.getPopupContainer() && !popup.getPopupContainer().contains(target);

				return shouldHide && !this.menuShownOnFocus;
			};

			this.scrollToClosestItem();
		},
		getMenuItems(): MenuItemOptions[]
		{
			const date = new Date(this.timestamp);
			const dateTs = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime() - this.offset;

			const timestamps = Array.from({ length: 49 }).fill(0)
				.map((_, i) => dateTs + i * halfHour)
				.filter((timestamp: number) => timestamp >= this.minTs + hour)
			;

			if (this.minTs > 0 && timestamps[0] - (this.minTs + hour) >= hour / 4)
			{
				timestamps.unshift(this.minTs + hour);
			}

			if (this.minTs > 0)
			{
				timestamps.unshift(this.minTs + hour / 4, this.minTs + hour / 2, this.minTs + 3 * hour / 4);
			}

			return timestamps.map((timestamp: number): MenuItemOptions => ({
				id: timestamp,
				html: `
					<span
						data-element="booking-time-selector-item"
						data-timestamp="${timestamp}"
					>
						${this.formatTime(timestamp)}
					</span>
					<span class="menu-popup-item-hint">${this.getDurationHint(timestamp)}</span>
				`,
				className: timestamp === this.timestamp ? 'menu-popup-no-icon --selected' : 'menu-popup-no-icon',
				onclick: () => {
					this.$emit('update:modelValue', timestamp);
					this.getMenu().close();
				},
			}));
		},
		getDurationHint(timestamp: string): string
		{
			if (this.minTs > 0)
			{
				const diff = timestamp - this.minTs;
				if (diff < hour)
				{
					return new Duration(diff).format();
				}

				const roundedDiff = Math.round(diff / halfHour) * halfHour;
				const hint = new Duration(roundedDiff).format();

				return diff === roundedDiff ? hint : this.loc('BOOKING_TIME_SELECTOR_APPROXIMATE_VALUE', {
					'#VALUE#': hint,
				});
			}

			return '';
		},
		scrollToClosestItem(): void
		{
			const closest = this.getMenu().getMenuItems().reduce((prev: MenuItem, curr: MenuItem): MenuItem => {
				return (Math.abs(curr.getId() - this.timestamp) < Math.abs(prev.getId() - this.timestamp) ? curr : prev);
			});

			const closestItemNode = closest?.getLayout()?.item;
			if (!closestItemNode)
			{
				return;
			}

			const menuContainer = this.getMenu().getPopupWindow().getContentContainer();

			menuContainer.scrollTop = closestItemNode.offsetTop - closestItemNode.offsetHeight - 36 * 3;
		},
		isShown(): boolean
		{
			return this.getMenu()?.getPopupWindow().isShown() ?? false;
		},
		adjustMenuPosition(): void
		{
			this.getMenu()?.getPopupWindow().adjustPosition();
		},
		getMenu(): Menu | null
		{
			return MenuManager.getMenuById(this.id);
		},
		onShow(): void
		{
			this.isMenuShown = true;
			this.freeze();
			this.$refs.input.select();
		},
		hideMenu(): void
		{
			this.isMenuShown = false;
			this.unfreeze();
			MenuManager.destroy(this.id);
		},
		freeze(): void
		{
			this.$emit('freeze');
		},
		unfreeze(): void
		{
			this.$emit('unfreeze');
		},
		onInput(event: InputEvent): void
		{
			const input = this.$refs.input;

			if (event.inputType === 'deleteContentBackward')
			{
				return;
			}

			input.value = timeFormatter.getMaskedTime(input.value, event.data);

			if (input.value === this.formatTime(this.parseTime(input.value)))
			{
				this.onChange();
			}
		},
		onChange(): void
		{
			let timestamp = this.parseTime(this.$refs.input.value);

			if (timestamp < this.minTs)
			{
				const value = this.formatTime(timestamp);
				const validItem = this.getMenuItems().find((item: MenuItemOptions) => item.text === value);
				timestamp = validItem?.id ?? timestamp;
			}

			this.$refs.input.value = this.formatTime(timestamp);

			this.$emit('update:modelValue', timestamp);
		},
		formatTime(timestamp: number): string
		{
			return timeFormatter.formatTime(timestamp + this.offset);
		},
		parseTime(value: string): number
		{
			return timeFormatter.parseTime(value, this.timestamp + this.offset) - this.offset;
		},
	},
	watch: {
		modelValue(): void
		{
			this.$refs.input.value = this.formatTime(this.timestamp);
		},
	},
	template: `
		<div
			class="booking-time-selector"
			:class="{'--menu-shown': isMenuShown}"
		>
			<input
				class="booking-time-selector-input"
				:class="{'--error': hasError}"
				ref="input"
				@focus="onFocus"
				@mousedown="onMouseDown"
				@click="onClick"
				@keydown="onKeyDown"
				@input="onInput"
				@change="onChange"
			/>
			<div
				class="ui-icon-set --chevron-down"
			></div>
		</div>
	`,
};
