
declare type TableField = {
	name: string,
	type: FieldType,
	unique?: boolean,
	index?: boolean,
}

declare enum FieldType
{
	integer = 'integer',
	text = 'text',
	date = 'date',
	boolean = 'boolean',
	json = 'json',
	map = 'map',
}

interface ITable
{
	getMap(): string
	getFields(): Array<TableField>
}