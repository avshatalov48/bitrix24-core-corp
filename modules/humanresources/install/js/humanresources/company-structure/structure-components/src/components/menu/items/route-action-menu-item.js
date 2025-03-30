import './styles/route-action-menu-item.css';
import { BIcon } from 'ui.icon-set.api.vue';

export const RouteActionMenuItem = {
	name: 'RouteActionMenuItem',
	components: { BIcon },

	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: false,
			default: '',
		},
		imageClass: {
			type: String,
			required: false,
			default: '',
		},
		bIcon: {
			type: Object,
			required: false,
			default: null,
		},
	},

	methods: {
		capitalizedText(text: string): string
		{
			return text.charAt(0).toUpperCase() + text.slice(1);
		},
	},

	template: `
		<div class="hr-structure-route-action-popup-menu-item">
			<div class="hr-structure-route-action-popup-menu-item__content">
				<BIcon
					v-if="bIcon"
					:name="bIcon.name"
					:size="bIcon.size || 20"
					:color="bIcon.color || 'black'"
				/>
				<div
					v-if="!bIcon && imageClass"
					class="hr-structure-route-action-popup-menu-item__content-icon-container"

				>
					<div
						class="hr-structure-route-action-popup-menu-item__content-icon"
						:class="imageClass"
					/>
				</div>
				<div class="hr-structure-route-action-popup-menu-item__content-text-container">
					<div
						class="hr-structure-route-action-popup-menu-item__content-title"
					>
						{{ capitalizedText(this.title) }}
					</div>
					<div class="hr-structure-route-action-popup-menu-item__content-description">{{ capitalizedText(this.description) }}</div>
				</div>
			</div>
		</div>
	`,
};
