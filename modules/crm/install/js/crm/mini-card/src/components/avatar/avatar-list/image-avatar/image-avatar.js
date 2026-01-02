import { BitrixVueComponentProps } from 'ui.vue3';

import { Avatar } from '../../layout/avatar/avatar';

import './image-avatar.css';

export const ImageAvatar: BitrixVueComponentProps = {
	name: 'ImageAvatar',

	components: {
		Avatar,
	},

	props: {
		url: {
			type: String,
			required: true,
		},
	},

	computed: {
		encodeUri(): string
		{
			return encodeURI(this.url);
		},
	},

	template: `
		<Avatar class="--image">
			<img :src="encodeUri" alt=""/>
		</Avatar>
	`,
};
