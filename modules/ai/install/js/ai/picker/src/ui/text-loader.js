import { Tag, Loc } from 'main.core';

import { Base } from './base';

import '../css/ui/text-loader.css';

export class TextLoader extends Base
{
	render(): HTMLElement
	{
		return Tag.render`
			<div class="ai__picker_text-loader">
				<div class="ai__picker_text-loader-line --one"></div>
				<div class="ai__picker_text-loader-line --two"></div>
				<div class="ai__picker_text-loader-line --three"></div>
				<div class="ai__picker_text-loader-line --four"></div>
				<div class="ai__picker_text-loader-cursor">
					<div class="ai__picker_text-loader-cursor-inner">
						<div class="ai__picker_text-loader-cursor-icon"></div>
						<span class="ai__picker_text-loader-cursor-text">
							${Loc.getMessage('AI_JS_PICKER_TEXT_LOADER')}
						</span>
					</div>
				</div>
			</div>
		`;
	}
}
