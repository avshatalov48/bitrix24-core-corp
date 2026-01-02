import { Tag } from 'main.core';
import { ShortQrAuth } from 'ui.short-qr-auth';
import { HeaderSubsectionContent } from './header-subsection-content';

export class MobileAuthContent extends HeaderSubsectionContent
{
	getContentWrapper(): HTMLElement
	{
		return this.cache.remember('content', () => {
			return Tag.render`
				<div class="intranet-avatar-widget-fast-mobile-auth__wrapper">
					${this.#getQR().render()}
					${this.#getWarning()}
				</div>
			`;
		});
	}

	getId(): string
	{
		return 'mobile-auth';
	}

	#getQR(): ShortQrAuth
	{
		return this.cache.remember('qr', () => {
			return new ShortQrAuth({ intent: 'profile', small: false, stub: false });
		});
	}

	#getWarning(): HTMLElement
	{
		return this.cache.remember('warning', () => {
			return Tag.render`
				<div class="intranet-avatar-widget-fast-mobile-auth-tool__warning">
					${this.getOptions().warning}
				</div>
			`;
		});
	}
}
