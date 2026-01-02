import './styles/route-action-menu-item.css';
import { BIcon } from 'ui.icon-set.api.vue';
import { getColorCode } from 'humanresources.company-structure.utils';

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
		itemClass: {
			type: String,
			required: false,
			default: '',
		},
		bIcon: {
			type: Object,
			required: false,
			default: null,
		},
		dataTestId: {
			type: String,
			required: false,
		},
	},

	methods: {
		getColor(bIcon: Object): string
		{
			if (bIcon.colorTokenName)
			{
				return getColorCode(bIcon.colorTokenName);
			}

			return bIcon.color ?? 'black';
		},
	},

	template: `
		<div
			class="hr-structure-route-action-popup-menu-item"
			:class="itemClass"
			:data-test-id="dataTestId"
		>
			<div class="hr-structure-route-action-popup-menu-item__content">
				<BIcon
					v-if="bIcon"
					:name="bIcon.name"
					:size="bIcon.size || 20"
					:color="getColor(bIcon)"
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
						{{ this.title }}
					</div>
					<div class="hr-structure-route-action-popup-menu-item__content-description">{{ this.description }}</div>
				</div>
			</div>
		</div>
	`,
};
