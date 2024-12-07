import type { Flow } from '../edit-form';

export class FormPage
{
	getId(): string {}
	getTitle(): string {}
	setFlow(flow: Flow): void {}
	render(): HTMLElement {}
	getFields(flowData: Flow): any {}
	getRequiredData(): string[] { return []; }
	update(): void { this.cleanErrors(); }
	cleanErrors(): void {}
	showErrors(incorrectData: string[]): void {}
	onContinueClick(flowData: Flow = {}): Promise<boolean> {
		return Promise.resolve(true);
	}
}
