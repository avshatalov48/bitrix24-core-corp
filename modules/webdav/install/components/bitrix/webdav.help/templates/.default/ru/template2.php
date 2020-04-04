<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script type="text/javascript" src="/bitrix/js/webdav/imgshw.js"></script>
С библиотекой документов можно работать двумя способами: через браузер (Internet Explorer, Opera, FireFox и т.д.) или через WebDAV-клиенты ОС (для ОС Windows: компонент веб-папок, подключение диска). <br><br>
<ul>
	<li><b><a href="#iewebfolder">Работа с библиотекой документов в веб-браузере</a></b></li>
	<li><b><a href="#ostable">Таблица сравнений клиентских WebDAV-приложений</a></b></li>
	<li><b><a href="#oswindows">Подключение библиотеки документов в ОС Windows</a></b></li>
	<ul>
		<li><a href="#oswindowsnoties">Ограничения ОС Windows</a></li>
		<li><a href="#oswindowsreg">Разрешение авторизации без https</a></li>
		<li><a href="#oswindowswebclient">Перезапуск службы Веб-клиент</a></li>
		<li><a href="#oswindowsfolders">Подключение через компонент Веб-папок</a></li>
		<li><a href="#oswindowsmapdrive">Подключение сетевого диска</a></li>

	</ul>
	<li><b><a href="#osmacos">Подключение к библиотеке в Mac OS, Mac OS X</a></b></li>
	<li><b><a href="#maxfilesize">Увеличение максимального размера загружаемых файлов</a></b></li>
</ul>


<h2><a name="browser"></a>Работа с библиотекой документов в веб-браузере</h2>
<h4><a name="upload"></a>Загрузка документов</h4>
<p>Для выполнения загрузки документов перейдите в папку, в которую необходимо загрузить документы. Нажмите кнопку <b>Загрузить</b>, расположенную на Контекстной панели:</p>
<p><img src="<?=$templateFolder.'/images/load_contex_panel.png'?>" border="0"/></p>
<p>Откроется форма для загрузки файлов, которая имеет несколько представлений:</p>
<ul>
<li><b>одиночная загрузка</b> - позволяет легко и быстро загрузить один документ;</li>
<li><b>обычный</b> - позволяет выполнить пофайловую загрузку документов из различных директорий (кнопка <b>Добавить файлы</b>) или загрузить документы какой-нибудь папки (кнопка <b>Добавить папку</b>);</li>
<li><b>классический</b> - служит для загрузки документов из определенной директории;</li>
<li><b>простой</b> - используется для индивидуальной загрузки каждого документа.</li>
</ul>

<p>Выберите подходящий вам вид формы и укажите документы, которые должны быть загружены.</p>
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/load_form.png\',661,575,\'Форма для загрузки документов\');'?>">
<img src="<?=$templateFolder.'/images/load_form_sm.png'?>" style="CURSOR: pointer" width="300" height="261" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p>

<p>Нажмите кнопку <b>Загрузить</b>.</p>

<br/>
<h4><a name="bizproc"></a>Запуск бизнес-процесса</h4>

<p>В некоторых случаях требуется выполнение некоторых операций с загруженным документом. Например, утвердить или согласовать документ. Для этого используются бизнес-процессы</p>

<p>Для запуска бизнес-процесса в контекстном меню пункт <b>Новый бизнес-процесс</b>:</p>
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/new_bizproc.png\',622,459,\'Запуск бизнес-процесса\');'?>">
<img src="<?=$templateFolder.'/images/new_bizproc_sm.png'?>" style="CURSOR: pointer" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p>
<? if (!empty($arResult['BPHELP'])) { ?>
<p><b>Примечание</b>: подробную информацию о бизнес-процессах смотрите на странице <a href="<?=$arResult['BPHELP']?>" target="_blank">Бизнес-процессы</a>.</p>
<? } ?>
<p>Для перехода к управлению шаблонами бизнес-процессов нажмите на кнопку <b>Бизнес-процессы</b>, расположенную на контекстной панели:</p> 

<br/>
<h4><a name="delete"></a>Изменение, удаление документов</h4>
<p>Управление документами осуществляется либо с помощью контекстного меню: 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/delete_file.png\',399,516,\'Изменение, удаление документов\');'?>">
<img src="<?=$templateFolder.'/images/delete_file_sm.png'?>" style="CURSOR: pointer" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p>
либо с помощью панели групповых действий, расположенных под списком документов. 
<br/><br/>

<br>
<h2><a name="ostable"></a>Таблица сравнений клиентских WebDAV-приложений</h2>

<p>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				При использовании WebDAV-клиента для управления библиотекой в случае <b>документооборота</b> или <b>бизнес-процессов</b>, есть некоторые ограничения: <br/><br/>
				1. нельзя запустить бизнес-процесс для документа; <br/>
				2. нельзя загружать, изменять документы, если на автозапуске находятся бизнес-процессы с обязательными параметрами автозапуска без значений по умолчанию; <br/>
				3. проследить историю документа. 
			</td>
		</tr>
	</table>
</div>
</p>


<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main data-table">
	<thead>
		<tr class="wd-row">
			<th class="wd-cell">WebDAV-клиент</th>
			<th class="wd-cell">Авторизация<br />Windows (IWA)</th>
			<th class="wd-cell">Дайджест<br /> авторизация<br />(Digest)</th>
			<th class="wd-cell">Авторизация<br />базовая (Basic)</th>
			<th class="wd-cell">SSL</th>
			<th class="wd-cell">Порт</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><a href="#oswindowsfolders"><u>Веб&ndash;папка</u></a>, Windows 7</td>
			<td>+</td>
			<td>+</td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Веб&ndash;папка</u></a>, Vista SP1</td>
			<td>+</td>
			<td>+</td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Веб&ndash;папка</u></a>, Windows XP</td>
			<td>+</td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Веб&ndash;папка</u></a>, Windows 2003/2000
				<sup><small><a title='По умолчанию не установлено в операционной системе' href='#osnote3'>[3]</a></small></sup></td>
			<td>+</td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsfolders"><u>Веб&ndash;папка</u></a>, Windows Server 2008
				<sup><small><a title='По умолчанию не установлено в операционной системе' href='#osnote3'>[3]</a></small></sup></td>
			<td>+</td>
			<td>+</td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Сетевой диск</u></a>, Windows 7</td>
			<td>+</td>
			<td>+</td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Сетевой диск</u></a>, Vista SP1</td>
			<td>+</td>
			<td>+</td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Сетевой диск</u></a>, Windows XP</td>
			<td>+</td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>80</td>
		</tr>
		<tr>
			<td><a href="#oswindowsmapdrive"><u>Сетевой диск</u></a>, Windows 2003/2000</td>
			<td>+</td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>&ndash;&nbsp;<sup><small><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></small></sup></td>
			<td>80</td>
		</tr>
		<tr>
			<td>MS Office 2007/2003/XP</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td>MS Office 2010</td>
			<td>+</td>
			<td>+</td>
			<td>+&nbsp;<sup><small><a title='Необходимо внести измения в реестр' href='#osnote2'>[2]</a></small></sup></td>
			<td>+</td>
			<td>все</td>
		</tr>
		<tr>
			<td><a href="#osmacos"><u>MAC OS X</u></a></td>
			<td>&ndash;&nbsp;<sup><a title='Не поддерживается операционной системой' href='#osnote1'>[1]</a></sup></td>
			<td>+</td>
			<td>+</td>
			<td>+</td>
			<td>все</td>
		</tr>
	</tbody>
</table>
<br />
<p>
	<b>Примечания:</b>
<ol>
<li><a name='osnote1'></a>Не поддерживается операционной системой.</li>
<li><a name='osnote2'></a>Для включения поддержки в операционной системе, необходимо <a href='#oswindowsreg'>внести изменнеия в реестр</a>.</li>
<li><a name='osnote3'></a>По умолчанию служба <i>Веб-клиент</i> (<i>WebClient</i>) не установлена в операционной системе. 
	Необходимо установить <i>Дополнения</i> (<i>Features</i>) след. образом: 
	<ul>
		<li><i>Start -> Administrative Tools -> Server Manager -> Features</i></li>
		<li>Справа сверху нажать на <i>Add Features</i></li>
		<li>Выбрать <i>Desktop Experience</i>, установить</li>
	</ul>
</li>
</ol>
</p>
<br>
<h2><a name="oswindows"></a>Подключение библиотеки документов в ОС Windows</h2>
<h4><a name="oswindowsnoties"></a>Ограничения ОС Windows</h4>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				<p>В <b>Windows 7</b> компонент веб-папок не работает по защищенному протоколу. Для работы с библиотекой из Windows 7 вам необходимо работать по протоколу HTTP. </p>
				<p>В <b>Windows XP</b> всегда необходимо указывать номер порта, даже в том случае, если используется 80 порт (http://servername:80/).</p>
				<p><b>Прежде, чем подключать библиотеку документов, убедитесь, что запущена служба Веб-клиент (WebClient).</b></p>
			</td>
		</tr>
	</table>
</div>

<h4><a name="oswindowsreg">Разрешение авторизации без https</a></h4>
<p><b>Во-первых</b>, необходимо изменить параметр <b>Basic authentication</b> в реестре ОС Windows: </p>
<ul>
  <li><a href="/bitrix/webdav/xp.reg">изменить реестр</a> для <b>Windows XP, Windows 2003 Server</b></li> 
  <li><a href="/bitrix/webdav/vista.reg">изменить реестр</a> для <b>Windows 7, Vista, Windows 2008 Server</b></li> 
  <li><a href="/bitrix/webdav/office14.reg">изменить реестр</a> для <b>Microsoft Office 2010</b></li> 
</ul>
<p>Нажмите кнопку <b>Запустить</b> в окне загрузки файла, в диалоге <b>Редактора реестра</b> с предупреждением о недостоверности источника нажмите <b>Да</b>:</p> 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/vista_reg_2.png\',572,212,\'Диалог ОС\');'?>">
<img src="<?=$templateFolder.'/images/vista_reg_2_sm.png'?>" style="CURSOR: pointer" width="250" height="93" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p>
<p>Если вы используете браузер, который не позволяет запускать .reg файлы, то необходимо скачать файл и запустить или внести изменения в реестр вручную при помощи <b>Редактора реестра</b>.</p>
<p><b>Изменение параметров с помощью Редактора реестра</b></p>
<p>Выполните команду: <b>Пуск &gt; Выполнить</b>. Откроется окно <b>Запуск программы</b>:</p>

<p><img src="<?=$templateFolder.'/images/regedit.png'?>" width="347" height="179" border="0"/></a></p>

<p>В поле <b>Открыть</b> введите <b>regedit</b> и нажмите кнопку <b>ОК</b>.</p>
<p>Для <b>Windows XP, Windows 2003 Server</b> необходимо изменить значение параметра на:</p>
<p></p>
  <table cellspacing="0" cellpadding="0" border="1"> 
    <tbody> 
      <tr><td width="638" valign="top"> 
          <p>[HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters] &quot;UseBasicAuth&quot;=dword:00000001</p>
         </td></tr>
     </tbody>
   </table>
<p></p>
<p>Для <b>Windows 7, Vista, Windows 2008 Server</b> нужно изменить значение параметра или создать запись в реестре:</p>
	<table cellspacing="0" cellpadding="0" border="1"> 
		<tbody> 
			<tr><td width="638" valign="top"> 
				<p>[HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\WebClient\Parameters] 
				<br />
				&quot;BasicAuthLevel&quot;=dword:00000002</p>
			</td></tr>
		</tbody>
	</table>

<p><b>Во-вторых</b>, необходимо перезапустить службу <a href="#oswindowswebclient"><b>Веб-клиент (WebClient)</b></a>.</p>
<h4><a name="oswindowswebclient"></a><b>Перезапуск службы Веб-клиент</b></h4>
<p>Для перезапуска перейдите: <b>Пуск &gt; Панель управления &gt; Администрирование &gt; Службы</b>. Откроется диалог <b>Службы</b>: 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/web_client.png\',638,450,\'Службы\');'?>">
<img src="<?=$templateFolder.'/images/web_client_sm.png'?>" style="CURSOR: pointer" width="300" height="212" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p>  
<p>Найдите в общем списке служб строку <b>Веб-клиент (WebClient)</b>, перезапустите (запустите). Чтобы служба запускалась в дальнейшем при старте ОС, необходимо в свойствах службы установить значение параметра <b>Тип запуска</b> в <b>Авто</b>:
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/web_client_prop.png\',410,461,\'Свойство службы Веб-клиент\');'?>">
<img src="<?=$templateFolder.'/images/web_client_prop_sm.png'?>" style="CURSOR: pointer" width="205" height="230" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p></li>
<p>Можно приступать непосредственно к подключению папки.</p>

<h4><a name="oswindowsfolders">Подключение через компонент Веб-папок (web-folders)</a></h4>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				В <b>Windows 7</b> не работает подключение по защищенному протоколу HTTPS/SSL.<br>
				В <b>Windows 2003 Server</b> компонент веб-папок не установлен. Необходимо установить компонент веб-папок ( <a href="http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64" target="_blank">перейти на сайт Microsoft</a> ).
			</td>
		</tr>
	</table>
</div>
<p>Прежде, чем подключать библиотеку документов, убедитесь, что <a href="#oswindowsreg">внесены изменения в реестр</a> и <a href="#oswindowswebclient">запущена служба Веб-клиент (WebClient).</a></p>
<p>Для подключения к библиотеке документов данным способом необходим компонент веб-папок. Желательно установить последнюю версию программного обеспечения для веб-папок ( <a href="http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64" target="_blank">перейти на сайт Microsoft</a> ) на клиентский компьютер. </p>
<p>Нажмите на кнопку <b>Подключить</b>, расположенную на панели инструментов. </p>
<p><img border="0" src="<?=$templateFolder.'/images/load_contex_panel.png'?>" /></p>
<p>В открывшемся диалоге вы увидете доступные способы подключения для вашего браузера и ОС.</p>
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/connection.png\',719,490,\'Подключение\');'?>">
<img src="<?=$templateFolder.'/images/connection_sm.png'?>" style="CURSOR: pointer" alt="Нажмите на рисунок, чтобы увеличить"  border="0"/></a></p>  
<p>Если вы не используете <b>Internet Explorer</b> или если библиотека не была открыта как веб-папка при нажати на кнопку в диалоговом окне, то выполните следующие действия:</p>
<ul>
<li>Запустите <b>Проводник</b></li>
<li>Выберите в меню пункт <b>Сервис &gt; Подключить сетевой диск</b></li>
<li>С помощью ссылки <b>Подписаться на хранилище в Интернете или подключиться к сетевому серверу</b> запустите <b>Мастер добавления в сетевое окружение</b>:</p> 
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/network_add_1.png\',447,322,\'Подключение сетевого диска\');'?>">
<img width="250" height="180" border="0" src="<?=$templateFolder.'/images/network_add_1_sm.png'?>" style="cursor: pointer;" alt="Нажмите на рисунок, чтобы увеличить" /></a></li>
<li>Нажмите кнопку <b>Далее</b>, откроется второе окно <b>Мастера</b></li>
<li>В этом окне сделайте активным позицию <b>Выберите другое сетевое размещение</b> и нажмите кнопку <b>Далее</b>. Откроется следующий шаг <b>Мастера</b>:
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/network_add_4.png\',563,459,\'Добавление в сетевое окружение: Шаг 3\');'?>">
<img width="250" height="204" border="0" src="<?=$templateFolder.'/images/network_add_4_sm.png'?>" style="cursor: pointer;" alt="Нажмите на рисунок, чтобы увеличить" /></a></li>
<li>В поле <b>Сетевой адрес или адрес в Интернете</b> введите URL подключаемой папки вида: <i>http://<ваш_сервер>/docs/shared/</i>.</li>
<li>Нажмите кнопку <b>Далее</b>. Если появится окно для авторизации, то введите данные для авторизации на сервере.</li>
</ul>

<p>Для последующего открытия папки выполните команду: <b>Пуск > Сетевое окружение > Имя папки</b>.</p>
 
<br />
<br />

<h4><a name="oswindowsmapdrive"></a>Подключение сетевого диска</h4>
<div style="border:1px solid #ffc34f; background: #fffdbe;padding:1em;">
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td style="border-right:1px solid #FFDD9D; padding-right:1em;">
				<img src="/bitrix/components/bitrix/webdav.help/templates/.default/images/help.png" width="20" height="18" border="0"/>
			</td>
			<td style="padding-left:1em;">
				<b>Внимание!</b> В ОС <b>Windows XP and Windows Server 2003</b> не работает подключение по защищенному протоколу HTTPS/SSL.
			</td>
		</tr>
	</table>
</div>
<p>Для подключения библиотеки как сетевого диска в <b>Windows 7</b> по защищенному протоколу <b>HTTPS/SSL</b>: выполните команду <b>Пуск &gt; Выполнить &gt; cmd</b>. В командной строке введите:<br>
<table cellspacing="0" cellpadding="0" border="1"> 
	<tbody> 
		<tr><td width="638" valign="top"> 
			<p>net use z: https://&lt;ваш_сервер&gt;/docs/shared/ /user:&lt;userlogin&gt; *</p>
		</td></tr>
	</tbody>
</table>
<br>

<p>Для подключения библиотеки как сетевого диска при помощи <b>проводника</b>:
<ul>
<li>Запустите <b>Проводник.</b></li> 
<li>Выберите в меню пункт <i>Сервис > Подключить сетевой диск</i>. Откроется диалог подключения сетевого диска: 
<br><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/network_storage.png\',628,465,\'Подключение сетевого диска\');'?>">
<img width="250" height="185" border="0" src="<?=$templateFolder.'/images/network_storage_sm.png'?>" style="cursor: pointer;" alt="Нажмите на рисунок, чтобы увеличить" /></a></li>
<li>В поле <b>Диск</b> назначьте букву для подключаемой папки.</li>
<li>В поле <b>Папка</b> введите путь до библиотеки: <i>http://&lt;ваш_сервер&gt;/docs/shared/</i>. Если необходимо, чтобы папка подключалась для просмотра при каждом запуске системы, установите флажок <b>Восстанавливать при входе в систему</b>.</li>
<li>Нажмите <b>Готово</b>. Если откроется диалог операционной системы для авторизации, введите данные для авторизации на сервере.</li>
</ul>
</p>
<p>Последующие открытия папки можно осуществлять через <b>Проводник Windows</b>, где папка отображается в виде отдельного диска, либо через любой файловый менеджер.</p>

<h2><a name="osmacos"></a>Подключение к библиотеке в Mac OS, Mac OS X</h2>

<p>Для подключения:</p>

<ul>
<li>Откройте <i>Finder Go->Connect to Server command</i>.</li>
<li>Введите адрес к библиотеке в поле <b>Server Address</b>:</p>
<p><a href="<? echo 'javascript:ShowImg(\''.$templateFolder.'/images/macos.png\',465,550,\'Mac OS X\');'?>">
<img width="235" height="278" border="0" src="<?=$templateFolder.'/images/macos_sm.png'?>" style="cursor: pointer;" alt="Нажмите на рисунок, чтобы увеличить" /></a></li>
</ul>
<br />

<h2><a name="maxfilesize"></a>Увеличение максимального размера загружаемых файлов</h2>

<p>Максимальный размер загружаемого файла - это минимальные значения переменных PHP (<b>upload_max_filesize</b> и <b>post_max_size</b>) и параметры настройки компонентов.</p>
<p>Если вы хотите увеличить квоту, которая превышает рекомендуемые значения, то внесите следующие изменения <b>php.ini</b>:</p>

<table cellspacing="0" cellpadding="0" border="1"> 
  <tbody> 
      <tr><td width="638" valign="top">
	  <p>upload_max_filesize = желаемое_значение;
	  <br/>post_max_size = превышает_размер_upload_max_filesize;</p>
      </td></tr>
  </tbody>
</table>

<p>Если вы арендуете площадку (виртуальный хостинг), то внесите изменения в файл <b>.htaccess</b>:</p>

<table cellspacing="0" cellpadding="0" border="1"> 
  <tbody> 
      <tr><td width="638" valign="top">
	  <p>php_value upload_max_filesize желаемое_значение<br/>
	  php_value post_max_size превышает_размер_upload_max_filesize</p>
      </td></tr>
  </tbody>
</table>

<p>Возможно, вам придется обратиться к хостеру с просьбой увеличить минимальные значения переменных PHP (<b>upload_max_filesize</b> и <b>post_max_size</b>).</p>
<p>После того, как будут увеличены квоты PHP, следует внести изменения в настройки компонентов.</p>
