import '../css/drop-area.css';

// @vue/component
export const DropArea = {
	template: `
		<div class="bx-im-content-chat-drop-area__container bx-im-content-chat-drop-area__scope">
			<div class="bx-im-content-chat-drop-area__box">
				<span class="bx-im-content-chat-drop-area__icon"></span>
				<label class="bx-im-content-chat-drop-area__label-text">
					{{ $Bitrix.Loc.getMessage('IM_CONTENT_DROP_AREA') }}
				</label>
			</div>
		</div>
	`,
};
