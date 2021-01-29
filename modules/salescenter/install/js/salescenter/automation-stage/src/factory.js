import {Stage} from "./stage";
import {Invariable} from "./invariable";

let stages = [
	Stage,
	Invariable
];

class Factory
{
	static create(options)
	{
		let stage = stages
			.filter(item => options.type === item.type())[0];

		if (!stage)
		{
			throw new Error(`Unknown field type '${options.type}'`);
		}

		return new stage(options);
	}
}

export
{
	Factory
};