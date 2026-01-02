import 'ui.icon-set.main';
import './empty-state.css';

export const EmptyState = {
	name: 'emptyState',

	props: {
		title: {
			type: String,
			required: false,
		},
		imageClass: {
			type: String,
			required: false,
		},
		description: {
			type: String,
			required: false,
		},
		list: {
			type: Array,
			required: false,
			default: [],
		},
	},

	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},

	template: `
		<div class="hr-department-detail-content_tab__empty-state-container">
			<div v-if="imageClass" :class="['hr-department-detail-content_tab__empty-state-icon', imageClass]"/>
			<span v-if="title" class="hr-department-detail-content__empty-tab-entity-title">
				{{ title }}
			</span>
			<span v-if="description" class="hr-department-detail-content_tab__empty-state-description">
				{{ description }}
			</span>
			<div v-if="list.length > 0" class="hr-department-detail-content_tab__empty-state-list">
				<div class="hr-department-detail-content_tab__empty-state-list-item"  v-for="item in list">
					<div 
						class="ui-icon-set --circle-check hr-department-detail-content_tab__empty-state-list-item-check"
						style="--ui-icon-set__icon-size: 24px;"
					/>
					<div class="hr-department-detail-content_tab__empty-state-list--item-text">
						{{ item.text }}
					</div>
				</div>
			</div>
			<slot name="content"/>
		</div>
	`,
};
