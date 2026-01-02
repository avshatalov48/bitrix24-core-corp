import { BIcon } from 'ui.icon-set.api.vue';
import { Avatar } from 'ui.vue3.components.avatar';

import { IconSetMixin } from '../../../../mixins/icon-set-mixin';

import './style.css';

// eslint-disable-next-line no-unused-vars
type ImageProp = {
	title?: string,
	bIconName?: string,
	imageSrc?: string,
	iconClass?: string,
};

// @vue/component
export const EntityMenuItem = {
	components: {
		BIcon,
		Avatar,
	},
	mixins: [IconSetMixin],
	props: {
		title: {
			type: String,
			required: true,
		},
		image: {
			/** @type ImageProp */
			type: Object,
			default: () => ({}),
		},
	},
	template: `
		<div class="intranet-user-mini-profile__entity-menu-item" data-test-id="usermp_entity-menu-item">
			<div class="intranet-user-mini-profile__entity-menu-item-content">
				<div v-if="image"
					class="intranet-user-mini-profile__entity-menu-item__icon"
					:class="image.iconClass ?? null"
					data-test-id="usermp_entity-menu-item-icon"
				>
					<BIcon v-if="image.bIconName" 
						:name="image.bIconName"
						:size="18"
					/>
					<Avatar v-else
						:options="{
							size: 24,
							title,
							picPath: image.imageSrc ? encodeURI(image.imageSrc) : undefined,
						}"
					/>
				</div>
				<div 
					class="intranet-user-mini-profile__entity-menu-item__title"
					:title="title"
					data-test-id="usermp_entity-menu-item-title"
				>
					{{ title }}
				</div>
			</div>
		</div>
	`,
};
