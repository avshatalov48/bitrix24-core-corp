type RequestResponse = {
	data: {[key: string]: any},
	errors: [{[key: string]: any}],
	status: string,
};

export default RequestResponse;