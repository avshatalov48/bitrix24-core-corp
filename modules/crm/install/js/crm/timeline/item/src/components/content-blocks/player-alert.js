import LineOfTextBlocks from './line-of-text-blocks';
import { AlertColor, AlertIcon } from 'ui.alerts';

export const PlayerAlert = {
	components: {
		LineOfTextBlocks,
	},
	props: {
		blocks: {
			type: Object,
			required: false,
			default: () => ({}),
		},
		color: {
			type: String,
			required: false,
			default: AlertColor.DEFAULT,
		},
		icon: {
			type: String,
			required: false,
			default: AlertIcon.NONE,
		},
	},
	computed: {
		containerClassname() {
			return [
				'crm-timeline__player-alert',
				'ui-alert',
				'ui-alert-xs',
				'ui-alert-text-center',
				this.color,
				this.icon,
			]
		},
	},
	template: `
		<div :class="containerClassname">
			<div class="ui-alert-message">
				<LineOfTextBlocks :blocks="blocks"></LineOfTextBlocks>
			</div>
		</div>
	`
}