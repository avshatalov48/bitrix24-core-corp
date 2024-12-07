import { Tag, Loc } from 'main.core';
import { Base } from './base';

import '../css/ui/image-history-empty-state.css';

export class ImageHistoryEmptyState extends Base
{
	render(): HTMLElement
	{
		const text = Loc.getMessage('AI_JS_PICKER_IMAGE_EMPTY_STATE');

		return Tag.render`
			<div class="ai__picker_image-history-empty-state">
				<div class="ai__picker_image-history-empty-state-icon"></div>
				<div class="ai__picker_image-history-empty-state-text">
					${text}
				</div>
			</div>
		`;
	}
}
