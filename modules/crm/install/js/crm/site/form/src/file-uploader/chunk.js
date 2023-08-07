export default class Chunk
{
	#data: Blob | null = null;
	#offset: number = 0;

	constructor(data: Blob, offset: number)
	{
		this.#data = data;
		this.#offset = offset;
	}

	getData(): Blob | null
	{
		return this.#data;
	}

	getOffset(): number
	{
		return this.#offset;
	}

	getSize(): number
	{
		return this.getData().size;
	}
}