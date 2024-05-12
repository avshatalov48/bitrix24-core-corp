import { Dom, Tag } from 'main.core';

const PROPERTIES = [
	'direction',
	'boxSizing',
	'width',
	'height',
	'overflowX',
	'overflowY',
	'borderTopWidth',
	'borderRightWidth',
	'borderBottomWidth',
	'borderLeftWidth',
	'borderStyle',
	'paddingTop',
	'paddingRight',
	'paddingBottom',
	'paddingLeft',
	'fontStyle',
	'fontVariant',
	'fontWeight',
	'fontStretch',
	'fontSize',
	'fontSizeAdjust',
	'lineHeight',
	'fontFamily',
	'textAlign',
	'textTransform',
	'textIndent',
	'textDecoration',
	'letterSpacing',
	'wordSpacing',
	'tabSize',
	'MozTabSize',
];

export type Coordinates = {
	top: number,
	left: number,
};

// eslint-disable-next-line sonarjs/cognitive-complexity
export function getCaretCoordinates(element: HTMLElement, position: number): Coordinates
{
	// eslint-disable-next-line no-eq-null
	const isFirefox = window.mozInnerScreenX !== null;

	const dummyEl = Tag.render`<div id='textarea-caret-position-dummy-div'></div>`;
	Dom.append(dummyEl, document.body);

	const style = dummyEl.style;
	const computed = window.getComputedStyle
		? window.getComputedStyle(element)
		: element.currentStyle;
	const isInput = element.nodeName === 'INPUT';

	style.whiteSpace = 'pre-wrap';
	if (!isInput)
	{
		style.wordWrap = 'break-word';
	}

	// Position off-screen
	style.position = 'absolute'; // required to return coordinates properly
	style.visibility = 'hidden'; // not 'display: none' because we want rendering

	// Transfer the element's properties to the div
	PROPERTIES.forEach((prop) => {
		if (isInput && prop === 'lineHeight')
		{
			// Special case for <input>s because text is rendered centered and line height may be != height
			if (computed.boxSizing === 'border-box')
			{
				const height = parseInt(computed.height, 10);
				const outerHeight = parseInt(computed.paddingTop, 10)
					+ parseInt(computed.paddingBottom, 10)
					+ parseInt(computed.borderTopWidth, 10)
					+ parseInt(computed.borderBottomWidth, 10)
				;

				const targetHeight = outerHeight + parseInt(computed.lineHeight, 10);
				if (height > targetHeight)
				{
					style.lineHeight = `${height - outerHeight}px`;
				}
				else if (height === targetHeight)
				{
					style.lineHeight = computed.lineHeight;
				}
				else
				{
					style.lineHeight = 0;
				}
			}
			else
			{
				style.lineHeight = computed.height;
			}
		}
		else
		{
			style[prop] = computed[prop];
		}
	});

	if (isFirefox)
	{
		if (element.scrollHeight > parseInt(computed.height, 10))
		{
			style.overflowY = 'scroll';
		}
	}
	else
	{
		style.overflow = 'hidden';
	}

	dummyEl.textContent = element.value.slice(0, Math.max(0, position));

	if (isInput)
	{
		dummyEl.textContent = dummyEl.textContent.replaceAll(/\s/g, '\u00A0');
	}

	const spanEl = Tag.render`<span></span>`;
	spanEl.textContent = element.value.slice(Math.max(0, position)) || '.'; // || because a completely empty faux span doesn't render at all
	Dom.append(spanEl, dummyEl);

	const coordinates = {
		top: spanEl.offsetTop + parseInt(computed.borderTopWidth, 10),
		left: spanEl.offsetLeft + parseInt(computed.borderLeftWidth, 10),
	};

	Dom.remove(dummyEl);

	return coordinates;
}
