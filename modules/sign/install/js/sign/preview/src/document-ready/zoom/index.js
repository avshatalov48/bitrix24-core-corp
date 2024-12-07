import { Tag, Dom } from 'main.core';

import { TouchController } from	'./touch-controller';

import './style.css';

export class Zoom
{
	constructor({ imageWrapper, previewArea })
	{
		this.imageWrapper = imageWrapper ? imageWrapper : null;
		this.previewArea = previewArea ? previewArea : null;
		this.layout = {
			plus: null,
			minus: null,
			value: null
		};
		this.currentZoom = 100;
		this.initTouchScroll();
	}

	lockPlus()
	{
		Dom.addClass(this.getNodePlus(), '--lock');
	}

	unLockPlus()
	{
		Dom.removeClass(this.getNodePlus(), '--lock');
	}

	lockMinus()
	{
		Dom.addClass(this.getNodeMinus(), '--lock');
	}

	unLockMinus()
	{
		Dom.removeClass(this.getNodeMinus(), '--lock');
	}

	getNodePlus()
	{
		if (!this.layout.plus)
		{
			const icon = Tag.render`<i></i>`;
			this.layout.plus = Tag.render`
				<div class="sign-preview__zoom-control --plus">
					${icon}
				</div>
			`;

			icon.addEventListener('click', () => {
				this.zoomPlus();
				this.unLockMinus();
				if (this.currentZoom === 200)
				{
					this.lockPlus();
				}
				this.adjustOverflow();
			});
		}

		return this.layout.plus;
	}

	getNodeMinus()
	{
		if (!this.layout.minus)
		{
			const icon = Tag.render`<i></i>`;
			this.layout.minus = Tag.render`
				<div class="sign-preview__zoom-control --minus --lock">
					${icon}
				</div>
			`;

			icon.addEventListener('click', () => {
				this.zoomMinus();
				this.unLockPlus();
				if (this.currentZoom === 100) 
				{
					this.lockMinus();
				}
				this.adjustOverflow();
			});
		}

		return this.layout.minus;
	}

	zoomPlus()
	{
		this.currentZoom += 25;
		this.getNodeValue().innerText = this.currentZoom;
		this.imageWrapper.style.setProperty('transform', `scale(${this.currentZoom / 100})`);
	}

	zoomMinus()
	{
		this.currentZoom -= 25;
		this.getNodeValue().innerText = this.currentZoom;
		this.imageWrapper.style.setProperty('transform', `scale(${this.currentZoom / 100})`);
	}

	adjustOverflow()
	{
		if (this.currentZoom > 100)
		{
			this.previewArea.style.setProperty('height', this.previewArea.offsetHight);
			Dom.addClass(this.previewArea, '--overflow-auto');
		}
		else
		{
			Dom.removeClass(this.previewArea, '--overflow-auto');
			this.previewArea.style.removeProperty('height');
		}
	}

	getNodeValue()
	{
		if (!this.layout.value)
		{
			this.layout.value = Tag.render`
				<div class="sign-preview__zoom-value">100</div>
			`;
		}

		return this.layout.value;
	}

	resetAll()
	{
		this.currentZoom = 100;
		this.lockMinus();
		this.unLockPlus();
		this.adjustOverflow();
		this.imageWrapper.style.removeProperty('transform');
		this.getNodeValue().innerText = this.currentZoom;
	}

	render()
	{
		return Tag.render`
			<div class="sign-preview__zoom">
				${this.getNodeMinus()}
				${this.getNodeValue()}
				${this.getNodePlus()}
			</div>
		`;
	}

	initTouchScroll()
	{
		new TouchController({
			target: this.previewArea
		});
	}
}