export default class UnifiedLinkItem extends BX.UI.Viewer.Item
{
	unifiedLink: ?string;

	constructor(options)
	{
		const normalizedOptions = options || {};

		super(normalizedOptions);
	}

	setController(controller: BX.UI.Viewer.Controller): void
	{
		super.setController(controller);

		this.controller.preload = 0;
	}

	setPropertiesByNode(node: HTMLElement): void
	{
		super.setPropertiesByNode(node);

		this.unifiedLink = node.dataset.unifiedLink;
	}

	getUnifiedLink(): string
	{
		return this.unifiedLink || '';
	}

	setUnifiedLink(link: string): void
	{
		this.unifiedLink = link;
	}

	loadData(): Promise
	{
		return new Promise((resolve, reject) => {
			const newWindow = window.open(this.getUnifiedLink(), '_blank');

			if (newWindow)
			{
				newWindow.focus();
				resolve(newWindow);
			}
			else
			{
				reject(new Error('Could not open new window'));
			}
		});
	}
}
