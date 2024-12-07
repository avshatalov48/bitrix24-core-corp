type UIFormBaseField = {
	isEmpty: () => boolean,
	isReadOnly: () => boolean,
	validate: () => boolean,
	isValid: () => boolean,
	isRequired: () => boolean,
	getId: () => string,
	hasUploadingFiles?: () => boolean,
	fieldContainerRef?: {},
	getCustomContentClickHandler?: () => {},
};

// eslint-disable-next-line no-unused-vars
type UIFormFieldFactory = {
	create: (type: string, props: {}) => UIFormBaseField | null,
};

type UIFormFieldFactoryFn = (props: {}) => UIFormBaseField | null;

type UIFormCompactMode = 'NONE' | 'ONLY' | 'BOTH' | 'FILL_COMPACT_AND_HIDE' | 'FILL_COMPACT_AND_KEEP';

// eslint-disable-next-line no-unused-vars
type UIFormFieldSchema = {
	type?: string,
	factory?: UIFormFieldFactoryFn,
	isPrimary?: boolean,
	debounceTimeout?: number,
	props: {
		id: string,
		ref?: () => {},
		onChange?: () => {},
	},
	compact?: {
		mode: UIFormCompactMode,
		factory: UIFormFieldFactoryFn,
		extraProps?: object,
	} | UIFormFieldFactoryFn,
	defaultCompactMode?: UIFormCompactMode,
};
