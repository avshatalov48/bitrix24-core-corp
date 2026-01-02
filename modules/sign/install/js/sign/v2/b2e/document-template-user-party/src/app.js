// @vue/component
export const UserPartyApp = {
	name: 'UserPartyApp',
	props: {
		userParty: {
			/** @type UserParty */
			type: Object,
			required: true,
		},
		region: {
			type: String,
			required: true,
		},
	},
	mounted(): void
	{
		const userPartyLayout = this.userParty.getLayout(this.region);
		this.$refs.userPartyContainer.appendChild(userPartyLayout);
	},
	template: `
		<div ref="userPartyContainer" class="sign-b2e-user-party-container"></div>
	`,
};
