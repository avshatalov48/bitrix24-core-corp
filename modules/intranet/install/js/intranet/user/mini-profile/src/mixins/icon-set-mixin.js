import { Set, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

// @vue/mixin
export const IconSetMixin = {
	computed: {
		set: () => Set,
		outlineSet: () => Outline,
	},
};
