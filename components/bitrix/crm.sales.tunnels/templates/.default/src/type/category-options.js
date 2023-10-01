import type Stage from './stage';

type CategoryOptions = {
	renderTo: HTMLElement;
	appContainer: HTMLDivElement,
	id: string | number,
	name: string,
	access: string | boolean,
	sort: number | string,
	default: boolean,
	stages: {
		P: Array<Stage>,
		S: Array<Stage>,
		F: Array<Stage>,
	},
	robotsSettingsLink: string,
	generatorSettingsLink: string,
	permissionEditLink: string,
	lazy: boolean,
	generatorsCount: number,
	generatorsListUrl: string,
	allowWrite: boolean,
	isCategoryEditable: boolean,
	areStagesEditable: boolean,
	isAvailableGenerator: boolean,
	isAutomationEnabled: boolean,
	isStagesEnabled: boolean,
	entityTypeId: Number,
};

export default CategoryOptions;
