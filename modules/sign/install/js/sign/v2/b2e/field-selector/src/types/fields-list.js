export type FieldsList = {
	[categoryId: string]: {
		CAPTION: string,
		FIELDS: Array<Field>,
		DYNAMIC_ID: any,
		MODULE_ID: ?string,
	},
};
