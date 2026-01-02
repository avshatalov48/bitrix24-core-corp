import type { Flow } from '../edit-form';
import { BaseEvent } from 'main.core.events';

export class FormPage
{
	getId(): string {}
	getTitle(): string {}
	setFlow(flow: Flow): void {}
	getFlowId(): ?number {}
	render(): HTMLElement {}
	getFields(flowData: Flow): any {}
	getRequiredData(): string[] { return []; }
	update(): void { this.cleanErrors(); }
	cleanErrors(): void {}
	showErrors(incorrectData: string[]): void {}
	onContinueClick(flowData: Flow = {}): Promise<boolean> {
		return Promise.resolve(true);
	}

	onDialogLoad(event: BaseEvent): void {
		const dialog = event.getTarget();
		const tagSelector = dialog.getTagSelector();

		// set all unavailable items deselectable
		const selectedItems = tagSelector.getTags();
		selectedItems.forEach((item) => {
			if (!item.deselectable)
			{
				item.setDeselectable(true);
				item.render();
			}
		});
	}
}
