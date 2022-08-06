import {ajax} from 'main.core';
import {LoaderComponent} from "./loader";
import {ContentComponent} from "./content";
import {getExpirationLevel, ExpirationLevel} from "../expiration-options";


export const PopupWrapperComponent = {
	components: {LoaderComponent, ContentComponent},
	props: [
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
		ajax.runAction('intranet.license.getLicenseData', {})
			.then(({data}) => {
				this.license = data.license;
				const {daysLeft} = this.license;
				this.license.expirationLevel = getExpirationLevel(daysLeft);
				this.market = data.market;
				this.telephony = data.telephony;
				this.isAdmin = data.isAdmin;
				this.partner = data.partner;
				this.loaded = true;
				this.loading = false;
			})
		;
	},
	methods: {},
	template: `
		<div>
			<LoaderComponent v-if="loading" :size="100" />
			<ContentComponent 
				v-if="!loading && loaded" 
				:license="license"
				:market="market"
				:telephony="telephony"
				:isAdmin="isAdmin"
				:partner="partner">
			</ContentComponent>
		</div>
	`,
};