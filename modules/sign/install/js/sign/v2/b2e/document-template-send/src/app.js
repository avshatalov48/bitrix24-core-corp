import { useDocumentTemplateFillingStore } from 'sign.v2.b2e.sign-settings-templates';
import { LocMixin } from 'sign.v2.b2e.vue-util';
import { ProgressBar } from 'ui.progressbar';
import { mapState } from 'ui.vue3.pinia';
import { Popup } from 'ui.vue3.components.popup';

// @vue/component
export const TemplateSendApp = {
	name: 'TemplateSendApp',
	components: {
		Popup,
	},
	mixins: [LocMixin],
	computed: {
		progress(): number
		{
			return mapState(useDocumentTemplateFillingStore, ['sendProgress']).sendProgress();
		},
		configured(): boolean
		{
			return mapState(useDocumentTemplateFillingStore, ['configured']).configured();
		},
		title(): string
		{
			return this.loc('SIGN_DOCUMENT_TEMPLATE_SEND_TITLE');
		},
		description(): string
		{
			return this.configured
				? this.loc('SIGN_DOCUMENT_TEMPLATE_SEND_DESCRIPTION_ALLOW_CLOSE')
				: this.loc('SIGN_DOCUMENT_TEMPLATE_SEND_DESCRIPTION')
			;
		},
	},
	watch: {
		progress: {
			handler(newValue: number): void
			{
				this.progressBar.update(newValue);
			},
		},
	},
	created(): void
	{
		this.progressBar = new ProgressBar({
			maxValue: 100,
			value: 0,
			colorTrack: '#dfe3e6',
		});
	},
	mounted(): void
	{
		this.progressBar.renderTo(this.$refs.progressContainer);
	},
	methods: {
		onClose(): void
		{
			this.$Bitrix.eventEmitter.emit('sign:document-template-send:close');
		},
	},
	template: `
		<div class="send-b2e-overlay">
			<div class="sign-b2e-overlay-content">
				<div class="sign-b2e-overlay__animate-layout">
					<div class="sign-b2e-overlay__overlap-docs">
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
					</div>
					<div class="sign-b2e-overlay__overlap-docs">
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
					</div>
				</div>
				<div class="sign-b2e-overlay-progress-title">
					{{ title }}
				</div>
				<div class="sign-b2e-overlay-close-description">
					{{ description }}
				</div>
				<button v-if="configured" class="ui-btn ui-btn-md ui-btn-light-border ui-btn-round" @click="onClose">
					{{ loc('SIGN_DOCUMENT_TEMPLATE_SEND_CLOSE') }}
				</button>
			</div>
			<div class="send-b2e-progress-container" ref="progressContainer"></div>
		</div>
	`,
};
