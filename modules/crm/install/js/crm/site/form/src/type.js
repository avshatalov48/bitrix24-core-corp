import * as Form from "./form/registry";

type B24Options = {
	id: string|number;
	sec: string;
	lang: string;
	address: string;
	sign: string;
	entities: Array;
	data: Form.Options;
	usedBySiteButton: boolean;
};

export {B24Options};