import type Stage from './stage';

type CategorySourceOptions = {
	ID: string | number,
	NAME: string,
	IS_DEFAULT: boolean,
	STAGES: Array<Stage>,
	SORT: string | number,
	RC_COUNT: number,
};

export default CategorySourceOptions;