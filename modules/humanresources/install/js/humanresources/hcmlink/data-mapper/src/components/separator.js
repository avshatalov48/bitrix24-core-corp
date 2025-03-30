import 'ui.icon-set.actions';

import '../styles/separator.css';

export const Separator = {
	name: 'Separator',

	props: {
		hasLink: {
			required: true,
			type: Boolean,
		},
		mode: {
			type: String,
			required: true,
		},
	},

	computed: {
		styleObject(): Object
		{
			return {
				'--ui-icon-set__icon-color': this.hasLink ? '#FFC34D' : '#D5D7DB',
			};
		},
		iconClasses(): Object
		{
			return {
				'--arrow-right': this.hasLink,
				'--delete-hyperlink': !this.hasLink,
				'--color-orange': this.hasLink && this.mode === 'direct',
				'--color-blue': this.hasLink && this.mode === 'reverse',
			};
		},
	},

	template: `
		<div class="hr-hcmlink-separator__container" ref="container">
			<div
				class="ui-icon-set hr-hcmlink-separator__container-icon"
				:class="iconClasses"
			></div>
		</div>
	`,
};
