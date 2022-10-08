import {Action} from "../../../action";

export const Logo = {
	props: {
		type: String,
		addIcon: String,
		addIconType: String,
		icon: String,
		iconType: String,
		inCircle: {
			type: Boolean,
			required: false,
			default: false,
		},
		action: Object,
	},
	computed: {
		className(): string
		{
			return [
				'crm-timeline__card-logo',
				`--${this.type}`, {
				'--clickable': this.action,
				}
			];
		},

		iconClassname() {
			return [
				'crm-timeline__card-logo_icon',
				`--${this.type||this.icon}`, {
					'--in-circle': this.inCircle,
				}
			]
		},

		addIconClassname() {
			return [
				'crm-timeline__card-logo_add-icon',
				`--type-${this.addIconType}`,
				`--icon-${this.addIcon}`
			]
		},
	},
	methods: {
		executeAction() {
			if (!this.action)
			{
				return;
			}

			const action = new Action(this.action);
			action.execute(this);
		},
	},
	template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<div :class="iconClassname">
					<i></i>
				</div>
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
<!--			<div v-if="action" @click="executeAction" class="crm-timeline__card-icon_action-btn"></div>-->
		</div>
	`
};
