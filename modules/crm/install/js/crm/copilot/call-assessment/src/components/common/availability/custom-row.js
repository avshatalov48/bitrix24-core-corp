import { DatetimeConverter } from 'crm.timeline.tools';
import { Dom, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { DatePicker } from 'ui.date-picker';

import { Chevron } from './chevron';
import { weekdayType as weekdayTypeList } from '../../enum/weekday-type';
import { TimePeriod as TimePeriodValidator } from '../../../validator/time-period';

export const CustomRow = {
	components: {
		Chevron,
	},
	props: {
		weekdayType: {
			type: String,
			required: true,
		},
		startPoint: {
			type: String,
			required: true,
		},
		endPoint: {
			type: String,
			required: true,
		},
		modelValue: {
			type: Object,
			default: {},
		},
	},
	emits: ['update:modelValue'],

	data(): Object
	{
		return {
			currentWeekdayType: this.weekdayType ?? weekdayTypeList.working,
			currentStartPoint: this.startPoint,
			currentEndPoint: this.endPoint,
			isExpandedStartPoint: false,
			isExpandedEndPoint: false,
			timePeriodValidator: new TimePeriodValidator(),
		};
	},

	created(): void
	{
		this.datePicker = new DatePicker({
			type: 'time',
			dateFormat: DatetimeConverter.getSiteDateTimeFormat(true),
			timeFormat: DatetimeConverter.getSiteShortTimeFormat(),
			minuteStep: 5,
			events: {
				onSelectChange: (event: BaseEvent) => {
					const wrapper = event.getTarget().getInputField().parentNode;
					if (!Type.isDomNode(wrapper))
					{
						return;
					}

					const dates = event
						.getTarget()
						.getSelectedDates()
						.map((date: Date) => event.getTarget().formatDate(date, DatetimeConverter.getSiteDateTimeFormat(true)))
					;

					if (!dates[0])
					{
						return;
					}

					if (wrapper?.getAttribute('id') === 'start-point-selector')
					{
						this.currentStartPoint = dates[0];
					}
					else if (wrapper?.getAttribute('id') === 'end-point-selector')
					{
						this.currentEndPoint = dates[0];
					}

					const isValidRow = this.timePeriodValidator.validate(this.currentStartPoint, this.currentEndPoint);
					if (isValidRow)
					{
						Dom.removeClass(this.$refs.start, '--invalid');
						Dom.removeClass(this.$refs.end, '--invalid');
					}
					else
					{
						Dom.addClass(this.$refs.start, '--invalid');
						Dom.addClass(this.$refs.end, '--invalid');
					}

					this.$emit('update:modelValue', {
						weekdayType: this.currentWeekdayType,
						startPoint: this.currentStartPoint,
						endPoint: this.currentEndPoint,
					});
				},
				onHide: () => {
					this.isExpandedStartPoint = false;
					this.isExpandedEndPoint = false;
				},
			},
		});
	},

	beforeUnmount(): void
	{
		this.datePicker.destroy();
	},

	computed: {
		weekdayTypeList(): Array<Object>
		{
			return [
				{
					value: weekdayTypeList.monday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_MONDAY'),
				}, {
					value: weekdayTypeList.tuesday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_TUESDAY'),
				}, {
					value: weekdayTypeList.wednesday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_WEDNESDAY'),
				}, {
					value: weekdayTypeList.thursday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_THURSDAY'),
				}, {
					value: weekdayTypeList.friday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_FRIDAY'),
				}, {
					value: weekdayTypeList.saturday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_SATURDAY'),
				}, {
					value: weekdayTypeList.sunday,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_SUNDAY'),
				}, {
					value: weekdayTypeList.working,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_WORKING_DAYS'),
				}, {
					value: weekdayTypeList.weekends,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_WEEKENDS'),
				},
			];
		},

		startTimeString(): string
		{
			return this.currentStartPoint.split(' ')[1];
		},

		endTimeString(): string
		{
			return this.currentEndPoint.split(' ')[1];
		},
	},

	watch: {
		currentWeekdayType(weekdayType: String): void
		{
			this.currentWeekdayType = weekdayType;

			this.$emit('update:modelValue', {
				weekdayType: this.currentWeekdayType,
				startPoint: this.currentStartPoint,
				endPoint: this.currentEndPoint,
			});
		},
	},

	methods: {
		showPicker(event: PointerEvent): void
		{
			event.preventDefault();
			event.stopPropagation();

			const wrapper = event.target.parentNode;
			if (!Type.isDomNode(wrapper))
			{
				return;
			}

			this.datePicker.setTargetNode(wrapper);

			if (wrapper?.getAttribute('id') === 'start-point-selector')
			{
				this.datePicker.setInputField(this.$refs.start);
				this.datePicker.selectDate(this.currentStartPoint);
				this.isExpandedStartPoint = true;
			}
			else if (wrapper?.getAttribute('id') === 'end-point-selector')
			{
				this.datePicker.setInputField(this.$refs.end);
				this.datePicker.selectDate(this.currentEndPoint);
				this.isExpandedEndPoint = true;
			}

			this.datePicker.show();
		},
	},

	template: `
		<div class="crm-copilot__call-assessment-availability-selector-row">
			<div 
				class="crm-copilot__call-assessment-availability-selector-row-wrapper"
				style="padding-right: 15px;"
			>
				<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select
						class="ui-ctl-element crm-copilot__call-assessment-availability-selector-input  --weekday-type"
						v-model="currentWeekdayType"
						:value="currentWeekdayType"
					>
						<option
							v-for="typeItem in weekdayTypeList"
							:value="typeItem.value"
							:disabled="currentWeekdayType === typeItem.value"
						>
							{{typeItem.title}}
						</option>
					</select>
				</div>
			</div>
			<div 
				id="start-point-selector"
				class="crm-copilot__call-assessment-availability-selector-row-wrapper"
			>
				<input 
					type="text"
					ref="start"
					class="crm-copilot__call-assessment-availability-selector-input --time"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="startTimeString"
				>
				<Chevron
					:isExpanded="isExpandedStartPoint"
					@click="showPicker"
				/>
			</div>
			<div class="crm-copilot__call-assessment-availability-selector-delimiter"></div>
			<div
				id="end-point-selector"
				class="crm-copilot__call-assessment-availability-selector-row-wrapper"
			>
				<input
					type="text"
					ref="end"
					class="crm-copilot__call-assessment-availability-selector-input --time"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="endTimeString"
				>
				<Chevron
					:isExpanded="isExpandedEndPoint"
					@click="showPicker"
				/>
			</div>
		</div>
	`,
};
