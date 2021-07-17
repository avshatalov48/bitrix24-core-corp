export default class File
{
	static checkForPaternity()
	{
		return true;
	}

	constructor(data, container, options)
	{
		this.id = data['id'];
		this.data = data;

		this.container = container;
		this.options = options;
	}

	getId()
	{
		return this.id;
	}

	getNode()
	{
		return this.container.querySelector(`#wdif-doc-${this.id}`);
	}
}