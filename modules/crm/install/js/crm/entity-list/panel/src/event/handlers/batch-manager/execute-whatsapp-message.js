import type { SettingsCollection } from 'main.core.collections';
import { type ProgressBarRepository } from 'crm.autorun';
import { BaseHandler } from '../base-handler';
import { Runtime } from 'main.core';

export class ExecuteWhatsappMessage extends BaseHandler
{
	#entityTypeId: number;

	#categoryId: ?number;

	#isWhatsAppEdnaEnabled: boolean;

	#ednaManageUrl: string;

	#progressBarRepo: ProgressBarRepository;

	constructor({ entityTypeId, categoryId, isWhatsAppEdnaEnabled, ednaManageUrl })
	{
		super();
		this.#entityTypeId = entityTypeId;
		this.#categoryId = categoryId;
		this.#isWhatsAppEdnaEnabled = isWhatsAppEdnaEnabled;
		this.#ednaManageUrl = ednaManageUrl;
	}

	injectDependencies(
		progressBarRepo: ProgressBarRepository,
		extensionSettings: SettingsCollection,
	): void
	{
		this.#progressBarRepo = progressBarRepo;
	}

	static getEventName(): string
	{
		return 'BatchManager:whatsappMessage';
	}

	async execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean)
	{
		if (!this.#isWhatsAppEdnaEnabled)
		{
			this.#showConnectEdnaSlider();

			return;
		}

		if (!this.#isEntityTypeSupported(this.#entityTypeId))
		{
			console.error(`entityTypeId ${this.#entityTypeId} is not supported for whatsapp message`);

			return;
		}

		try
		{
			const exports = await Runtime.loadExtension('crm.group-actions.messages');
			const { Messages } = exports;

			const options = {
				gridId: grid.getId(),
				entityTypeId: this.#entityTypeId,
				categoryId: this.#categoryId,
				selectedIds,
				forAll,
			};

			const whatsAppMessage = Messages.getInstance(this.#progressBarRepo, options);
			await whatsAppMessage.execute();
		}
		catch (e)
		{
			console.error(e);
		}
	}

	#isEntityTypeSupported(entityTypeId: number): boolean
	{
		const supportTypes = [
			BX.CrmEntityType.enumeration.contact,
			BX.CrmEntityType.enumeration.company,
		];

		return supportTypes.includes(entityTypeId);
	}

	#showConnectEdnaSlider(): void
	{
		BX.SidePanel.Instance.open(
			this.#ednaManageUrl,
			{
				width: 700,
				events: {
					onClose(e) {
						BX.SidePanel.Instance.postMessage(
							e.getSlider(),
							'ContactCenter:reloadItem',
							{
								moduleId: 'imopenlines',
								itemCode: 'whatsappbyedna',
							},
						);
					},
				},
			},
		);
	}
}
