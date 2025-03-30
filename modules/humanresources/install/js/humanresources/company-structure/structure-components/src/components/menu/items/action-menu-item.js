import './styles/action-menu-item.css';

const SupportedColors = new Set(['red']);

export const ActionMenuItem = {
	name: 'ActionMenuItem',
	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		imageClass: {
			type: String,
			required: false,
			default: '',
		},
		color: {
			type: String,
			required: false,
			default: '',
		},
	},

	computed: {
		colorClass(): string
		{
			if (SupportedColors.has(this.color))
			{
				return `--${this.color}`;
			}

			return '';
		},
	},

	template: `
		<div class="hr-structure-action-popup-menu-item">
			<div class="hr-structure-action-popup-menu-item__content">
				<div
					class="hr-structure-action-popup-menu-item__content-title"
					:class="[imageClass, colorClass]"
				>
					{{ title }}
				</div>
			</div>
		</div>
	`,
};
