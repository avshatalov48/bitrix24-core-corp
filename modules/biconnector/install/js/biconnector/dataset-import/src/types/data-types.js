import { Loc } from 'main.core';

type DataTypeDescription = {
	title: string,
	icon: string,
}

const DataType = {
	string: 'string',
	money: 'money',
	int: 'int',
	double: 'double',
	date: 'date',
	datetime: 'datetime',
};

const DataTypeDescriptions: Record<string, DataTypeDescription> = {
	[DataType.string]: {
		title: Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_TEXT'),
		icon: '--formatting',
	},
	[DataType.money]: {
		title: Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_MONEY'),
		icon: '--money',
	},
	[DataType.int]: {
		title: Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_NUMBER'),
		icon: '--numbers-123',
	},
	[DataType.double]: {
		title: Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_DECIMAL'),
		icon: '--numbers-05',
	},
	[DataType.date]: {
		title: Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_DATE'),
		icon: '--calendar-1',
	},
	[DataType.datetime]: {
		title: Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_TYPE_DATETIME'),
		icon: '--planning-2',
	},
};

type DataFormatTemplate = {
	[DataType.money]: string[],
	[DataType.double]: string[],
	[DataType.date]: string[],
	[DataType.datetime]: string[],
};

export {
	DataType,
	DataTypeDescriptions,
	DataFormatTemplate,
};
