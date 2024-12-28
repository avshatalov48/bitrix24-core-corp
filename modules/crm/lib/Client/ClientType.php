<?php

namespace Bitrix\Crm\Client;

enum ClientType
{
	// New Client
	case New;

	// Is not a new client, has not been approached before and has no sales
	case Existing;

	// A lead with a contact or a contact|company with a deal at the final failed stage
	case PreviouslyContacted;

	// A lead with a contact or a contact|company with a deal at the final successfully stage
	case WithSale;

	// Client type could not be recognised
	case Unrecognised;
}
