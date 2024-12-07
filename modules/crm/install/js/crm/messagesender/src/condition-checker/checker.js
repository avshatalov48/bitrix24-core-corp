import { Type } from 'main.core';
import type { SenderType } from './common/consent-approver';
import { ConsentApprover } from './common/consent-approver';
import { Factory } from './factory';

type OpenLineItems = {
	[key: string]: OpenLineItem;
}

type OpenLineItem = {
	name: string;
	selected: boolean;
	url: string;
}

export class ConditionChecker
{
	#openLineItems: ?OpenLineItems = null;
	#senderType: SenderType;
	#serviceId: string;
	#entityTypeId: number;

	/**
	 * @param {SenderType} senderType
	 * @param {OpenLineItems | null} openLineItems
	 * @param {string | null} serviceId
	 * @param {number | null} entityTypeId
	 * @returns {Promise<number|null>}
	 */
	static async checkAndGetLine({
		senderType,
		openLineItems = null,
		serviceId = null,
		entityTypeId = null,
	}): Promise<number | null>
	{
		const instance = new ConditionChecker({
			senderType,
		});

		if (Type.isObjectLike(openLineItems))
		{
			instance.setOpenLineItems(openLineItems);
		}

		if (Type.isStringFilled(serviceId))
		{
			instance.setServiceId(serviceId);
		}
		else
		{
			throw new TypeError('ServiceId is required');
		}

		if (BX.CrmEntityType.isDefined(entityTypeId))
		{
			instance.setEntityTypeId(entityTypeId);
		}
		else
		{
			throw new TypeError('EntityTypeId is not specified or incorrect');
		}

		return instance.check();
	}

	static async checkIsApproved({ senderType }): Promise<boolean>
	{
		const instance = new ConditionChecker({
			senderType,
		});

		return instance.checkApproveConsent();
	}

	/**
	 * @param {string} openLineCode
	 * @param {string} senderType
	 */
	constructor({ senderType })
	{
		this.#senderType = senderType;
	}

	setOpenLineItems(items: OpenLineItems): ConditionChecker
	{
		this.#openLineItems = items;

		return this;
	}

	setServiceId(serviceId: ?string): ConditionChecker
	{
		this.#serviceId = serviceId;

		return this;
	}

	setEntityTypeId(entityTypeId: ?number): ConditionChecker
	{
		this.#entityTypeId = entityTypeId;

		return this;
	}

	async check(): Promise<number | null>
	{
		const scenario = Factory.getScenarioInstance(this.#serviceId, {
			senderType: this.#senderType,
			openLineItems: this.#openLineItems,
			entityTypeId: this.#entityTypeId,
		});

		return scenario.checkAndGetLineId();
	}

	async checkApproveConsent(): Promise<boolean | null>
	{
		const isApproved = await (new ConsentApprover(this.#senderType)).checkAndApprove();
		if (isApproved)
		{
			return Promise.resolve(true);
		}

		return Promise.resolve(null);
	}
}
