import {Vue} from 'ui.vue';
import {Manager} from 'salescenter.manager';

const RequisiteBlock = {
	props: ['companyId','isNewCompany'],
	computed:
		{
			loc() {
				return Vue.getFilteredPhrases('SC_MYCOMPANY_SETTINGS_')
			}
		},
	methods:{
		openSlider()
		{
			if(this.isNewCompany)
			{
				let url = '/crm/configs/mycompany/';
				window.open(url);
			}
			else
			{
				let url = '/crm/company/details/'+ this.companyId +'/?init_mode=edit';
				Manager.openSlider(url).then(() => this.onSettings());
			}
		},
		onSettings()
		{
			this.$emit('on-mycompany-requisite-settings');
		}
	},
	template: `   
			<div>
				<div class="salescenter-company-contacts-text">{{loc.SC_MYCOMPANY_SETTINGS_COMPANY_REQUISITE_INFO_V2}}</div>
				<div class="salescenter-company-contacts-text salescenter-company-contacts-text--link" @click="openSlider">{{loc.SC_MYCOMPANY_SETTINGS_COMPANY_REQUISITE_EDIT}}</div>
			</div>`
};
export {
	RequisiteBlock
}