import { Event, Type } from 'main.core';

export class MyDocumentsGrid
{
	openSignSliderByGridId(gridId: string): Promise<void>
	{
		Event.ready(() => {
			const gridContainer = document.querySelector(gridId);
			if (!gridContainer)
			{
				return;
			}

			gridContainer.addEventListener('click', async (event) => {
				let target = event.target;
				if (BX.Dom.hasClass(target, 'ui-btn-text'))
				{
					target = target.parentNode;
				}

				if (!target.classList.contains('ui-btn') || !target.dataset.memberId)
				{
					return;
				}

				if (target.classList.contains('sign-action-button'))
				{
					BX.Dom.addClass(target, 'ui-btn-wait');

					const memberId = Number(target.dataset.memberId);
					BX.Runtime.loadExtension('sign.v2.b2e.sign-link')
						.then((exports) => {
							return new exports.SignLink({ memberId }).openSlider({
								target,
								events: {
									onClose: () => {
										BX.ajax.runAction('sign.api_v1.B2e.Document.Member.callStatus', { json: { memberId } })
											.then(() => {
												this.#reload();
											});
									},
								},
							});
						})
						.finally(() => {
							BX.Dom.removeClass(target, 'ui-btn-wait');
						});
					event.preventDefault();
				}
			});
		});
	}

	#reload(): Promise<void>
	{
		Event.ready(() => {
			const grid = BX.Main.gridManager.getById('SIGN_B2E_MY_DOCUMENTS_GRID')?.instance;
			if (Type.isObject(grid))
			{
				grid.reload();
			}
		});
	}
}
