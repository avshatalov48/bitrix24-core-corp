import {Loc, Runtime} from 'main.core';
import Default from './default';

export default class AIImageGenerator extends Default
{
	id: string = 'ai-image-generator';
	buttonParams: ?Object = {
		name: 'AI image generator',
		iconClassName: 'feed-add-post-editor-btn-ai-image',
		disabledForTextarea: false,
		toolbarSort: 398,
		compact: true
	}

	handler()
	{
		Runtime.loadExtension('ai.picker').then(() => {
			const aiImagePicker = new BX.AI.Picker({
				moduleId: 'main',
				contextId: Loc.getMessage('USER_ID'),
				analyticLabel: 'main_post_form_comments_ai_image',
				saveImages: false,
				history: true,
				onSelect: (imageURL) => {
					fetch(imageURL)
						.then((response) => response.blob())
						.then((myBlob: Blob) => {
							BX.onCustomEvent(window, 'onAddVideoMessage', [myBlob, this.editor.getFormId()]);
						})
					;
				},
			});
			aiImagePicker.setLangSpace(BX.AI.Picker.LangSpace.image);
			aiImagePicker.image();
		});
	}

	parse(content, pLEditor)
	{
		return content;
	}

	unparse(bxTag, oNode)
	{
		return '';
	}
}