import { Reflection, Text, Type } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';
import 'crm_activity_planner'; // BX.CrmCallListHelper

/**
 * @memberof BX.Crm.EntityList.Panel
 */
export function createCallListAndShowAlertOnErrors(
	entityTypeId: number,
	selectedIds: number[],
	createActivity: boolean,
	gridId: ?string = null,
	forAll: boolean = false,
): void
{
	void createCallList(entityTypeId, selectedIds, createActivity, gridId, forAll)
		.then(({ errorMessages }) => {
			if (Type.isArrayFilled(errorMessages))
			{
				const error = errorMessages.join('. \n');
				MessageBox.alert(Text.encode(error));
			}
		})
	;
}

/**
 * @memberof BX.Crm.EntityList.Panel
 */
export function createCallList(
	entityTypeId: number,
	selectedIds: number[],
	createActivity: boolean,
	gridId: string = null,
	forAll: boolean = false,
): Promise<{ errorMessages?: string[] }>
{
	return new Promise((resolve) => {
		BX.CrmCallListHelper.createCallList(
			{
				entityType: BX.CrmEntityType.resolveName(entityTypeId),
				entityIds: (forAll ? [] : selectedIds),
				gridId: Type.isNil(gridId) ? undefined : gridId,
				createActivity,
			},
			(response) => {
				if (!Type.isPlainObject(response))
				{
					resolve({});

					return;
				}

				if (!response.SUCCESS && response.ERRORS)
				{
					resolve({
						errorMessages: response.ERRORS,
					});

					return;
				}

				if (!response.SUCCESS || !response.DATA)
				{
					resolve({});

					return;
				}

				const data = response.DATA;
				if (data.RESTRICTION)
				{
					showRestriction(data.RESTRICTION);

					resolve({});

					return;
				}

				const callListId = data.ID;
				if (createActivity && top.BXIM)
				{
					top.BXIM.startCallList(callListId, {});
				}
				else
				{
					(new BX.Crm.Activity.Planner()).showEdit({
						PROVIDER_ID: 'CALL_LIST',
						PROVIDER_TYPE_ID: 'CALL_LIST',
						ASSOCIATED_ENTITY_ID: callListId,
					});
				}

				resolve({});
			},
		);
	});
}

function showRestriction(restriction: string | Object): void
{
	if (Type.isPlainObject(restriction) && Reflection.getClass('B24.licenseInfoPopup'))
	{
		// eslint-disable-next-line no-undef
		B24.licenseInfoPopup.show('ivr-limit-popup', restriction.HEADER, restriction.CONTENT);
	}
	else if (Type.isStringFilled(restriction))
	{
		// eslint-disable-next-line no-eval
		eval(restriction);
	}
}

export function addItemsToCallList(
	entityTypeId: number,
	selectedIds: number[],
	callListId: number,
	context: string,
	gridId: string,
	forAll: boolean,
): void
{
	BX.CrmCallListHelper.addToCallList({
		callListId,
		context,
		entityType: BX.CrmEntityType.resolveName(entityTypeId),
		entityIds: (forAll ? [] : selectedIds),
		gridId,
	});
}
