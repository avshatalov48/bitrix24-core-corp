export const ExpirationLevel = {
	normal: 0,
	soonExpired: 1,
	almostExpired: 2,
	expired: 4,
	blocked: 8
};

export function getExpirationLevel(daysLeft)
{
	if (daysLeft <= (-14))
	{
		return ExpirationLevel.soonExpired
			| ExpirationLevel.almostExpired
			| ExpirationLevel.expired
			| ExpirationLevel.blocked;
	}
	if (daysLeft <= 0)
	{
		return ExpirationLevel.soonExpired
			| ExpirationLevel.almostExpired
			| ExpirationLevel.expired;
	}
	if (daysLeft <= 14)
	{
		return ExpirationLevel.soonExpired
			| ExpirationLevel.almostExpired;
	}
	if (daysLeft <= 30)
	{
		return ExpirationLevel.soonExpired;
	}
	return ExpirationLevel.normal;
}