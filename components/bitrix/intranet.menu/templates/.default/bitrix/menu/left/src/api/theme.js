import { Dom, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';

export default class Theme
{
	bacgroundNode: HTMLElementTagNameMap = null;

	init(): void
	{
		if (!this.bacgroundNode)
		{
			const theme: any = this.#getThemePicker()?.getAppliedTheme();
			this.bacgroundNode = document.getElementsByTagName("body")[0];

			if (theme)
			{
				this.#applyTheme(this.bacgroundNode, theme);
			}

			EventEmitter.subscribe(
				'BX.Intranet.Bitrix24:ThemePicker:onThemeApply',
				(event) =>
				{
					this.#applyTheme(this.bacgroundNode, event.data.theme);
				},
			);
		}
	}

	#getThemePicker(): ?BX.Intranet.Bitrix24.ThemePicker
	{
		return BX.Intranet?.Bitrix24?.ThemePicker.Singleton ?? top.BX.Intranet?.Bitrix24?.ThemePicker.Singleton;
	}

	#applyTheme(container: HTMLElementTagNameMap, theme: any): void
	{
		const previewImage = `url('${Text.encode(theme.previewImage)}')`;
		Dom.style(container, 'backgroundImage', previewImage);
		Dom.removeClass(container, 'bitrix24-theme-default bitrix24-theme-dark bitrix24-theme-light');
		let themeClass = 'bitrix24-theme-default';

		if (theme.id !== 'default')
		{
			themeClass = String(theme.id).indexOf('dark:') === 0 ? 'bitrix24-theme-dark' : 'bitrix24-theme-light';
		}

		Dom.addClass(container, themeClass);
	}
}