export class MediaRenderer
{
	element = null;

	constructor(params)
	{
		params = params || {};
		this.kind = params.kind || 'video';
		if (params.track)
		{
			this.stream = new MediaStream([params.track]);
		}
		else
		{
			this.stream = null;
		}
	}

	render(el)
	{
		if (!el.srcObject || !el.srcObject.active || el.srcObject.id !== this.stream.id)
		{
			el.srcObject = this.stream;
		}
	}

	requestVideoSize()
	{
	}
}
