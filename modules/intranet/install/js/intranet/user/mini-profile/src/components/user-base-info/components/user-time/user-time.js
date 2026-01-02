import { DateTimeFormat } from 'main.date';

import { LocMixin } from '../../../../mixins/loc-mixin';

import './style.css';

// @vue/component
export const UserTime = {
	name: 'UserTime',
	mixins: [LocMixin],
	props: {
		utcOffset: {
			type: Number,
			required: true,
		},
	},
	data(): { date: Date, tickInterval: number | null }
	{
		return {
			date: new Date(),
			tickInterval: null,
		};
	},
	computed: {
		formattedTime(): string
		{
			const date = this.date;

			const localOffset = date.getTimezoneOffset() * 60 * 1000;
			const targetOffset = this.utcOffset * 1000;

			const totalOffset = localOffset + targetOffset;

			date.setTime(date.getTime() + totalOffset);

			const sign = this.utcOffset >= 0 ? '+' : '-';

			const absOffset = Math.abs(this.utcOffset);

			const hours = Math.floor(absOffset / 3600);
			const minutes = Math.floor((absOffset % 3600) / 60);

			const timezoneParts = [
				this.loc('INTRANET_USER_MINI_PROFILE_USER_TZ_TEMPLATE', {
					'#VALUE#': `${sign}${hours.toString()}`,
				}),
			];

			if (minutes > 0)
			{
				timezoneParts.push(`:${minutes.toString().padStart(2, 0)}`);
			}

			return `${timezoneParts.join('')} (${this.formatDate(date)})`;
		},
	},
	created(): void
	{
		this.tickInterval = setInterval(() => {
			this.date = new Date();
		}, 1000);
	},
	unmounted(): void
	{
		clearInterval(this.tickInterval);
	},

	methods: {
		formatDate(date: Date): string
		{
			const template = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return DateTimeFormat.format(template, date);
		},
	},
	template: `
		<span class="intranet-user-mini-profile__user-time" data-test-id="usermp_user-time-display">
			{{ formattedTime }}
		</span>
	`,
};
