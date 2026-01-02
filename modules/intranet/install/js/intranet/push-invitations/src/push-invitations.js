import 'ui.notification';
import { Dom, Type } from 'main.core';

export class PushInvitations extends BX.UI.Notification.Balloon
{
	invitationButton: ?Element = null;
	constructor(...args) {
		super(...args);

		this.doNotAdjustPosition = true;
		this.invitationButton = this.getData().target;

		addEventListener('resize', (event) => {
			for (const key of Object.keys(BX.UI.Notification.Center.balloons))
			{
				const ballon = BX.UI.Notification.Center.balloons[key];
				if (ballon.category === 'PushInvitations')
				{
					ballon.getContainer().style.left = `${this.invitationButton.offsetLeft - this.invitationButton.scrollWidth - 100}px`;
					ballon.getContainer().style.top = `${this.invitationButton.offsetTop + this.invitationButton.scrollHeight + 25}px`;
				}
			}
		});
	}

	getContainer(): HTMLElement
	{
		if (this.container !== null)
		{
			return this.container;
		}

		this.container = Dom.create('div', {
			props: {
				className: 'ui-notification-balloon ui-notification-balloon-animate intranet-notification-balloon-push-invitation-content',
			},
			children: [
				this.render(),
			],
		});

		return this.container;
	}

	adjustPosition(): void
	{
		if (this.invitationButton !== null)
		{
			const invitationButtons = document.querySelectorAll('[data-id="invitationButton"]');
			if (invitationButtons.length > 0)
			{
				this.invitationButton = invitationButtons[0];
			}
		}

		this.getContainer().style.left = `${this.invitationButton.offsetLeft - this.invitationButton.scrollWidth - 100}px`;
		this.getContainer().style.top = `${this.invitationButton.offsetTop + this.invitationButton.scrollHeight + 25}px`;
	}

	render(): HTMLElement
	{
		const ballon = this;
		const width = this.getWidth();
		const title = this.getData().title;
		const description = this.getData().description;

		const actions = this.getActions().map(function(action) {
			return action.getContainer();
		});

		return Dom.create('div', {
			props: {
				className: 'intranet-notification-balloon-container',
			},
			children: [
				Dom.create('div', {
					props: {
						className: 'ui-notification-balloon-content',
					},
					style: {
						width: Type.isNumber(width) ? `${width}px` : width,
					},
					children: [
						this.getPointerContainer(),
						this.getMessageContainer(title, description),
						Dom.create('div', {
							props: {
								className: 'ui-notification-balloon-close-btn',
							},
							events: {
								click() {
									ballon.close();
								},
							},
						}),
					],
				}),
				Dom.create('div', {
					props: {
						className: 'ui-notification-balloon-actions',
					},
					children: actions,
				}),
			],
		});
	}

	getPointerContainer(): HTMLElement
	{
		return Dom.create('div', {
			props: {
				className: 'intranet-notification-balloon-circle-pointer',
			},
			children: [
				Dom.create('div', {
					props: {
						className: 'intranet-notification-balloon-circle',
					},
				}),
				Dom.create('div', {
					props: {
						className: 'intranet-notification-balloon-line',
					},
				}),
			],
		});
	}

	getMessageContainer(title, description): HTMLElement
	{
		return Dom.create('div', {
			props: {
				className: 'ui-notification-balloon-message',
			},
			children: [
				Dom.create('div', {
					props: {
						className: 'intranet-notification-balloon-message-title',
					},
					html: title,
				}),
				Dom.create('div', {
					props: {
						className: 'intranet-notification-balloon-message-description',
					},
					html: description,
				}),
			],
		});
	}
}
