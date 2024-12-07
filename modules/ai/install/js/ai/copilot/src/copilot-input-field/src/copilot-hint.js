import { Dom, Tag, Event } from 'main.core';
import { Popup } from 'main.popup';

type CopilotHintOptions = {
	target: HTMLElement,
	text: string;
	width: number;
}

type CopilotHintInitPopupOptions = CopilotHintOptions;

const initPopup = (options: CopilotHintInitPopupOptions): Popup => {
	const target = options.target;
	const text = options.text;

	return new Popup({
		content: Tag.render`<div style="padding-right: 20px;">${text}</div>`,
		darkMode: true,
		borderRadius: '4px',
		animation: 'fading-slide',
		autoHide: true,
		events: {
			onPopupShow: getPopupShowEventHandler(target),
		},
	});
};

const getPopupShowEventHandler = (target): Function => {
	return (popup: Popup): void => {
		const targetPos = Dom.getPosition(target);
		const popupPos = Dom.getPosition(popup.getPopupContainer());

		const angleOffset = Popup.getOption('angleLeftOffset');
		popup.setAngle({
			offset: popupPos.width / 2 - targetPos.width / 2 - 4,
		});

		popup.setBindElement({
			left: targetPos.left - popupPos.width / 2 + targetPos.width / 2 + angleOffset,
			top: targetPos.bottom,
		});

		popup.adjustPosition({
			forceBindPosition: true,
			forceLeft: true,
			forceTop: true,
		});
	};
};

export class CopilotHint
{
	#popup: Popup | null = null;

	constructor(options: CopilotHintOptions) {
		this.#popup = initPopup(options);
	}

	static addHintOnTargetHover(options: CopilotHintOptions): void
	{
		const popup = initPopup(options);
		const target = options.target;

		Event.bind(target, 'mouseenter', () => {
			popup.show();
		});

		Event.bind(target, 'mouseleave', () => {
			popup.close();
		});
	}

	show(): void
	{
		this.#popup?.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	isShown(): boolean
	{
		return Boolean(this.#popup?.isShown());
	}
}
