export type Parameter = {
	code: string,
	scope: string,
	title: string,
	description: string,
};

export type CheckCompatibilityResult = {
	paramsToDelete: Set<string>,
	paramsToSave: Set<string>,
	paramsNotToSave: Set<string>,
	intersection: Set<string>,
}
