import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.actions';

import './resource-type.css';

export const ResourceType = {
	name: 'ResourceType',
	emits: ['selected'],
	props: {
		resourceType: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {
			IconSet,
		};
	},
	components: {
		Icon,
	},
	template: `
		<div class="booking--rcw--resource-type-item" @click="$emit('selected', resourceType)">
			<div class="rcw__resource-type">
				<div :class="['booking--rcw--resource-type__icon', 'booking--rcw--resource-type__icon--' + resourceType.code]">
				</div>
				<div class="rcw__resource-type__row">
					<div class="rcw__resource-type__label">
						<div class="rcw__resource-type__label-title">{{ resourceType.name }}</div>
						<div class="rcw__resource-type__label-description">{{ resourceType.description }}</div>
					</div>
					<Icon :name="IconSet.ARROW_RIGHT"/>
				</div>
			</div>
		</div>
	`,
};
