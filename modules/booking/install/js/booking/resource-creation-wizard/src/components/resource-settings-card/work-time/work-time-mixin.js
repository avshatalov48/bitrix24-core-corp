import { createNamespacedHelpers } from 'ui.vue3.vuex';
import { DateTimeFormat } from 'main.date';

const { mapGetters: mapResourceGetters } = createNamespacedHelpers('resource-creation-wizard');

export const WorkTimeMixin = {
	data(): Object
	{
		return {
			selected: {
				Mon: false,
				Tue: false,
				Wed: false,
				Thu: false,
				Fri: false,
				Sat: false,
				Sun: false,
			},
			daysLabel: '',
		};
	},
	created()
	{
		this.initialDays.forEach((day) => {
			if (Object.prototype.hasOwnProperty.call(this.selected, day))
			{
				this.selected[day] = true;
			}
		});

		this.daysLabel = this.formatDaysLabel();
	},
	methods: {
		formatDaysLabel(): string
		{
			const defaultString = this.loc('BRCW_SETTINGS_CARD_WORK_TIME_DAYS');

			const weekDays = this.companyScheduleSlots[0].weekDays;
			if (this.isArraysEqual(this.selectedDays, weekDays))
			{
				return defaultString;
			}

			const orderedDays = Object.keys(this.daysLabelMap);

			const sortedSelectedDays = this.selectedDays.sort((a, b) => {
				return orderedDays.indexOf(a) - orderedDays.indexOf(b);
			});
			const formattedDays = sortedSelectedDays.map((day) => this.daysLabelMap[day]);

			return String(formattedDays.join(', '));
		},
		getDayDefaultIndex(dayKey): number
		{
			const dayIndices = {
				Sun: 0,
				Mon: 1,
				Tue: 2,
				Wed: 3,
				Thu: 4,
				Fri: 5,
				Sat: 6,
			};

			return dayIndices[dayKey];
		},
		isArraysEqual(first: [], second: []): boolean
		{
			return first.length === second.length && first.every((value, index) => value === second[index]);
		},
	},
	computed: {
		...mapResourceGetters({
			companyScheduleSlots: 'getCompanyScheduleSlots',
			weekStart: 'weekStart',
		}),
		selectedDays(): Array
		{
			return Object.keys(this.selected).filter((day) => this.selected[day]);
		},
		daysLabelMap(): Object
		{
			const weekdays = [];
			const format = 'D';

			const allDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

			const startIndex = allDays.indexOf(this.weekStart);

			const orderedDays = [...allDays.slice(startIndex), ...allDays.slice(0, startIndex)];

			orderedDays.forEach((dayKey, index) => {
				const currentDayIndex = new Date().getDay();
				const targetDayIndex = this.getDayDefaultIndex(dayKey);
				const dayDifference = (targetDayIndex - currentDayIndex + 7) % 7;

				const dayDate = new Date();
				dayDate.setDate(dayDate.getDate() + dayDifference);

				weekdays[index] = DateTimeFormat.format(format, dayDate);
			});

			return orderedDays.reduce((result, dayKey, index) => {
				// eslint-disable-next-line no-param-reassign
				result[dayKey] = weekdays[index];

				return result;
			}, {});
		},
	},
};
