import type Stage from './stage';

type CategoryOptions = {
	renderTo: HTMLElement;
	appContainer: HTMLDivElement,
	id: string | number,
	name: string,
	sort: number | string,
	default: boolean,
	stages: {
		P: Array<Stage>,
		S: Array<Stage>,
		F: Array<Stage>,
	},
	robotsSettingsLink: string,
	generatorSettingsLink: string,
	lazy: boolean,
	generatorsCount: number,
	allowWrite: boolean,
	canEditTunnels: boolean,
	canAddCategory: boolean,
	categoriesQuantityLimit: number,
	isAvailableGenerator: boolean,
	showGeneratorRestrictionPopup: () => void,
	isAvailableRobots: boolean,
	showRobotsRestrictionPopup: () => void,
};

export default CategoryOptions;