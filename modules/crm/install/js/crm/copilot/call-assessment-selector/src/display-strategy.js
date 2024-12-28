export interface DisplayStrategy
{
	getTargetNode(): HTMLElement;
	updateTitle(title: ?string): void;
	setLoading(isLoading: boolean): void;
}
