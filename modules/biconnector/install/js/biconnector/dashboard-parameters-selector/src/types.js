export type ParameterSelectorParams = {
	groups: number[],
	scopes: string[],
	params: string[],
	scopeParamsMap: {[scopeCode: string]: Parameter[]},
};

export type Parameter = {
	code: string,
	scope: string,
	title: string,
	description: string,
};

export type CheckCompatibilityResult = {
	incompatibleParams: Parameter[],
	addedParameters: Parameter[],
}
