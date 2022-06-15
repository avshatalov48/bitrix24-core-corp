import {Vue} from 'ui.vue';

export default
{
	computed: {
		loc()
		{
			return Vue.getFilteredPhrases('CRM_CFG_C_SETTINGS_');
		}
	}
}
