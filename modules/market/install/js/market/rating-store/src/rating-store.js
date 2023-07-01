import { defineStore } from 'ui.vue3.pinia';

export const ratingStore = defineStore('rating-store', {
	actions: {
		isActiveStar: function (currentStar, rating) {
			return currentStar <= parseInt(rating, 10);
		},
		getAppRating: function (value) {
			return value ? value : 0;
		},
	},
});