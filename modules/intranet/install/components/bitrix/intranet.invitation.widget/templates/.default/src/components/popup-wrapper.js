import {LoaderComponent} from "./loader";
import {ContentComponent} from "./content";

export const PopupWrapperComponent = {
	components: {LoaderComponent, ContentComponent},
	props: [
		"isCrurrentUserAdmin",
	],
	data()
	{
		return {
			loaded: false,
			loading: true,
			invitationLink: "",
			structureLink: "",
			isInvitationAvailable: true,
			users: [],
		};
	},
	mounted()
	{
		this.getData();
	},
	methods: {
		getData(event = {})
		{
			BX.ajax.runAction("intranet.invitationwidget.getData", {
				data: {},
				analyticsLabel: {
					headerPopup: "Y"
				}
			}).then((response) => {

				this.invitationLink = response.data.invitationLink;
				this.structureLink = response.data.structureLink;
				this.isInvitationAvailable = response.data.isInvitationAvailable;
				this.isExtranetAvailable = response.data.isExtranetAvailable;
				this.users = response.data.users;
				this.loaded = true;
				this.loading = false;

			}, (response) => {

			});
		}
	},
	template: `
		<div>
			<LoaderComponent v-if="loading" :size="100" />
			<ContentComponent 
				v-if="!loading && loaded"
				:invitationLink="invitationLink"
				:structureLink="structureLink"
				:isInvitationAvailable="isInvitationAvailable"
				:isExtranetAvailable="isExtranetAvailable"
				:users="users"
				:isCrurrentUserAdmin="isCrurrentUserAdmin"
			>
			</ContentComponent>
		</div>
	`,
};