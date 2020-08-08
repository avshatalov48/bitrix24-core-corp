import {Vue} from 'ui.vue';

export default {
	computed: {
		localize()
		{
			return Vue.getFilteredPhrases('TIMELINE_DELIVERY_TAXI_');
		},
	},
};
