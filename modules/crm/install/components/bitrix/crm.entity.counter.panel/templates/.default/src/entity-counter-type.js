export default class EntityCounterType
{
	// type ID
	static UNDEFINED = 0;
	static IDLE = 1;
	static PENDING = 2;
	static OVERDUE = 4;
	static CURRENT = 6; // PENDING|OVERDUE
	static ALL = 7;     // IDLE|PENDING|OVERDUE

	// type name
	static IDLE_NAME  = 'IDLE';
	static PENDING_NAME = 'PENDING';
	static OVERDUE_NAME = 'OVERDUE';
	static CURRENT_NAME = 'CURRENT';
	static ALL_NAME = 'ALL';
}
