import { addCustomEvent, Loc, removeCustomEvent, Text } from 'main.core';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { MessageBox } from 'ui.dialogs.messagebox';
import '../css/close-confirm.css';

export const CloseConfirm = {
	name: 'CloseConfirm',
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
		...mapMutations(['setIsConfirmPopupShow']),
		onMessageClose(event) {
			if (event.uniquePopupId === this.uniquePopupId)
			{
				this.setIsConfirmPopupShow(false);
			}
		},
	},
	mounted()
	{
		this.messageBoxInstance = MessageBox.create({
			message: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_CANCEL_CONFIRM_TEXT'),
			title: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_CANCEL_CONFIRM_TITLE'),
			okCaption: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_CANCEL_CONFIRM_CLOSE'),
			cancelCaption: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_CANCEL_CONFIRM_CANCEL'),
			onOk: () => {
				this.$Bitrix.eventEmitter.emit('crm:ai:form-fill:close-confirm:confirmClose', {});
			},
			onCancel: () => {
				this.setIsConfirmPopupShow(false);
			},
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
			popupOptions: {
				targetContainer: this.$refs.closeConfirmRoot,
				id: this.uniquePopupId,
			},
		});

		addCustomEvent(window, 'BX.Main.Popup:onClose', this.onMessageClose);

		this.messageBoxInstance.show();
	},
	unmounted()
	{
		if (this.messageBoxInstance)
		{
			this.messageBoxInstance.close();
		}
		removeCustomEvent(window, 'BX.Main.Popup:onClose', this.onMessageClose);
	},
	template: `
		<div 
			ref="closeConfirmRoot" 
			class="crm-ai-form-fill__close-confirm"
			:class="{'hidden-footer': isFooterHiddenAndSaveDisabled}"
		></div>
	`,
};
