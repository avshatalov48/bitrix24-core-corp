import { DocumentIcon } from './icon/document-icon';
import { SearchIcon } from './icon/search-icon';

export const EmptyState = {
	name: 'EmptyState',

	components: {
		DocumentIcon,
		SearchIcon,
	},

	props: {
		icon: String,
		title: String,
		description: String,
	},

	template: `
		<div class="crm-copilot__call-card-replacement-empty">
			<div class="crm-copilot__call-card-replacement-empty-icon">
				<Component :is="icon"/>
			</div>
			<div class="crm-copilot__call-card-replacement-empty-title" v-html="title"></div>
			<div class="crm-copilot__call-card-replacement-empty-description" v-html="description"></div>
		</div>
	`,
};
