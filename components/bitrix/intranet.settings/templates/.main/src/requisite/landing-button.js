import { Loc } from 'main.core';
import { Button, ButtonState } from 'ui.buttons';
import { ErrorCollection } from 'ui.form-elements.field';

export class LandingButton
{
	#button: Button

	constructor()
	{
		this.#button = new Button({
			className: 'landing-button-trigger',
			round: true,
			noCaps: true,
			size: BX.UI.Button.Size.MEDIUM,
			color: BX.UI.Button.Color.LIGHT_BORDER,
		});
	}

	setState(state: LandingButtonState): void
	{
		state.apply(this.#button);
	}

	getButton(): Button
	{
		return this.#button;
	}
}

export class LandingButtonState
{
	apply(button: Button)
	{

	}
}

export type LandingOptions = {
	is_connected: boolean,
	is_public: boolean,
	public_url: string,
	edit_url: string
}

export class EditState extends LandingButtonState
{
	#landing: LandingOptions

	constructor(landing: LandingOptions)
	{
		super();
		this.#landing = landing;
	}

	apply(button: Button): void
	{
		button.setText(Loc.getMessage('INTRANET_SETTINGS_BUTTON_EDIT'));
		button.setDropdown(false);
		button.bindEvent('click', () => {
			this.openNewTab(this.#landing.edit_url);
		});
	}

	openNewTab(url: string)
	{
		window.open(url, '_blank').focus();
	}
}

export class ShowState extends LandingButtonState
{
	#landing: LandingOptions
	#menuRenderer: function

	constructor(landing: LandingOptions, menuRenderer: function)
	{
		super();
		this.#landing = landing;
		this.#menuRenderer = menuRenderer;
	}

	apply(button: Button): void
	{
		button.setText(Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_SITE'));
		button.setDropdown(true);
		button.unbindEvent('click');
		button.setColor(BX.UI.Button.Color.PRIMARY);
		button.setMenu(this.#menuRenderer(this.#landing));
	}
}

export class CreateState extends LandingButtonState
{
	#requestBuilder: function;
	#menuRenderer: function

	constructor(request: function, menuRenderer: function)
	{
		super();
		this.#requestBuilder = request;
		this.#menuRenderer = menuRenderer;
	}

	apply(button: Button): void
	{
		button.setText(Loc.getMessage('INTRANET_SETTINGS_BUTTON_CREATE'));
		button.setDropdown(false);
		button.bindEvent('click', (event) => {

			if (button.getState() === ButtonState.WAITING)
			{
				return;
			}
			button.setState(ButtonState.WAITING);

			this.#requestBuilder().then((response) => {
				const landing: LandingOptions = response.data;
				button.setState(null);
				button.unbindEvent('click');
				if (landing.is_public)
				{
					(new ShowState(landing, this.#menuRenderer)).apply(button);
				}
				else
				{
					const state = new EditState(landing);
					state.apply(button);
					state.openNewTab(landing.edit_url);
				}

			}, (response) => {
				button.setState(null);
				ErrorCollection.showSystemError(response.errors[0].message)
			});
		});
	}

}