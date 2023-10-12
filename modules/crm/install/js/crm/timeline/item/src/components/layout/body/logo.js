import {Action} from "../../../action";
import {Text} from "main.core";

export const Logo = {
	props: {
		type: String,
		addIcon: String,
		addIconType: String,
		icon: String,
		iconType: String,
		backgroundUrl: String,
		backgroundSize: Number,
		inCircle: {
			type: Boolean,
			required: false,
			default: false,
		},
		action: Object,
	},
	data() {
		return {
			currentIcon: this.icon,
		}
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

		iconClassname()
		{
			return [
				'crm-timeline__card-logo_icon',
				`--${this.currentIcon}`,
				{
					'--in-circle': this.inCircle,
					[`--type-${this.iconType}`]: !!this.iconType && !this.backgroundUrl,
					'--custom-bg': !!this.backgroundUrl,
				},
			];
		},

		addIconClassname() {
			return [
				'crm-timeline__card-logo_add-icon',
				`--type-${this.addIconType}`,
				`--icon-${this.addIcon}`
			]
		},

		iconInteriorStyle()
		{
			const result = {};

			if (this.backgroundUrl)
			{
				result.backgroundImage = 'url(' + encodeURI(Text.encode(this.backgroundUrl)) + ')';
			}

			if (this.backgroundSize)
			{
				result.backgroundSize = parseInt(this.backgroundSize) + 'px';
			}

			return result;
		}
	},
	watch: {
		icon(newIcon): void
		{
			this.currentIcon = newIcon;
		}
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
		setIcon(icon: String) {
			this.currentIcon = icon;
		}
	},
	template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<div :class="iconClassname">
					<i :style="iconInteriorStyle"></i>
				</div>
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
		</div>
	`
};
