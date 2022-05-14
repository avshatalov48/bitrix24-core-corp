import "ui.vue.components.smiles";

export const MobileSmiles = {
	methods:
	{
		onSelectSmile(event)
		{
			this.$emit('selectSmile', event);
		},
		onSelectSet(event)
		{
			this.$emit('selectSet', event);
		},
		hideSmiles()
		{
			this.$emit('hideSmiles');
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close">
			<div class="bx-messenger-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show" key="vote">
				<div class="bx-livechat-alert-close" @click="hideSmiles"></div>
				<div class="bx-messenger-smiles-box">
					<bx-smiles
						@selectSmile="onSelectSmile"
						@selectSet="onSelectSet"
					/>
				</div>
			</div>
		</transition>
	`
};