export default class EntityCounterType
{
	// type ID
	static UNDEFINED = 0;
	static IDLE = 1;
	static PENDING = 2;
	static OVERDUE = 4;
	static CURRENT = 6; // PENDING|OVERDUE
	static ALL_DEADLINE_BASED = 7;  //IDLE|PENDING|OVERDUE
	static INCOMING_CHANNEL = 8;
	static ALL = 15;  //IDLE|PENDING|OVERDUE|INCOMING_CHANNEL

	// type name
	static IDLE_NAME  = 'IDLE';
	static PENDING_NAME = 'PENDING';
	static OVERDUE_NAME = 'OVERDUE';
	static CURRENT_NAME = 'CURRENT';
	static INCOMING_CHANNEL_NAME = 'INCOMINGCHANNEL';
	static ALL_DEADLINE_BASED_NAME = 'ALLDEADLINEBASED';
	static ALL_NAME = 'ALL';
}
