import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { Text } from 'main.core';
import { BaseEvent } from 'main.core.events';
import type { SlotRange } from 'booking.model.resources';
import { WorkTimeSlotRange } from './work-time-slot-range';

import './work-time-selector.css';

export const WorkTimeSelector = {
	name: 'ResourceSettingsCardWorkTimeSelector',
	emits: [
		'update',
		'updateGlobalSchedule',
		'getGlobalSchedule',
	],
	props: {
		initialSlotRanges: {
			type: Array,
			required: true,
		},
		isGlobalSchedule: {
			type: Boolean,
			required: true,
		},
		defaultSlotRange: {
			type: Object,
			required: true,
		},
		initialTimezone: {
			type: String,
			required: true,
		},
		currentTimezone: {
			type: String,
			required: true,
		},
		isCompanyScheduleAccess: {
			type: Boolean,
			required: true,
		},
	},
	components: {
		WorkTimeSlotRange,
		Icon,
	},
	data(): Object
	{
		return {
			localSlotRanges: this.processSlotRanges(this.initialSlotRanges),
			isChecked: this.isGlobalSchedule,
			hasTimezonesDifference: false,
			formattedInitialOffset: '',
			formattedCurrentOffset: '',
		};
	},
	created(): void
	{
		this.calculateDifferenceBetweenTimezones(this.initialTimezone, this.currentTimezone);
	},
	watch: {
		initialSlotRanges: {
			handler(newSlotRanges)
			{
				this.localSlotRanges = this.processSlotRanges(newSlotRanges);
			},
			deep: true,
		},
		isChecked(checked): void
		{
			this.$emit('updateGlobalSchedule', checked);
		},
	},
	methods: {
		openCompanyWorkTime(event): void
		{
			const isTextClick = event.target === event.currentTarget;
			if (!isTextClick && this.isCompanyScheduleAccess)
			{
				top.BX.Event.EventEmitter.subscribeOnce(
					top.BX.Event.EventEmitter.GLOBAL_TARGET,
					'SidePanel.Slider:onLoad',
					(baseEvent: BaseEvent) => {
						const slider = baseEvent.getTarget();
						slider.getWindow().BX.Event.EventEmitter.subscribeOnce(
							slider.getWindow().BX.Event.EventEmitter.GLOBAL_TARGET,
							'BX.Intranet.Settings:onSuccessSave',
							(innerBaseEvent: BaseEvent) => {
								const extraSettings = innerBaseEvent.getData();
								extraSettings.reloadAfterClose = false;

								this.$emit('getGlobalSchedule');
							},
						);
					},
				);

				BX.SidePanel.Instance.open(
					'/settings/configs/?page=schedule',
					{ cacheable: false },
				);

				event.preventDefault();
			}
		},
		updateSlotRange(index: number, slotRange: SlotRange): void
		{
			this.slotRanges[index] = slotRange;

			this.$emit('update', this.slotRanges);
		},
		addSlotRange(): void
		{
			this.slotRanges.push(this.defaultSlotRange);

			this.$emit('update', this.slotRanges);
		},
		removeSlotRange(index: number): void
		{
			this.slotRanges.splice(index, 1);

			this.$emit('update', this.slotRanges);
		},
		processSlotRanges(slotRanges): SlotRange[]
		{
			return slotRanges.map((slotRange) => {
				if (slotRange.id === null)
				{
					return {
						...slotRange,
						id: Text.getRandom(),
					};
				}

				return slotRange;
			});
		},
		getTimezoneOffset(timeZone: string): number
		{
			const now = new Date();
			const utcDate = new Date(now.toLocaleString('en-US', { timeZone: 'UTC' }));
			const tzDate = new Date(now.toLocaleString('en-US', { timeZone }));

			return (utcDate.getTime() - tzDate.getTime()) / (1000 * 60);
		},
		calculateDifferenceBetweenTimezones(initialTimezone: string, currentTimezone: string): void
		{
			const initialOffset = this.getTimezoneOffset(initialTimezone);
			const currentOffset = this.getTimezoneOffset(currentTimezone);

			this.hasTimezonesDifference = initialOffset !== currentOffset;

			if (this.hasTimezonesDifference)
			{
				const formatOffset = (offset) => {
					const sign = offset >= 0 ? '+' : '-';
					const hours = Math.floor(Math.abs(offset) / 60);
					const minutes = Math.abs(offset) % 60;

					return `${sign}${hours}:${minutes.toString().padStart(2, '0')}`;
				};

				this.formattedInitialOffset = `UTC ${formatOffset(-initialOffset)} ${initialTimezone}`;

				const totalDifferenceMinutes = currentOffset - initialOffset;

				this.formattedCurrentOffset = formatOffset(totalDifferenceMinutes);
			}
		},
		onSlotClick(): void
		{
			this.isChecked = false;
		},
	},
	computed: {
		companyWorkTimeOptionLabel(): string
		{
			return this.loc('BRCW_SETTINGS_CARD_WORK_TIME_COMPANY_OPTION');
		},
		slotRanges(): SlotRange[]
		{
			return this.localSlotRanges;
		},
		isSlotsDisabled(): boolean
		{
			return this.isChecked;
		},
		timezoneIconType(): string
		{
			return IconSet.EARTH_TIME;
		},
		formattedTimezonesText(): string
		{
			return this.loc('BRCW_SETTINGS_CARD_WORK_TIME_TIMEZONES')
				.replace('#utc#', this.formattedInitialOffset)
				.replace('#difference#', this.formattedCurrentOffset);
		},
	},
	template: `
		<div class="ui-form resource-creation-wizard__form-work-time-selector">
			<div class="ui-form-row-inline">
				<div class="ui-form-row">
					<label
						for="brcw-settings-work-time-company-option"
						class="ui-ctl ui-ctl-checkbox"
					>
						<input
							id="brcw-settings-work-time-company-option"
							data-id="brcw-settings-work-time-company-option"
							type="checkbox"
							class="ui-ctl-element"
							v-model="isChecked"
						>
						<span
							class="ui-ctl-label-text work-time-selector-label-text"
							:class="{'--disabled': !isCompanyScheduleAccess }"
							@click="openCompanyWorkTime"
							v-html="companyWorkTimeOptionLabel"
						></span>
					</label>
				</div>
			</div>
			<div
				class="resource-creation-wizard__form-work-time-selector-slots"
				:class="{'--disabled': isSlotsDisabled }"
			>
				<div
					v-for="(slotRange, index) in slotRanges"
					:key="slotRange.id"
					:data-id="'brcw-resource-work-time-slot-' + slotRange.id"
					class="ui-form-row-inline"
					@click="onSlotClick"
				>
					<WorkTimeSlotRange
						:id="index"
						:initialSlotRange="slotRange"
						:isLastRange="index === slotRanges.length - 1"
						@add="addSlotRange"
						@update="updateSlotRange"
						@remove="removeSlotRange"
					/>
				</div>
				<div
					v-if="hasTimezonesDifference"
					class="resource-creation-wizard__form-work-time-selector-slots-timezone"
				>
					<div class="resource-creation-wizard__form-work-time-selector-slots-timezone-icon">
						<Icon
							:name="timezoneIconType"
							:color="'var(--ui-color-palette-gray-50)'"
							:size="24"
						/>
					</div>
					<div
						class="resource-creation-wizard__form-work-time-selector-slots-timezone-text"
						v-html="formattedTimezonesText"
					></div>
				</div>
			</div>
		</div>
	`,
};
