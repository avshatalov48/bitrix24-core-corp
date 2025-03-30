import { Dom, Event, Loc, Type, Uri } from 'main.core';
import { Analytics } from 'sign.v2.analytics';
import { Api } from 'sign.v2.api';
import { MessageBox } from 'ui.dialogs.messagebox';
import { Switcher, SwitcherSize } from 'ui.switcher';

export class Templates
{
	#analytics = new Analytics();
	#api = new Api();

	#changeVisibility(templateId: number, visibility: string): Promise<Object>
	{
		const api = this.#api;

		return api.changeTemplateVisibility(templateId, visibility);
	}

	async deleteTemplate(templateId: number): Promise<void>
	{
		const messageContent = document.createElement('div');
		messageContent.innerHTML = Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_MESSAGE');
		Dom.style(messageContent, 'margin-top', '5%');
		Dom.style(messageContent, 'color', '#535c69');

		MessageBox.show({
			title: Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_TITLE'),
			message: messageContent.outerHTML,
			modal: true,
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_YES'),
					color: BX.UI.Button.Color.PRIMARY,
					onclick: async (button) => {
						try
						{
							const api = this.#api;
							await api.deleteTemplate(templateId);

							window.top.BX.UI.Notification.Center.notify({
								content: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_SUCCESS'),
							});
						}
						catch
						{
							window.top.BX.UI.Notification.Center.notify({
								content: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_FAIL'),
							});
						}

						await this.reload();
						button.getContext().close();
					},
				}),
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_NO'),
					color: BX.UI.Button.Color.LINK,
					onclick: (button) => {
						button.getContext().close();
					},
				}),
			],
		});
	}

	async renderSwitcher(
		templateId: number,
		isChecked: boolean,
		isDisabled: boolean,
		hasEditTemplateAccess?: boolean,
	): Promise<void>
	{
		const switcherNode = document.getElementById(`switcher_b2e_template_grid_${templateId}`);
		const switcher = new Switcher({
			node: switcherNode,
			checked: isChecked,
			size: SwitcherSize.medium,
			disabled: isDisabled,
			handlers: {
				toggled: async () => {
					switcher.setLoading(true);
					const checked = switcher.isChecked();
					const visibility = checked ? 'visible' : 'invisible';
					try
					{
						await this.#changeVisibility(templateId, visibility);
					}
					catch
					{
						switcher.setLoading(false);
						switcher.check(!checked, false);
					}
					finally
					{
						this.#sendActionStateAnalytics(checked, templateId);
						switcher.setLoading(false);
					}
				},
			},
		});

		if (!isDisabled)
		{
			return;
		}
		const title = hasEditTemplateAccess
			? Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT')
			: Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT_NO_ACCESS');

		switcherNode.setAttribute('title', title);
	}

	#sendActionStateAnalytics(checked: boolean, templateId: number): void
	{
		this.#analytics.send({
			category: 'templates',
			event: 'turn_on_off_template',
			type: 'manual',
			c_section: 'sign',
			c_sub_section: 'templates',
			c_element: checked ? 'on' : 'off',
			p5: `templateid_${templateId}`,
		});
	}

	reload(): Promise<void>
	{
		Event.ready(() => {
			const grid = BX.Main.gridManager.getById('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_GRID')?.instance;
			if (Type.isObject(grid))
			{
				grid.reload();
			}
		});
	}

	reloadAfterSliderClose(addNewTemplateLink: string): void
	{
		BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', async (event) => {
			const baseUrl = '/sign/b2e/doc/0/';
			const closedSliderUrl = event.getData()[0].getSlider().getUrl();
			const uri = new Uri(closedSliderUrl);
			const path = uri.getPath();

			if (
				closedSliderUrl === addNewTemplateLink
				|| path.startsWith(baseUrl)
				|| closedSliderUrl === 'sign-settings-template-created'
			)
			{
				await this.reload();
			}
		});
	}

	async exportBlank(templateId: number): Promise<void>
	{
		try
		{
			const { json, filename } = await this.#api.template.exportBlank(templateId);
			const mimeType = 'application/json';
			this.#downloadStringLikeFile(json, filename, mimeType);

			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_EXPORT_BLANK_SUCCESS'),
			});
		}
		catch (e)
		{
			console.error(e);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_EXPORT_BLANK_FAILURE'),
			});
		}
	}

	#downloadStringLikeFile(data: string, filename: string, mimeType: string): void
	{
		const blob = new Blob([data], { type: mimeType });
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		Dom.style(a, 'display', 'none');
		a.href = url;
		a.download = filename;
		Dom.append(a, document.body);
		a.click();
		window.URL.revokeObjectURL(url);
		Dom.remove(a);
	}

	async copyTemplate(templateId: number): Promise<void>
	{
		try
		{
			await this.#api.copyTemplate(templateId);
			await this.reload();
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_SUCCESS'),
			});
		}
		catch (error)
		{
			console.error('Error copying template:', error);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_FAIL'),
			});
		}
	}
}
