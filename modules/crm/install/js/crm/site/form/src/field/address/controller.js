import { Controller as ContainerController } from '../container/controller';

type Options = {
	name: ?string;
	label: ?string;
	disabled: ?boolean;
	nestedFields: Array<Object>;
};

class Controller extends ContainerController
{
	static type(): string
	{
		return 'address';
	}

	actualizeFields11()
	{
		//this.nestedFields = [].concat([this.searchField], this.makeFields(fields));
	}
}

export {Controller, Options}