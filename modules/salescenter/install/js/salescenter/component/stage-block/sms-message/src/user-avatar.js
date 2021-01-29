const UserAvatar = {
	props: {
		manager: {
			type: Object,
			required: true
		},
	},

	computed:
	{
		avatarStyle()
		{
			let url = this.manager.photo ? { 'background-image': 'url(' + this.manager.photo + ')'} : null ;

			return [url];
		}
	},

	template: `
		<div class="salescenter-app-payment-by-sms-item-container-sms-user">
			<div class="salescenter-app-payment-by-sms-item-container-sms-user-avatar" :style="avatarStyle"></div>
			<div class="salescenter-app-payment-by-sms-item-container-sms-user-name">{{manager.name}}</div>
		</div>
	`
};

export {
	UserAvatar
}