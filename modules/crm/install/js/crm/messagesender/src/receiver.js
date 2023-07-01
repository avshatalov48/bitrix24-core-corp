import { ItemIdentifier } from 'crm.data-structures';
import { ensureIsItemIdentifier, ensureIsValidMultifieldValue, ensureIsValidSourceData } from './internal/validation';

export type MultifieldValue = {
	id: ?number,
	typeId: string,
	valueType: string,
	value: string,
	valueFormatted: ?string,
};

export type SourceData = {
	title: string,
};

export class Receiver
{
	#rootSource: ItemIdentifier;
	#addressSource: ItemIdentifier;
	#addressSourceData: ?SourceData = null;
	#address: MultifieldValue;

	constructor(
		rootSource: ItemIdentifier,
		addressSource: ItemIdentifier,
		address: MultifieldValue,
		addressSourceData: ?SourceData = null,
	)
	{
		ensureIsItemIdentifier(rootSource);
		this.#rootSource = rootSource;

		ensureIsItemIdentifier(addressSource);
		this.#addressSource = addressSource;

		ensureIsValidMultifieldValue(address);
		this.#address = Object.freeze({
			id: address.id,
			typeId: address.typeId,
			valueType: address.valueType,
			value: address.value,
			valueFormatted: address.valueFormatted,
		});

		if (addressSourceData)
		{
			ensureIsValidSourceData(addressSourceData);
			this.#addressSourceData = Object.freeze({
				title: addressSourceData.title,
			});
		}
	}

	static fromJSON(data: Object): ?Receiver
	{
		const rootSource = ItemIdentifier.fromJSON(data?.rootSource);
		if (!rootSource)
		{
			return null;
		}

		const addressSource = ItemIdentifier.fromJSON(data?.addressSource);
		if (!addressSource)
		{
			return null;
		}

		try
		{
			return new Receiver(rootSource, addressSource, data?.address, data?.addressSourceData);
		}
		catch (e)
		{
			return null;
		}
	}

	get rootSource(): ItemIdentifier
	{
		return this.#rootSource;
	}

	get addressSource(): ItemIdentifier
	{
		return this.#addressSource;
	}

	get addressSourceData(): ?SourceData
	{
		return this.#addressSourceData;
	}

	get address(): MultifieldValue
	{
		return this.#address;
	}

	isEqualTo(another: Receiver): boolean
	{
		if (!(another instanceof Receiver))
		{
			return false;
		}

		// noinspection OverlyComplexBooleanExpressionJS
		return (
			this.rootSource.isEqualTo(another.rootSource)
			&& this.addressSource.isEqualTo(another.addressSource)
			&& String(this.address.typeId) === String(another.address.typeId)
			&& String(this.address.valueType) === String(another.address.valueType)
			&& String(this.address.value) === String(another.address.value)
		);
	}
}
