export type FileUploaderOptions = {
	baseContainer: ?HTMLElement,
	events?: { [event: string]: (event) => {} },
	ownerId: number,
	ownerTypeId: number,
	activityId: ?number,
	files: Array,
};
