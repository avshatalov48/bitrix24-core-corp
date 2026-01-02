import { Loc, Tag, Type, Text } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';
import Notifier from './notifier';
import 'ui.icon-set';

export default class Notifier0 extends Notifier
{
	static STATUS = 0;
	static CODE = 9713;

	effectiveUrl: ?String;
	body: ?String;

	constructor(
		message: ?String,
		effectiveUrl: ?String,
		body: ?String,
	)
	{
		super(message);
		this.effectiveUrl = effectiveUrl;
		this.body = body;
	}

	getTitle(): String
	{
		return Loc.getMessage('BAAS_ERROR_REGISTRATION_TITLE_0');
	}

	getMessage(): String
	{
		return Loc.getMessage(
			'BAAS_ERROR_REGISTRATION_MESSAGE_0',
			{ '#effectiveUrl#': this.effectiveUrl },
		);
	}

	getEffectiveUrl(): ?String
	{
		return this.effectiveUrl;
	}

	getBody(): ?String
	{
		if (Type.isStringFilled(this.body))
		{
			return `<textarea style="width: 90%;">${Text.encode(this.body)}</textarea>`;
		}

		return '';
	}

	show(): void
	{
		const copyButton = Tag.render`<div class="ui-icon-set --copy-plates"></div>`;
		BX.clipboard.bindCopyClick(copyButton, {
			text: () => {
				return JSON.stringify({
					effectiveUrl: this.getEffectiveUrl(),
					body: this.getBody(),
				});
			},
		});

		MessageBox.show({
			title: this.getTitle(),
			message: Tag.render`
				<div>${this.getMessage()}
					<dd>
						<dt>${this.getEffectiveUrl()} ${copyButton}</dt>
						<dl>${this.getBody()}</dl>
					</dd>
				</div>
			`,
			modal: true,
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
			maxWidth: 1000,
		});
	}
}
