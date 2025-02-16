import { Calendar } from './calendar/calendar';
import './sidebar.css';

export const Sidebar = {
	components: {
		Calendar,
	},
	template: `
		<div class="booking-booking-sidebar">
			<Calendar/>
		</div>
	`,
};
