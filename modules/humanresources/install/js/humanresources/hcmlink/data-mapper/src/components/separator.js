import 'ui.icon-set.actions';

export const Separator = {
	name: 'Separator',

	props: {
		hasLink: {
			required: true,
			type: Boolean,
		},
	},

	computed: {
		styleObject()
		{
			return {
				'--ui-icon-set__icon-color': this.hasLink ? '#FFC34D' : '#D5D7DB',
			};
		},
	},

	template: `
		<div class="hr-hcmlink-separator__container" ref="container">
            <div 
	            style="--ui-icon-set__icon-size: 24px;"
	            :style="styleObject"
                class="ui-icon-set"
	            :class="[ hasLink ? '--arrow-right' : '--delete-hyperlink']"
            ></div>
		</div>
	`,
};
