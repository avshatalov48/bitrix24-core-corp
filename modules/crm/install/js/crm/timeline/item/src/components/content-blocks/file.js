import { FileIcon } from 'ui.icons.generator';
import {TimelineAudio} from "./timeline-audio";

export default {
	components: {
		TimelineAudio,
	},
	props: {
		id: Number,
		text: String,
		href: String,
		size: Number,
		attributes: Object,
		hasAudioPlayer: {
			type: Boolean,
			required: false,
			default: false,
		}
	},

	computed: {
		fileExtension() {
			return this.text.split('.').slice(-1)[0] || '';
		},

		titleFirstPart(): string {
			return this.text.slice(0, -this.titleLastPartSize);
		},

		titleLastPart(): string {
			return this.text.slice(-this.titleLastPartSize);
		},

		titleLastPartSize(): number {
			return 10;
		},
	},

	mounted() {
		const fileIcon = new FileIcon({
			name: this.fileExtension,
		});

		fileIcon.renderTo(this.$refs.icon);
	},

	template:
		`
		<div class="crm-timeline__file">
			<div ref="icon" class="crm-timeline__file_icon"></div>
			<a
				target="_blank"
				class="crm-timeline__file_title crm-timeline__card_link"
				v-if="href"
				:title="text"
				:href="href"
				v-bind="attributes"
				ref="title"
			>
				<span>{{ titleFirstPart }}</span>
				<span>{{ titleLastPart }}</span>
			</a>
			<div class="crm-timeline__file_audio-player" v-if="this.hasAudioPlayer">
				<TimelineAudio :id="id" :mini="true" :src="href"></TimelineAudio>
			</div>
		</div>
		`
};
