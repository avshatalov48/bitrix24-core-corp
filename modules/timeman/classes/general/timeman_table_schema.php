<?

class CTimeManTableSchema
{
	public function __construct()
	{
	}

	public static function OnGetTableSchema()
	{
		return array(
			"main" => array(
				"b_user" => array(
					"ID" => array(
						"b_timeman_absence" => "USER_ID"
					),
				),
			),
			"timeman" => array(
				"b_timeman_entries" => array(
					"ID" => array(
						"b_timeman_absence" => "ENTRY_ID"
					),
				),
			),
		);
	}
}

?>
