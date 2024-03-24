<?php
$MESS["DAV_HELP_NAME"] = "DAV Module";
$MESS["DAV_HELP_TEXT"] = "The DAV module makes it possible to synchronize calendars and contacts on the website with any
software and devices supporting CardDAV and CardDAV protocols. iPhone and iPad support
these protocols. Software support is provided by Mozilla Sunbird, eM Client and some other software applications.<br>
<ul>
	<li><b><a href=\"#carddav\">Connect using CardDav</a></b></li>
	<li><b><a href=\"#caldav\">Connect using CalDav</a></b>
	<ul>
		<li><a href=\"#caldavipad\">Connect </a><a href=\"#caldavipad\">iPhone/iPad</a></li>
		<li><a href=\"#carddavsunbird\">Connect Mozilla Sunbird</a></li>
	</ul>
	</li>
</ul>

<br>

<h3><a name=\"carddav\"></a>Connect using CardDav</h3>

To set up your Apple device to support CardDAV:
<ol>
<li>Click <b>Settings</b> and select <b>Passwords & Accounts</b>.</li>
<li>Click <b>Add Account</b>.</li>
<li>Select <b>Other</b> &gt; <b>Add CardDAV Account</b>.</li>
<li>Specify this website address as server (#SERVER#). Use your login and password.</li>
<li>If you are using two-step authentication, use the password specified in the
  user profile: <b>Application passwords</b> - <b>Contacts</b>.</li>
<li>To specify the port number, save the account, then open it for editing again
  and proceed to the <b>More</b> area.</li>
</ol>

All your contacts will appear in the Contacts app.<br>
To enable or disable synchronization for selected contacts, open your profile
and select <b>Synchronize </b>in the menu.

<br><br>

<h3><a name=\"caldav\"></a>Connect using CalDAV</h3>

<h4><a name=\"caldavipad\"></a>Connect iPhone/iPad</h4>

To set up your Apple device to support CalDAV:
<ol>
<li>On your Apple device, open the menu <b>Settings</b> &gt; <b>Passwords & Accounts</b>.</li>
<li>Select <b>Add Account</b> under the account list.</li>
<li>Select CalDAV as account type (<b>Other</b> &gt; <b>CalDAV Account</b>).</li>
<li>Specify this website address as server (#SERVER#). Use your login and password.</li>
<li>If you are using two-step authentication, use the password specified in the user profile: <b>Application passwords</b>
  &gt; <b>Calendar</b>.</li>
<li>To specify the port number, save the account, then open it for editing again and proceed to the <b>More</b>  area.</li>
</ol>

Your calendars will appear in the Calendar app.&nbsp;<br>
To connect other users' calendars, use these links in <b>More</b> &gt; <b>Account
URL</b>:<br>
<i>#SERVER#/bitrix/groupdav.php/site ID/user name/calendar/</i><br>
and<br>
<i>#SERVER#/bitrix/groupdav.php/</i><i>site ID</i><i>/group-group ID/calendar/</i>

<br><br>

<h4><a name=\"carddavsunbird\"></a>Connect Mozilla Sunbird</h4>

To configure Mozilla Sunbird for use with CalDAV:
<ol>
<li>Run Sunbird and select <b>File &gt; New Calendar</b>.</li>
<li>Select <b>On the Network</b> and click <b>Next</b>.</li>
<li>Select <b>CalDAV</b> format.</li>
<li>In the <b>Location</b> field, enter:<br>
  <i>#SERVER#/bitrix/groupdav.php/site ID/user name/calendar/calendar ID/</i><br>
  or<br>
  <i>#SERVER#/bitrix/groupdav.php/</i><i>site ID</i><i>/group-</i><i>group ID</i><i>/calendar/calendar
  ID/</i><br>
and click <b>Next</b>.</li>
<li>Give your calendar a name and select a colour for it.</li>
<li>Enter your user name and password.</li>
<li>If you are using two-step authentication, use the password specified in the user profile: <b>Application passwords</b>
  &gt; <b>Calendar</b>.</li>
</ol>

Your calendars are now available in Mozilla Sunbird.";
$MESS["DAV_HELP_TEXT_1"] = "The DAV module makes it possible to synchronize calendars and contacts on the website with any
software and devices supporting CardDAV and CardDAV protocols. iPhone and iPad support
these protocols. Software support is provided by Mozilla Sunbird, eM Client and some other software applications.<br>
<ul>
	<li><b><a href=\"#carddav\">Connect using CardDav</a></b></li>
	<li><b><a href=\"#caldav\">Connect using CalDav</a></b>
	<ul>
		<li><a href=\"#caldavipad\">Connect </a><a href=\"#caldavipad\">iPhone/iPad</a></li>
		<li><a href=\"#carddavsunbird\">Connect Mozilla Sunbird</a></li>
	</ul>
	</li>
</ul>

<br>

<h3><a name=\"carddav\"></a>Connect using CardDav</h3>

To set up your Apple device to support CardDAV:
<ol>
<li>On your Apple device, open the menu <b>Settings</b> &gt; <b>Contacts</b> &gt; <b>Accounts</b>.</li>
<li>Click <b>Add Account</b>.</li>
<li>Select <b>Other</b> &gt; <b>Add CardDAV Account</b>.</li>
<li>Specify this website address as server (#SERVER#). Use your login and password.</li>
<li>If you are using two-step authentication, use the password specified in the
  user profile: <b>Application passwords</b> - <b>Contacts</b>.</li>
<li>To specify the port number, save the account, then open it for editing again
  and proceed to the <b>More</b> area.</li>
</ol>

All your contacts will appear in the Contacts app.<br>
To enable or disable synchronization for selected contacts, open your profile
and select <b>Synchronize </b>in the menu.

<br><br>

<h3><a name=\"caldav\"></a>Connect using CalDAV</h3>

<h4><a name=\"caldavipad\"></a>Connect iPhone/iPad</h4>

To set up your Apple device to support CalDAV:
<ol>
<li>On your Apple device, open the menu <b>Settings</b> &gt; <b>Calendar</b> &gt; <b>Accounts</b>.</li>
<li>Select <b>Add Account</b> under the account list.</li>
<li>Select CalDAV as account type (<b>Other</b> &gt; <b>CalDAV Account</b>).</li>
<li>Specify this website address as server (#SERVER#). Use your login and password.</li>
<li>If you are using two-step authentication, use the password specified in the user profile: <b>Application passwords</b>
  &gt; <b>Calendar</b>.</li>
<li>To specify the port number, save the account, then open it for editing again and proceed to the <b>More</b>  area.</li>
</ol>

Your calendars will appear in the Calendar app.&nbsp;<br>
To connect other users' calendars, use these links in <b>More</b> &gt; <b>Account
URL</b>:<br>
<i>#SERVER#/bitrix/groupdav.php/site ID/user name/calendar/</i><br>
and<br>
<i>#SERVER#/bitrix/groupdav.php/</i><i>site ID</i><i>/group-group ID/calendar/</i>

<br><br>

<h4><a name=\"carddavsunbird\"></a>Connect Mozilla Sunbird</h4>

To configure Mozilla Sunbird for use with CalDAV:
<ol>
<li>Run Sunbird and select <b>File &gt; New Calendar</b>.</li>
<li>Select <b>On the Network</b> and click <b>Next</b>.</li>
<li>Select <b>CalDAV</b> format.</li>
<li>In the <b>Location</b> field, enter:<br>
  <i>#SERVER#/bitrix/groupdav.php/site ID/user name/calendar/calendar ID/</i><br>
  or<br>
  <i>#SERVER#/bitrix/groupdav.php/</i><i>site ID</i><i>/group-</i><i>group ID</i><i>/calendar/calendar
  ID/</i><br>
and click <b>Next</b>.</li>
<li>Give your calendar a name and select a colour for it.</li>
<li>Enter your user name and password.</li>
<li>If you are using two-step authentication, use the password specified in the user profile: <b>Application passwords</b>
  &gt; <b>Calendar</b>.</li>
</ol>

Your calendars are now available in Mozilla Sunbird.";
