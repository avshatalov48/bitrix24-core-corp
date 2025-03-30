import './styles/route-action-menu-item.css';
import { BIcon } from 'ui.icon-set.api.vue';

export const UserActionMenuItem = {
	name: 'UserActionMenuItem',
	components: { BIcon },

	props: {
		id: {
			type: Number,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		avatar: {
			type: String,
			required: false,
			default: null,
		},
		workPosition: {
			type: String,
			required: false,
			default: null,
		},
	},
	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},
	template: `
		<div class="hr-structure-route-action-popup-menu-item">
			<div class="hr-structure-route-action-popup-menu-item__content">
				<img
					:src="!this.avatar ? '/bitrix/js/humanresources/company-structure/org-chart/src/images/default-user.svg' : encodeURI(this.avatar)"
					class="humanresources-tree__node_avatar --head"
				 	alt=""
				/>
				<div class="hr-structure-route-action-popup-menu-item__content-text-container">
					<span
						class="humanresources-tree__node_head-name"
						:title="this.name"
					>
						{{ this.name }}
					</span>
					<span class="humanresources-tree__node_head-position">{{ this.workPosition }}</span>
				</div>
			</div>
		</div>
	`,
};
