type savedFileParams = {
	url: string,
	toLibrary?: boolean,
}

type saveToDeviceParams = {
	files: Array<{
		url: string;
		toLibrary?: boolean;
	}>;
};

export { savedFileParams, saveToDeviceParams };
