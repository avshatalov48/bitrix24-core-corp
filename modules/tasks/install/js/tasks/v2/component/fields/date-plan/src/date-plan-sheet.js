import { Runtime } from 'main.core';
import { DurationFormat } from 'main.date';

import { DatePicker, DatePickerEvent } from 'ui.date-picker';
import { mapGetters } from 'ui.vue3.vuex';
import { Button as UiButton, ButtonColor, ButtonSize } from 'ui.vue3.components.button';
import { HeadlineMd, TextSm } from 'ui.system.typography.vue';
import { BInput, InputDesign } from 'ui.system.input.vue';
import { BMenu, type MenuOptions } from 'ui.vue3.components.menu';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model, DurationUnit } from 'tasks.v2.const';
import { BottomSheet } from 'tasks.v2.component.elements.bottom-sheet';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import { calendar } from 'tasks.v2.lib.calendar';
import { timezone } from 'tasks.v2.lib.timezone';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { datePlanMeta } from './date-plan-meta';
import { DatePlanSwitcher } from './components/date-plan-switcher';
import './date-plan.css';

type UnitParams = {
	duration: number,
	title: string,
};

const MAX_INT = 2 ** 31;

// @vue/component
export const DatePlanSheet = {
	components: {
		BottomSheet,
		BIcon,
		UiButton,
		HeadlineMd,
		TextSm,
		BInput,
		BMenu,
		DatePlanSwitcher,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isShown: {
			type: Boolean,
			required: true,
		},
		getBindElement: {
			type: Function,
			default: null,
		},
		getTargetContainer: {
			type: Function,
			default: null,
		},
	},
	emits: ['close'],
	setup(): Object
	{
		return {
			Outline,
			InputDesign,
			ButtonSize,
			ButtonColor,
			wasEmpty: false,
		};
	},
	data(): Object
	{
		return {
			isEndPicker: false,
			isEndDuration: false,
			isPickerShown: false,
			isMenuShown: false,
			unitId: DurationUnit.Days,
			durationValue: '',
			startTsTemp: null,
			endTsTemp: null,
			matchesWorkTimeTemp: null,
		};
	},
	computed: {
		...mapGetters({
			titleFieldOffsetHeight: `${Model.Interface}/titleFieldOffsetHeight`,
		}),
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		// do not remove startPlanTs, otherwise startTs is not updated
		startPlanTs(): number
		{
			return this.task.startPlanTs;
		},
		endPlanTs(): number
		{
			return this.task.endPlanTs;
		},
		startTs(): number
		{
			return this.startTsTemp ?? this.startPlanTs;
		},
		endTs(): number
		{
			return this.endTsTemp ?? this.endPlanTs;
		},
		wasFilled(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, datePlanMeta.id);
		},
		allowsChangeDatePlan: {
			get(): boolean
			{
				return this.task.allowsChangeDatePlan ?? false;
			},
			set(allowsChangeDatePlan: boolean): void
			{
				void taskService.update(this.taskId, { allowsChangeDatePlan });
			},
		},
		matchesWorkTime: {
			get(): boolean
			{
				return this.matchesWorkTimeTemp ?? this.task.matchesWorkTime ?? false;
			},
			set(matchesWorkTime: boolean): void
			{
				this.matchesWorkTimeTemp = matchesWorkTime;

				this.update();

				if (!this.maxDurationReached)
				{
					void taskService.update(this.taskId, { matchesWorkTime });
				}
			},
		},
		matchesSubTasksTime: {
			get(): boolean
			{
				return this.task.matchesSubTasksTime ?? false;
			},
			set(matchesSubTasksTime: boolean): void
			{
				void taskService.update(this.taskId, { matchesSubTasksTime });
			},
		},
		maxDurationReached(): boolean
		{
			return this.endTs && this.startTs && Math.floor((this.endTs - this.startTs) / 1000) >= MAX_INT;
		},
		menuOptions(): Function
		{
			return (): MenuOptions => ({
				bindElement: this.$refs.unit.$el,
				items: [DurationUnit.Days, DurationUnit.Hours, DurationUnit.Minutes].map((unitId) => {
					const unit = this.units[unitId];

					return {
						title: unit.title,
						isSelected: unitId === this.unitId,
						onClick: (): void => {
							this.unitId = unitId;

							this.update();
						},
					};
				}),
			});
		},
		units(): { [unitId: string]: UnitParams }
		{
			const unitDurations = DurationFormat.getUnitDurations();

			return {
				[DurationUnit.Days]: {
					duration: this.matchesWorkTime ? calendar.workdayDuration : unitDurations.d,
					title: this.loc('TASKS_V2_DATE_PLAN_DAYS'),
				},
				[DurationUnit.Hours]: {
					duration: unitDurations.H,
					title: this.loc('TASKS_V2_DATE_PLAN_HOURS'),
				},
				[DurationUnit.Minutes]: {
					duration: unitDurations.i,
					title: this.loc('TASKS_V2_DATE_PLAN_MINUTES'),
				},
			};
		},
		inputDesign(): string
		{
			return this.matchesSubTasksTime ? InputDesign.Disabled : InputDesign.Grey;
		},
	},
	watch: {
		isShown(isShown: boolean): void
		{
			if (isShown)
			{
				this.clearTemps();
				this.updateDuration(this.task.startPlanTs, this.task.endPlanTs);
				this.wasEmpty = !this.wasFilled;
			}
		},
		titleFieldOffsetHeight(): void
		{
			this.$refs.bottomSheet?.adjustPosition();
		},
	},
	mounted(): void
	{
		this.updateDuration(this.task.startPlanTs, this.task.endPlanTs);
	},
	methods: {
		clearStart(): void
		{
			this.clearTemps();
			this.durationValue = '';
			this.updateTaskPlan(null, this.task.endPlanTs);
		},
		clearEnd(): void
		{
			this.clearTemps();
			this.durationValue = '';
			this.updateTaskPlan(this.task.startPlanTs, null);
		},
		handleDateClick({ target }: { target: HTMLElement }, { isEnd }: { isEnd: boolean }): void
		{
			this.clearTemps();
			this.updateDuration(this.task.startPlanTs, this.task.endPlanTs);
			this.isEndPicker = isEnd;

			const datePicker = this.getDatePicker();
			datePicker.setTargetNode(target);
			datePicker.show();
			if (this.isEndPicker && this.task.endPlanTs)
			{
				datePicker.setFocusDate(this.task.endPlanTs + timezone.getOffset(this.task.endPlanTs));
			}
		},
		getDatePicker(): DatePicker
		{
			this.handlePickerChangedDebounced ??= Runtime.debounce(this.handlePickerChanged, 10, this);
			this.datePicker ??= new DatePicker({
				enableTime: true,
				selectionMode: 'range',
				defaultTime: calendar.defaultTime,
				events: {
					[DatePickerEvent.SELECT]: this.handlePickerChangedDebounced,
					[DatePickerEvent.DESELECT]: this.handlePickerChangedDebounced,
					onShow: (): void => {
						this.isPickerShown = true;
					},
					onHide: (): void => {
						this.isPickerShown = false;
					},
				},
			});

			return this.datePicker;
		},
		handlePickerChanged(): void
		{
			let startPlanTs = this.preparePickerTimestamp(this.datePicker.getRangeStart());
			let endPlanTs = this.preparePickerTimestamp(this.datePicker.getRangeEnd());
			if (this.isEndPicker && !endPlanTs && !this.task.startPlanTs)
			{
				[startPlanTs, endPlanTs] = [null, startPlanTs];
			}

			if (startPlanTs && !this.task.startPlanTs)
			{
				startPlanTs = calendar.setHours(startPlanTs, calendar.workdayStart.H, calendar.workdayStart.M);
			}

			this.updateDuration(startPlanTs, endPlanTs);
		},
		updateDuration(startTs: number, endTs: number): void
		{
			const [startPlanTs, endPlanTs] = this.prepareRange(startTs, endTs);
			if (!startPlanTs || !endPlanTs)
			{
				this.durationValue = '';

				this.updateTaskPlan(startPlanTs, endPlanTs);

				return;
			}

			const [durationValue, unitId] = this.calculateDurationValue(startPlanTs, endPlanTs, this.matchesWorkTime);
			this.durationValue = String(durationValue);
			this.unitId = unitId;

			this.updateTaskPlan(startPlanTs, endPlanTs);
		},
		update(): void
		{
			const [startPlanTs, endPlanTs] = this.prepareRange(this.task.startPlanTs, this.task.endPlanTs);
			if (startPlanTs && endPlanTs)
			{
				this.updateRangeFromDuration();
			}
			else
			{
				this.updateTaskPlan(startPlanTs, endPlanTs);
			}
		},
		updateRangeFromDuration(): void
		{
			let [startPlanTs, endPlanTs] = this.prepareRange(this.task.startPlanTs, this.task.endPlanTs);
			if (!startPlanTs && !endPlanTs)
			{
				return;
			}

			if (this.isEndDuration)
			{
				startPlanTs = null;
			}

			this.durationValue = this.durationValue.replaceAll(/\D/g, '');
			const duration = this.durationValue * this.units[this.unitId].duration;
			if (this.matchesWorkTime)
			{
				startPlanTs = calendar.calculateStartTs(startPlanTs, endPlanTs, duration);
				endPlanTs = calendar.calculateEndTs(startPlanTs, endPlanTs, duration);
			}
			else
			{
				startPlanTs ??= endPlanTs - duration;
				endPlanTs = startPlanTs + duration;
			}

			this.updateTaskPlan(startPlanTs, endPlanTs);
		},
		calculateDurationValue(startTs: number, endTs: number, matchWorkTime: boolean): [number, string]
		{
			const duration = matchWorkTime ? calendar.calculateDuration(startTs, endTs) : endTs - startTs;
			const minutes = duration / this.units[DurationUnit.Minutes].duration;
			const hours = duration / this.units[DurationUnit.Hours].duration;
			const days = duration / this.units[DurationUnit.Days].duration;

			return {
				[true]: [minutes, DurationUnit.Minutes],
				[Number.isInteger(hours)]: [hours, DurationUnit.Hours],
				[Number.isInteger(days)]: [days, DurationUnit.Days],
			}.true;
		},
		updateTaskPlan(startPlanTs: ?number, endPlanTs: ?number): void
		{
			this.startTsTemp = startPlanTs;
			this.endTsTemp = endPlanTs;
			if (this.maxDurationReached)
			{
				return;
			}

			this.clearTemps();

			const datePicker = this.getDatePicker();
			const options = { emitEvents: false };
			if (startPlanTs || endPlanTs)
			{
				const rangeStart = startPlanTs + timezone.getOffset(startPlanTs);
				const rangeEnd = endPlanTs ? endPlanTs + timezone.getOffset(endPlanTs) : null;
				if (startPlanTs)
				{
					datePicker.selectRange(rangeStart, rangeEnd, options);
				}
				else
				{
					datePicker.selectRange(rangeEnd, null, options);
				}
			}
			else
			{
				datePicker.deselectAll(options);
			}

			void taskService.update(this.taskId, { startPlanTs, endPlanTs });
		},
		clearTemps(): void
		{
			this.startTsTemp = null;
			this.endTsTemp = null;
			this.matchesWorkTimeTemp = null;
		},
		prepareRange(startTs: number, endTs: number): [?number, ?number]
		{
			if (this.matchesWorkTime)
			{
				return [calendar.clampWorkDateTime(startTs), calendar.clampWorkDateTime(endTs)];
			}

			return [startTs, endTs];
		},
		preparePickerTimestamp(date: ?Date): ?number
		{
			if (!date)
			{
				return null;
			}

			const dateTs = calendar.createDateFromUtc(date).getTime();

			return dateTs - timezone.getOffset(dateTs);
		},
		formatDate(timestamp: number): string
		{
			return calendar.formatDateTime(timestamp, { forceYear: true });
		},
		close(): void
		{
			if (this.wasEmpty && this.wasFilled)
			{
				void fieldHighlighter.setContainer(this.$root.$el).highlight(datePlanMeta.id);
			}

			if (this.task.matchesSubTasksTime && !this.isEdit)
			{
				this.updateDuration(null, null);
			}

			this.$emit('close');
		},
	},
	template: `
		<BottomSheet
			v-if="isShown"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="bottomSheet"
		>
			<div class="tasks-field-date-plan-sheet">
				<div class="tasks-field-date-plan-header">
					<HeadlineMd>{{ loc('TASKS_V2_DATE_PLAN_TITLE_SHEET') }}</HeadlineMd>
					<BIcon class="tasks-field-date-plan-close" :name="Outline.CROSS_L" :hoverable="true" @click="close"/>
				</div>
				<TextSm class="tasks-field-date-plan-description">{{ loc('TASKS_V2_DATE_PLAN_DESCRIPTION') }}</TextSm>
				<div class="tasks-field-date-plan-fields">
					<BInput
						:modelValue="formatDate(startTs)"
						:label="loc('TASKS_V2_DATE_PLAN_START')"
						:design="inputDesign"
						:icon="Outline.CALENDAR_WITH_SLOTS"
						:withClear="Boolean(startTs)"
						:active="isPickerShown && !isEndPicker"
						@clear="clearStart"
						@click="handleDateClick($event, { isEnd: false })"
						@focus="$event.target.blur()"
					/>
					<BInput
						:modelValue="formatDate(endTs)"
						:label="loc('TASKS_V2_DATE_PLAN_END')"
						:design="inputDesign"
						:icon="Outline.CALENDAR_WITH_SLOTS"
						:withClear="Boolean(endTs)"
						:active="isPickerShown && isEndPicker"
						@clear="clearEnd"
						@click="handleDateClick($event, { isEnd: true })"
						@focus="$event.target.blur()"
					/>
					<div class="tasks-field-date-plan-duration">
						<BInput
							v-model="durationValue"
							:label="loc('TASKS_V2_DATE_PLAN_DURATION')"
							:design="inputDesign"
							:error="maxDurationReached ? ' ' : null"
							@input="updateRangeFromDuration"
							@focus="isEndDuration = !task.startPlanTs"
							@blur="isEndDuration = false"
						/>
						<BInput
							:modelValue="units[unitId].title"
							:design="inputDesign"
							:dropdown="true"
							:active="isMenuShown"
							ref="unit"
							@click="isMenuShown = true"
							@focus="$event.target.blur()"
						/>
					</div>
				</div>
				<div class="tasks-field-date-plan-switchers">
					<DatePlanSwitcher
						v-if="task.rights.edit"
						v-model="allowsChangeDatePlan"
						:text="loc('TASKS_V2_DATE_PLAN_ALLOW_CHANGE')"
					/>
					<DatePlanSwitcher
						v-model="matchesWorkTime"
						:text="loc('TASKS_V2_DATE_PLAN_MATCH_WORK_TIME')"
						:hint="loc('TASKS_V2_DATE_PLAN_MATCH_WORK_TIME_HINT')"
					/>
					<DatePlanSwitcher
						v-model="matchesSubTasksTime"
						:text="loc('TASKS_V2_DATE_PLAN_MATCH_SUBTASKS_TIME')"
						:hint="loc('TASKS_V2_DATE_PLAN_MATCH_SUBTASKS_TIME_HINT')"
					/>
				</div>
				<div class="tasks-field-date-plan-footer">
					<UiButton
						:text="loc('TASKS_V2_DATE_PLAN_BUTTON_SAVE')"
						:size="ButtonSize.MEDIUM"
						:color="ButtonColor.PRIMARY"
						@click="close"
					/>
				</div>
			</div>
		</BottomSheet>
		<BMenu v-if="isMenuShown" :options="menuOptions()" @close="isMenuShown = false"/>
	`,
};
