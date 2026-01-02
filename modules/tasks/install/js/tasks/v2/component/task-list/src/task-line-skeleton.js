import { BLine } from 'ui.system.skeleton.vue';

import './task-line.css';

export const TaskLineSkeleton = {
	components: {
		BLine,
	},
	props: {
		last: {
			type: Boolean,
			default: false,
		},
	},
	template: `
		<div class="tasks-task-line-container" :class="{ '--last': last }">
			<div class="tasks-task-line-wrapper">
				<div class="tasks-task-line-title-container">
					<BLine :width="200" :height="10"/>
				</div>
				<div class="tasks-task-line-fields-container">
					<div class="tasks-task-line-skeleton-avatar">
						<BLine circle :width="25" :height="25" :radius="25"/>
					</div>
					<div class="tasks-task-line-deadline">
						<BLine :width="80" :height="10"/>
					</div>
				</div>
				<div class="tasks-task-line-cross"/>
			</div>
		</div>
	`,
};
