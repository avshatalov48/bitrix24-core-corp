import { Tag } from 'main.core';
import { Loader } from 'main/loader';

import './style.css';

export const DocumentLoading =
{
	render()
	{
		const nodeLoader =  Tag.render`
			<div class="sign-preview__loader"></div>
		`;

		new Loader({
			size: 80
		}).show(nodeLoader);

		return Tag.render`
			<div class="sign-preview__document-background">
				<div class="sign-preview__document-loading">
					<div class="sign-preview__document-loading_container">
						<div class="sign-preview__document-loading_logo ${BX.message('LANGUAGE_ID') === 'ru' ? '--ru' : ''}"></div>
						${nodeLoader}		
						<div class="sign-preview__document-loading_message"></div>
					</div>
				</div>
			</div>
		`;;
	}
}