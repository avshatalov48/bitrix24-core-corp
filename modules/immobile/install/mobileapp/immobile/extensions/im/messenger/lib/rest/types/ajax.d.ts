declare type RunActionResponse<DataType> = {
	status: 'success' | 'error' | 'denied',
	errors: RunActionError[],
	data: DataType,
};

export type RunActionError = {
	message: string,
	code: string | number,
	customData: JsonObject | null,
};

type JsonValue = string | number | boolean | { [x: string]: JsonValue } | Array<JsonValue>;
type JsonObject = Object<string, JsonValue>;
