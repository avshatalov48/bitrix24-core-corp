import { defineStore } from 'ui.vue3.pinia';

export const useChartStore = defineStore('hr-org-chart', {
	state: () => ({
		departments: new Map(),
		currentDepartments: [],
		focusedNode: 0,
		searchedUserId: 0,
	}),
});
