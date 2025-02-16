import { Event, Runtime, Type, Dom } from 'main.core';
import { PULL } from 'pull.client';

type MyDocumentsGridOptions = {
	needActionCounterId: string,
	counterPullEventName: string,
};

export class MyDocuments
{
	#options: MyDocumentsGridOptions;

	constructor(options: MyDocumentsGridOptions)
	{
		this.#options = options;
	}

	openSignSliderByGridId(gridId: string): void
	{
		Event.ready(async () => {
			const gridContainer = document.querySelector(gridId);
			if (!gridContainer)
			{
				return;
			}

			Event.bind(gridContainer, 'click', async (event) => {
				let target = event.target;
				if (Dom.hasClass(target, 'ui-btn-text'))
				{
					target = target.parentNode;
				}

				if (!Dom.hasClass(target, 'ui-btn') || !target.dataset.memberId)
				{
					return;
				}

				if (Dom.hasClass(target, 'sign-action-button'))
				{
					Dom.addClass(target, 'ui-btn-wait');

					const memberId = Number(target.dataset.memberId);
					Runtime.loadExtension('sign.v2.b2e.sign-link')
						.then((exports) => {
							return new exports.SignLink({ memberId }).openSlider({
								target,
								events: {
									onClose: async () => {
										await BX.ajax.runAction(
											'sign.api_v1.B2e.Document.Member.callStatus',
											{ json: { memberId } },
										);

										if (Type.isNil(PULL))
										{
											this.#reload();
										}
									},
								},
							});
						})
						.catch((error) => {
							console.error(error);
						})
						.finally(() => {
							Dom.removeClass(target, 'ui-btn-wait');
						});
					event.preventDefault();
				}
			});
		});
	}

	#reload(): void
	{
		Event.ready(async () => {
			const grid = BX.Main.gridManager.getById('SIGN_B2E_MY_DOCUMENTS_GRID')?.instance;
			if (Type.isObject(grid))
			{
				grid.reload();
			}
		});
	}

	subscribeOnPullEvents(): void
	{
		Event.ready(() => {
			if (Type.isNil(PULL))
			{
				return;
			}

			PULL.subscribe({
				moduleId: 'sign',
				command: 'updateMyDocumentGrid',
				callback: () => {
					this.#reload();
				},
			});

			PULL.subscribe({
				moduleId: 'sign',
				command: this.#options?.counterPullEventName,
				callback: (params) => {
					if (!Type.isNumber(params?.needActionCount))
					{
						return;
					}

					if (!Type.isStringFilled(this.#options?.needActionCounterId))
					{
						return;
					}

					Event.EventEmitter.emit('BX.Sign.DocumentCounter.Item:updateCounter', {
						id: this.#options.needActionCounterId,
						count: params.needActionCount,
					});
				},
			});
		});
	}
}
