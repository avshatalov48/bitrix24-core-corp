import { addCustomEvent, removeCustomEvent, Text } from 'main.core';
import '../css/feedback-message.css';
import { mapActions, mapGetters } from 'ui.vue3.vuex';
import { createFeedbackMessageBox } from 'crm.ai.feedback';

export const FeedbackMessage = {
	name: 'FeedbackMessage',
	data() {
		return {
			messageBoxInstance: null,
			uniquePopupId: `ai-form-fill-feedback-popup_${Text.getRandom(20).toLowerCase()}`,
		};
	},
	computed: {
		...mapGetters(['isFooterHiddenAndSaveDisabled']),
	},
	methods: {
		...mapActions(['closeFeedbackMessage', 'sendAiCallParsingData']),
		async onOKButton() {
			this.closeFeedbackMessage(true);
		},
		onCancelButton() {
			this.closeFeedbackMessage(false);
			this.sendAiCallParsingData('feedback_refused');
		},
		onMessageClose(event) {
			if (event.uniquePopupId === this.uniquePopupId)
			{
				this.closeFeedbackMessage(false);
			}
		},
	},
	mounted() {
		this.messageBoxInstance = createFeedbackMessageBox({
			onOk: this.onOKButton,
			onCancel: this.onCancelButton,
			popupOptions: {
				targetContainer: this.$refs.feedbackMessageRoot,
				id: this.uniquePopupId,
			},
		});

		addCustomEvent(window, 'BX.Main.Popup:onClose', this.onMessageClose);

		this.messageBoxInstance.show();
	},
	unmounted() {
		if (this.messageBoxInstance)
		{
			this.messageBoxInstance.close();
		}
		removeCustomEvent(window, 'BX.Main.Popup:onClose', this.onMessageClose);
	},
	template: `
		<div 
			ref="feedbackMessageRoot" 
			class="crm-ai-form-fill__confirm" 
			:class="{'hidden-footer': isFooterHiddenAndSaveDisabled}"
		></div>
	`,
};
