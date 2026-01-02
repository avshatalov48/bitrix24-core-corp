import { Calendar } from './calendar/calendar';
import { WaitList } from './wait-list/wait-list';
import './sidebar.css';

export const Sidebar = {
	components: {
		Calendar,
		WaitList,
	},
	template: `
		<div class="booking-booking-sidebar">
			<Calendar/>
			<WaitList/>
		</div>
	`,
};
