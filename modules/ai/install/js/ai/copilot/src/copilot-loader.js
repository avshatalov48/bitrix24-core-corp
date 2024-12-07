import { Tag } from 'main.core';

export class CopilotLoader
{
	render(): HTMLElement
	{
		return Tag.render`
			<div class="ai__copilot_loader">
				<div class="ai__copilot_loader-text"></div>
				<div class="ai__copilot_loader-dot dot-flashing"></div>
			</div>
		`;
	}
}
