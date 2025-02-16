import { DateTimeFormat } from 'main.date';
import { Clients } from './clients/clients';
import { Profit } from './profit/profit';
import './after-title.css';

export const AfterTitle = {
	computed: {
		dateFormatted(): string
		{
			const format = DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT');

			return DateTimeFormat.format(format, Date.now() / 1000);
		},
	},
	components: {
		Clients,
		Profit,
	},
	template: `
		<div class="booking-toolbar-after-title">
			<div class="booking-toolbar-after-title-date" ref="date">
				{{ dateFormatted }}
			</div>
			<div class="booking-toolbar-after-title-info">
				<Clients/>
				<Profit/>
			</div>
		</div>
	`,
};
