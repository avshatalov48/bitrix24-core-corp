import { DatetimeConverter } from 'crm.timeline.tools';
import { Type } from 'main.core';
import { DateTimeFormat } from 'main.date';

import { Button } from './button';
import { PeriodRow } from './period-row';
import { CustomRow } from './custom-row';
import { availabilityType as availabilityTypeList } from '../../enum/availability-type';
import { weekdayType as weekdayTypeList } from '../../enum/weekday-type';

export const TypeSelector = {
	components: {
		Button,
		PeriodRow,
		CustomRow,
	},

	props: {
		availabilityType: {
			type: String,
			required: true,
		},
		readOnly: {
			type: Boolean,
		},
	},

	data(): Object
	{
		return {
			currentAvailabilityType: this.availabilityType,
			availabilityTypeData: {},
		};
	},

	beforeCreate(): void
	{
		this.availabilityTypeList = availabilityTypeList;
		this.dateTimeFormat = DatetimeConverter.getSiteDateTimeFormat(true);
	},

	computed: {
		typeList(): Array<Object>
		{
			return [
				{
					value: availabilityTypeList.always_active,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_ALWAYS_ACTIVE'),
				}, {
					value: availabilityTypeList.period,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_PERIOD'),
				}, {
					value: availabilityTypeList.custom,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_CUSTOM'),
				}, {
					value: availabilityTypeList.inactive,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_INACTIVE'),
				},
			];
		},

		hasRows(): boolean
		{
			return Type.isArrayFilled(this.availabilityTypeData[this.currentAvailabilityType]);
		},

		rowsForCurrentType(): Array<Object>
		{
			return this.availabilityTypeData[this.currentAvailabilityType] ?? [];
		},

		isExtendedAvailabilityType(): boolean
		{
			return this.currentAvailabilityType !== availabilityTypeList.always_active
				&& this.currentAvailabilityType !== availabilityTypeList.inactive
			;
		},
	},

	watch: {
		currentAvailabilityType(availabilityType: String): void
		{
			this.$emit('type-change', {
				availabilityType,
			});
		},
	},

	methods: {
		onActionButtonClick({ action, index }): void
		{
			if (
				this.currentAvailabilityType === availabilityTypeList.always_active
				|| this.currentAvailabilityType === availabilityTypeList.inactive
			)
			{
				return;
			}

			if (action === 'add')
			{
				const item = (
					this.currentAvailabilityType === availabilityTypeList.period
						? this.getDefaultDatePeriodItem()
						: this.getDefaultCustomItem()
				);

				this.rowsForCurrentType.push(item);
			}
			else // remove
			{
				this.rowsForCurrentType.splice(index, 1);
			}
		},

		onUpdateCustomRow(row: Object): void
		{
			this.$emit('data-change', {
				availabilityType: this.currentAvailabilityType,
				availabilityTypeData: this.getAvailabilityData(),
				updatedRow: row,
			});
		},

		getDefaultDatePeriodItem(): Object
		{
			const startPointDate = this.getRoundedToNearestMinuteDate(new Date(), 15);
			const endPointDate = this.getRoundedToNearestMinuteDate(new Date(), 15);
			endPointDate.setDate(endPointDate.getDate() + 7); // 1 week by default

			return {
				startPoint: DateTimeFormat.format(this.dateTimeFormat, startPointDate),
				endPoint: DateTimeFormat.format(this.dateTimeFormat, endPointDate),
			};
		},

		getDefaultCustomItem(): Object
		{
			const startPointDate = Math.round((new Date()).setHours(8, 0, 0, 0) / 1000);
			const endPointDate = Math.round((new Date()).setHours(18, 0, 0, 0) / 1000);

			return {
				weekdayType: weekdayTypeList.working,
				startPoint: DateTimeFormat.format(this.dateTimeFormat, startPointDate),
				endPoint: DateTimeFormat.format(this.dateTimeFormat, endPointDate),
			};
		},

		getRoundedToNearestMinuteDate(date: Date, minutes: number): Date
		{
			const currentTime = date.getTime();
			const ms = minutes * 60 * 1000;
			const roundedTime = Math.round(currentTime / ms) * ms;

			return new Date(roundedTime);
		},

		getAvailabilityData(): Array
		{
			return this.availabilityTypeData[this.currentAvailabilityType] ?? [];
		},

		setAvailabilityData(availabilityData: Array): void
		{
			if (Type.isArrayFilled(availabilityData))
			{
				this.availabilityTypeData[this.currentAvailabilityType] = availabilityData;

				if (this.currentAvailabilityType === this.availabilityTypeList.period)
				{
					this.availabilityTypeData[this.availabilityTypeList.custom] = [
						this.getDefaultCustomItem(),
					];
				}
				else if (this.currentAvailabilityType === this.availabilityTypeList.custom)
				{
					this.availabilityTypeData[this.availabilityTypeList.period] = [
						this.getDefaultDatePeriodItem(),
					];
				}

				return;
			}

			this.availabilityTypeData = {
				[this.availabilityTypeList.period]: [
					this.getDefaultDatePeriodItem(),
				],
				[this.availabilityTypeList.custom]: [
					this.getDefaultCustomItem(),
				],
			};
		},
	},

	template: `
		<div class="crm-copilot__call-assessment-availability-selector">
			<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select
					class="ui-ctl-element"
					v-model="currentAvailabilityType"
					:value="currentAvailabilityType"
				>
					<option
						v-for="typeItem in typeList"
						:value="typeItem.value"
						:disabled="readOnly && currentAvailabilityType !== typeItem.value"
					>
						{{typeItem.title}}
					</option>
				</select>
			</div>
			<div 
				class="crm-copilot__call-assessment-availability-rows"
				v-if="isExtendedAvailabilityType"
			>
				<div
					class="crm-copilot__call-assessment-availability-row"
					v-if="currentAvailabilityType === availabilityTypeList.period"
					v-for="(row, index) in rowsForCurrentType"
				>
					<PeriodRow
						:start-point="row.startPoint"
						:end-point="row.endPoint"
						v-model="availabilityTypeData[availabilityTypeList.period][index]"
					/>
					<Button
						:action="(index < rowsForCurrentType.length - 1 ? 'remove' : 'add')"
						:index="index"
						@button-click="onActionButtonClick"
					/>
				</div>
				<div
					class="crm-copilot__call-assessment-availability-row"
					v-if="currentAvailabilityType === availabilityTypeList.custom"
					v-for="(row, index) in rowsForCurrentType"
				>
					<CustomRow
						:weekday-type="row.weekdayType"
						:start-point="row.startPoint"
						:end-point="row.endPoint"
						v-model="availabilityTypeData[availabilityTypeList.custom][index]"
						v-on:update:modelValue="onUpdateCustomRow"
					/>
					<Button
						:action="(index < rowsForCurrentType.length - 1 ? 'remove' : 'add')"
						:index="index"
						@button-click="onActionButtonClick"
					/>
				</div>
			</div>
		</div>
	`,
};
