declare type TableField = {
	name: string,
	type: FieldType,
	unique?: boolean,
	index?: boolean,
	defaultValue?: any,
}

// TODO in order for the autocomplete of keys to work, you need to add return as const to method 'getFields' on TS.
declare type FieldsCollection<T extends readonly TableField[]> = {
	[K in T[number]['name']]: Extract<T[number], { name: K }>
};

declare enum FieldType {
	integer = 'integer',
	text = 'text',
	date = 'date',
	boolean = 'boolean',
	json = 'json',
	map = 'map',
}

interface ITable<T> {
	getMap(): string

	getFields(): Array<TableField>

	getList(options: TableGetListOptions<T>): Promise<{ items: Array<T> }>

	getListByIds(idList: Array<any>, shouldRestoreRows: boolean): Promise<{ items: Array<T> }>

	getById(id: any): Promise<T | null>
}

interface TableGetListOptions<T> {
	select?: Array<string>
	filter?: Partial<T>;
	order?: { name: 'asc' | 'desc' };
	offset?: number;
	limit?: number;
}


export interface IDatabaseTableInstance<T> {
	getMap(): Array<any>,

	getList(options: TableGetListOptions<T>): Promise<{ items: Array<T> }>,

	delete(filter: object): Promise<any>,

	add(items: object | Array<any>, replace: boolean): Promise<any>,

	update({filter, fields}): Promise<any>,

	drop(): void,

	create(): void,

	prepareInsert(insert: Array<any>): { statement: Array<any>, binding: Array<any>, values: Array<any> },

	executeSql({query, values}): Promise<any>,
}
