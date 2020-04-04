<?
$APPLICATION->IncludeFile("support/ticket_edit/default.php", Array(
	"ID"						=>	$_REQUEST["ID"],			// ID ���������
	"TICKET_LIST_URL"			=>	"ticket_list.php",			// �������� ������ ���������
	"TICKET_MESSAGE_EDIT_URL"	=>	"ticket_message_edit.php"	// �������� �������������� ���������
	)
);
?>