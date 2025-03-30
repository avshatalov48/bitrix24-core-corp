import { Api } from 'sign.v2.api';
import { Dom, Loc, Tag, Text } from 'main.core';
import { Select } from 'ui.select';
import { Layout } from 'ui.sidepanel.layout';
import type { HcmLinkMultipleVacancyEmployee } from 'sign.v2.api';

import "./vacancy-choose.css";

type VacancyChooserOptions = {
	api: Api,
	documentGroupUids: Array<string>,
	companyTitle: string,
	employees: Map<number, HcmLinkMultipleVacancyEmployee>
};

export class HcmLinkVacancyChooser
{
	#api: Api;
	#documentGroupUids: Array<string> = [];
	#employees: Map<number, HcmLinkMultipleVacancyEmployee>;
	#companyTitle: string;

	#content: HTMLElement | null = null;
	#overlay: HTMLElement | null = null;

	#selectByUserId: Map<number, Select> = new Map();

	constructor(options: VacancyChooserOptions)
	{
		this.#api = options.api;
		this.#documentGroupUids = options.documentGroupUids;
		this.#employees = options.employees;
		this.#companyTitle = options.companyTitle;
	}

	static openSlider(
		options: VacancyChooserOptions,
		sliderOptions: { onCloseHandler: () => void },
	): void
	{
		BX.SidePanel.Instance.open('sign:hcmlink-vacancy-chooser', {
			width: 800,
			loader: 'default-loader',
			cacheable: false,
			contentCallback: () => {
				return top.BX.Runtime.loadExtension('sign.v2.b2e.hcm-link-employee-selector').then((exports) => {
					return (new exports.HcmLinkVacancyChooser(options)).getLayout();
				});
			},
			events: {
				onClose: sliderOptions?.onCloseHandler ?? (() => {}),
			},
		});
	}

	getLayout(): Layout
	{
		const context = this;
		return Layout.createContent({
			extensions: ['ui.forms', 'sign.v2.b2e.hcm-link-employee-selector'],
			title: this.#companyTitle,
			toolbar(): []
			{
				return [];
			},
			content(): string
			{
				return context.#getContent();
			},
			buttons({cancelButton, SaveButton})
			{
				const saveButton = new SaveButton({
					onclick: () => {
						saveButton.setWaiting(true);
						Dom.show(context.#overlay);

						context.saveEmployees()
							.then((): void => {
								BX.SidePanel.Instance.close();
							})
							.catch(() => {})
							.finally((): void => {
								saveButton.setWaiting(false);
								Dom.hide(context.#overlay);
							})
						;
					},
				});

				return [
					saveButton,
					cancelButton,
				];
			},
		});
	}

	async saveEmployees(): Promise<void>
	{
		if (this.#employees.size === 0)
		{
			return;
		}

		const employeeCollection = Array
			.from(this.#selectByUserId)
			.map(([key, value]) => ({ userId: key, employeeId: value.getValue() }))
		;

		for (const documentUid of this.#documentGroupUids)
		{
			await this.#api.saveEmployeesForSignProcess({
				documentUid: documentUid,
				selectedEmployeeCollection: employeeCollection,
			});
		}
	}

	#getContent(): HTMLElement
	{
		if (!this.#content)
		{
			const { root, selectContainer, overlay } = Tag.render`
				<div class="sign-b2e-hcm-link-vacancy-choose">
					<div class="sign-b2e-hcm-link-vacancy-choose-overlay" ref="overlay"></div>
					<div class="sign-b2e-hcm-link-vacancy-choose-title">
						${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_EMPLOYEE_SELECTOR_TITLE')}
					</div>
					<div class="sign-b2e-hcm-link-vacancy-choose-select-container" ref="selectContainer"></div>
				</div>
			`;
			this.#overlay = overlay;
			Dom.hide(overlay);

			const employees = Array
				.from(this.#employees.values())
				.sort((a: HcmLinkMultipleVacancyEmployee, b: HcmLinkMultipleVacancyEmployee) => a.order - b.order)
			;

			employees.forEach((item: HcmLinkMultipleVacancyEmployee): void => {
				const firstPositionValue = item.positions[0].employeeId;
				const select = new Select({
					options: item.positions.map((positionItem) => {
						return {
							label: positionItem.position,
							value: positionItem.employeeId
						};
					}),
					value: firstPositionValue,
				});

				this.#selectByUserId.set(item.userId, select);

				const { rowElement, vacancySelector }  = Tag.render`
					<div class="sign-b2e-hcm-link-vacancy-choose-select-row" ref="rowElement">
						<div class="sign-b2e-hcm-link-vacancy-choose-select-row__avavar">
							<img src="${item.avatarLink}">
						</div>
						<div class="sign-b2e-hcm-link-vacancy-choose-select-row__name">
							${Text.encode(item.fullName)}
						</div>
						<div class="sign-b2e-hcm-link-vacancy-choose-select-row__select" ref="vacancySelector"></div>
					</div>
				`;

				select.renderTo(vacancySelector);
				Dom.append(rowElement, selectContainer);
			});

			this.#content = root;
		}

		return this.#content;
	}
}