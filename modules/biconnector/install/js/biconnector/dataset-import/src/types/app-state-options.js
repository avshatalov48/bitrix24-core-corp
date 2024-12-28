import { DataType } from './data-types';

type PreviewData = {
	rows: Array,
};

type DatasetProperties = {
	id: Number,
	name: String,
	description: String,
	externalCode: String,
	externalName: String,
};

type FieldSettings = {
	id: Number,
	visible: Boolean,
	type: String,
	name: String,
	externalCode: String,
};

type DataFormats = {
	[DataType.money]: string,
	[DataType.double]: string,
	[DataType.date]: string,
	[DataType.datetime]: string,
}

type FileProperties = {
	fileToken: string,
	fileName: string,
	encoding: string,
	separator: string,
	firstLineHeader: boolean,
}

type ConnectionProperties = {
	connectionType: String,
	connectionId: Number,
	connectionName: String,
	tableName: String,
};

type Config = {
	datasetProperties: DatasetProperties,
	fieldsSettings: FieldSettings[],
	dataFormats: DataFormats,
	fileProperties: FileProperties,
	connectionProperties: ConnectionProperties,
};

type AppStateOptions = {
	previewData: PreviewData,
	config: Config,
};

export {
	PreviewData,
	DatasetProperties,
	FieldSettings,
	DataFormats,
	ConnectionProperties,
	Config,
	AppStateOptions,
};
