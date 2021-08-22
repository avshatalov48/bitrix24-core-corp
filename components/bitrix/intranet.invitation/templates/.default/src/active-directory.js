export class ActiveDirectory
{
	constructor(parent)
	{
		this.parent = parent;
	}

	showForm()
	{
		BX.UI.Feedback.Form.open(
			{
				id: 'intranet-active-directory',
				defaultForm: {id: 309, sec: 'fbc0n3'}
			}
		);
	}
}