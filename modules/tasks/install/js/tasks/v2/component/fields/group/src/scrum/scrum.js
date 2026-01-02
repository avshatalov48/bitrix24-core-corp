import { Loader } from 'tasks.v2.component.elements.user-custom-tag-selector';
import { groupService } from 'tasks.v2.provider.service.group-service';

import { Epic } from './epic/epic';
import { StoryPoints } from './story-points/story-points';
import './scrum.css';

// @vue/component
export const Scrum = {
	components: {
		Epic,
		StoryPoints,
		Loader,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): Object
	{
		return {
			hasScrumInfo: groupService.hasScrumInfo(this.taskId),
		};
	},
	async mounted(): Promise<void>
	{
		await groupService.getScrumInfo(this.taskId);

		this.hasScrumInfo = groupService.hasScrumInfo(this.taskId);
	},
	template: `
		<div v-if="hasScrumInfo" class="tasks-field-group-scrum">
			<Epic :taskId="taskId"/>
			<StoryPoints :taskId="taskId"/>
		</div>
		<div v-else class="tasks-field-group-scrum-loader">
			<Loader/>
		</div>
	`,
};
