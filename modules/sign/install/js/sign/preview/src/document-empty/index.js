import { Tag } from 'main.core';

import './style.css';

export const DocumentEmpty =
{
	render()
	{
		return Tag.render`
			<div class="sign-preview__document-background">
				<div class="sign-preview__document-empty">
					<video poster="/bitrix/js/sign/preview/images/sign-preview-document-demo.jpg" autoplay="true" loop="true" muted="true" playsinline="true">
						<source type="video/mp4" src="/bitrix/js/sign/preview/images/sign-preview-document-demo.mp4">
						<source type="video/webm" src="/bitrix/js/sign/preview/images/sign-preview-document-demo.webm">
					</video>
				</div>
			</div>
		`;
	}
}