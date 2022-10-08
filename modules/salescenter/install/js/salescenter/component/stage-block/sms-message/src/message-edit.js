const MessageEdit = {
	props: {
		text: {
			type: String,
			required: true
		},
		selectedMode: {
			type: String,
			required: true,
		},
	},
	computed:
		{
			classObject()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-sms-content-message-text ': true,
					'salescenter-app-payment-by-sms-item-container-sms-content-message-text-edit': true
				}
			},
		},
	methods:
		{
			updateMessage(e)
			{
				this.text = e.target.innerText;
				this.$emit('edit-on-update-template', this.text);
			},

			isHasLink()
			{
				return this.text.match(/#LINK#/)
			},

			saveSmsTemplate(smsText)
			{
				BX.ajax.runComponentAction(
					"bitrix:salescenter.app",
					"saveSmsTemplate",
					{
						mode: "class",
						data: {
							smsTemplate: smsText,
							mode: this.selectedMode,
						},
						analyticsLabel: 'salescenterSmsTemplateChange'
					}
				).catch((response) => {
					const errorMessage = response.errors.map((err) => {return err.message}).join("; ");
					alert(errorMessage);
				});
			},

			adjustUpdateMessage(e)
			{
				this.updateMessage(e);

				if(!this.isHasLink())
				{
					this.showErrorHasLink(e)
				}
				else
				{
					this.saveSmsTemplate(this.text);
				}
			},

			onPressKey(e)
			{
				if(e.code === "Enter")
				{

					this.adjustUpdateMessage(e);
					this.afterPressKey(e);
				}
			},

			onBlur(e)
			{
				this.beforeBlur();
				this.adjustUpdateMessage(e);
			},

			afterPressKey(e)
			{
				this.$emit('edit-on-after-press-key', e);
			},

			beforeBlur(e)
			{
				this.$emit('edit-on-before-blur', e);
			},

			showErrorHasLink(e)
			{
				this.$emit('edit-on-has-link-error', e);

			}
		},
	template: `

		<div 
			contenteditable="true"	
			:class="classObject"
			@blur="onBlur($event)"
			@keydown="onPressKey($event)"
		>{{text}}
</div>

	`
};

export {
	MessageEdit
}