import {LoaderComponent} from "./loader";
import {ContentComponent} from "./content";

export const PopupWrapperComponent = {
	components: {LoaderComponent, ContentComponent},
	props: [
		"componentName",
		"signedParameters",
		"licenseType",
	],
	data()
	{
		return {
			loaded: false,
			loading: true,
			license: [],
			market: [],
			isAdmin: "",
			isCloud: "",
		};
	},
	mounted()
	{
		this.getData();
	},
	methods: {
		getData(event = {})
		{
			BX.ajax.runAction("intranet.license.getLicenseData", {
				data: {},
				analyticsLabel: {
					licenseType: this.licenseType,
					headerPopup: "Y"
				}
			}).then((response) => {

				this.license = response.data.license;
				this.market = response.data.market;
				this.partner = response.data.partner;
				this.telephony = response.data.telephony;
				this.isCloud = response.data.isCloud;
				this.isAdmin = response.data.isAdmin;
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
				:license="license"
				:market="market"
				:telephony="telephony"
				:isAdmin="isAdmin"
				:isCloud="isCloud"
				:partner="partner"
			>
			</ContentComponent>
		</div>
	`,
};