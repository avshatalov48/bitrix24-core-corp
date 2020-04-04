<?
$MESS["SERVICES_TITLE"] = "Business Processes";
$MESS["SERVICES_INFO"] = "<div> Contents:
  <ul>
    <li><a href='&#35;introduction' title='General description'>Workflow And Business Processes</a></li>

    <li><a href='&#35;workflow' title=\"What is meant by 'workflow'?\">Workflow</a></li>

    <li><a href='&#35;bizproc' title='What is a business process?'>Business Processes</a></li>

    <li><a href='&#35;tipical' title='What typical business processes are included?'>Typical Business Processes</a></li>

    <li><a href='&#35;work' title='How to create a business process'>Creating a Business Process</a></li>

    <li><a href='&#35;perfomance' title='How is a business process fulfilled?'>Running a Business Process</a></li>
   </ul>

  <br />

  <h1><a name='introduction'></a>Workflow And Business Processes</h1>

  <p><b>Bitrix Intranet</b> includes the following two modules that enable
  teamwork in the context of the intranet portal.</p>

  <ul>
    <li> The <b>Workflow</b> module provides step-by-step
    processing of static or dynamic pages and information. This module is
      included in all the product editions.</li>

    <li>The <b>Business Process</b> module supports simple, step-by-step
      information block element processing (a separate linear time-limited
      process) as well as variable or status-driven processes. This
      module is included as <b>Typical Processes</b> module in junior
      editions. The full version, which provides tools to create new processes, is included in <b>Bitrix Intranet:
      Enterprise Edition</b>.</li>
   </ul>

  <p>How to implement workflow these process management tools optimally on the kind of
  documents which are circulating in the company, and should be determined by the person responsible
  for business process integration. Implementation can be done by a portal administrator. </p>

  <h1><a name='workflow'></a>Workflow</h1>

  <p>The <b>Workflow</b> module facilitates linear document processing. This module is appropriate when a document simply needs to go through a series of consecutive steps until it reaches its final state (e.g. publication). </p>

  <p>By default, the <b> Workflow</b> module provides three statuses which are sufficient
  for the simplest workflow scheme. However, real projects in the real world will usually
  require adding custom statuses. Custom statuses can be created by a
  portal administrator or by persons given permissions to create statuses.</p>
 <img border='1' title='Documents and statuses' alt='Documents and statuses' src='#SITE#images/en/bp/1.png' />
  <p>The <b> Workflow</b> module supports the assigning of persons responsible for
  moving a document from one status to another, as well as letting you list persons permitted to edit a
  document while it is in a given status. The module can keep the historical versions of a copies of document
  before changes are saved, depending on the settings. Only a portal
  administrator can change the module settings.</p>

  <h1><a name='bizproc'></a>Business Processes</h1>

  <p>The <b>Business Processes</b> module is an extensive instrument to create,
  perform and manage information flows. This module provides much more
  information management capacity than <b>Workflow</b>. </p>

  <p><i>A <b>Business Process</b> is the flow of information (or documents) through a
  defined route or scheme. A business process scheme can specify:</i></p>

  <ul>
    <li><i>one or several entry and exit points;</i></li>
    <li><i>sequence of actions (steps, stages, functions), to be fulfilled in an assigned order or under certain conditions.</i></li>
   </ul>

  <p>The real world will require many different information flows, from the very simple to the very intricate.
  The simple process of publishing a document can contain a
  variety of possible actions and conditional forks and may require a variety of input
  data and user notifications.</p>

  <p>The <b>Business Processes</b> module provides user a interface to create and
  edit business processes. This editor is as simple as it can be, but no simpler, which means that a regular business user will be able to access a broad range of functionality. However, the very notion of business
  processes implies that a high level of analytical prowess and in-depth knowledge of what is really going on inside the company must be combined to gain the full benefit of this feature.  </p>

  <p>The business process designer in
  essence is a simple visual <b>drag and drop</b>  block creator. Business
  process templates are created in a specialized version of the visual editor. A
  business process author can specify steps in the process and their sequence, as well as highlight the details specific to the process using
  simple visual schemes.</p>

  <p>The specific information flow is defined by the business process
  template, which is comprised of a set of actions. An action can be any event
  of your choice: creating a creation; sending an e-mail message; making a database record
  etc. </p>

  <p>The product distribution package contains dozens of built-in actions and
  some typical business processes which can be used to model most common
  activities. </p>

  <p>The two general business process types exist, and the <b>Business Processes
  </b>module supports both of them: </p>

  <ul>
    <li><b>sequential business process</b> - to perform a series of consecutive actions on a document,
      from a predefined start point to a predefined end point; </li>

    <li><b>state-driven business process</b> does not have a start or end
      point; the workflow changes the process status. Such business processes
      can, theoretically, finish at any stage.</li>
   </ul>

  <h2> <b>Sequential Business Process</b></h2>

  <p>The sequential mode is generally used for processes having a limited and predefined
  lifecycle. A typical example of this is the creation and approval of a text
  document. Any sequential process usually includes several actions between the
  start and end points.</p>

  <p><img border='1' alt='Example: simple linear process' title='Example: simple linear process' src='#SITE#images/en/bp/2.png' /></p>

  <h2>State-driven Business Process</h2>

  <p>A status-driven approach is used when a process does not have a definite time
  frame and can repeat or return to a given status due to nature of
  the process (for example: the continuous update of product
  documentation). A status here is not just a marker concerning
  degree of document readiness; rather, it describes a real
  world process cycle. </p>

  <p>The creation of a status-driven process template is not as simple as for
  a sequential process, but it opens wide possibilities to automate information
  processing. A typical scheme for such processes consists of several statuses
  which in turn include actions and status change conditions. </p>
 <img border='1' alt='Example: process with statuses' title='Example: process with statuses' src='#SITE#images/en/bp/3.png' />
  <p>Each action in a status is usually a finite sequential process.</p>

  <h1> <a name='tipical'></a>Typical Business Processes</h1>

  <p>The most common business processes are included in junior editions (<b>Bitrix Intranet: Office Edition</b> and <b>Bitrix Intranet: Extranet Edition</b>) as read-only constructions. You can
  configure them to handle the required documents, but cannot change the logic.
  The <b>Bitrix Intranet:
      Enterprise Edition</b> includes a visual editor in which you
  can edit standard templates and create your own business processes. </p>

  <h2>'Simple Approval/Vote' Sequential Process </h2>

  <p> Recommended when a decision is to be made by a simple majority of votes. </p>

  <h2>'First Approval' Sequential Process </h2>

  <p> Recommended when a single approval or response ('I need a volunteer...') is sufficient.</p>

  <h2>&quot;Approve Document with States&quot; State-driven Process </h2>

  <p>Recommended when mutual agreement is required to approve a document. </p>

  <h2> &quot;Two-stage Approval&quot; Sequential Process </h2>

  <p> Recommended when a document requires preliminary expert evaluation before being approved. </p>

  <h2> &quot;Expert Opinion&quot; Sequential Process </h2>

  <p> Recommended for situations when a person who is to approve or reject a document needs expert comments on it.</p>

  <h2> &quot;Read Document&quot; Sequential Process </h2>

  <p>Recommended when employees are to familiarize themselves with a document. </p>
  You can view the business processes (standard and user-defined) related to a
  certain document type by clicking <img src='#SITE#images/en/bp/4.png' alt='Business processes button' title='Business processes button' />
  ,which will open the <b>Business Process Templates</b> page where you can edit
  existing and create new processes.
  <p>
 <img border='1' src='#SITE#images/en/bp/11.png' alt='Business processes page' title='Business processes page' />
  <h1><a name='work'></a>Creating a Business Process</h1>

  <p>To create and edit business processes, you will need the special visual
  editor included only in the <b>Bitrix Intranet:
      Enterprise Edition</b> only.</p>

  <p>Before you create a business process, you have to select the process type:
  sequential or status-driven, which will define the layout of the visual
  editor. The type can be selected using the context toolbar controls of the <b>Business
  Process Templates</b> form.</p>

  <p>The first step in creating a business process is to define the parameters. The
  process parameters are data that can be accessed in any command, action or
  condition. Having the parameters defined you can proceed and create the
  process.</p>
 <img border='1' title='Setting process parameters' alt='Setting process parameters' src='#SITE#images/en/bp/6.png' />

  <h2>Creating a Status-Driven Process</h2>

  <p>First of all, create and configure the required statuses using the Add State button. Then, create
  commands for each status. Each command represents a separate sequential
  process.</p>
   <img border='1' src='#SITE#images/en/bp/7.png' alt='Assigning actions in statuses' title='Assigning actions in statuses' />

  <h2>Creating a Sequential Process </h2>

  <p>When you create a sequential process, the visual editor shows a
  customizable set of actions. </p>

  <p>The visual editor uses the popular drag-and-drop technique to add
  actions. Having added a command, configure its parameters. Each command has a
  unique parameters dialog.</p>
 <img border='1' title='Adding actions in the visual editor' alt='Adding actions in the visual editor' src='#SITE#images/en/bp/8.png' />
  <h1><a name='perfomance'></a>Running a Business Process</h1>

  <p>Acreated (or existing) business process can be run manually or
  automatically depending on its parameters. This choice
  does not affect execution. A process can have multiple instances, each
  running independently. </p>

  <p>To run a business process on a specific document, select the <b>New
  Business Process </b>command in the document action menu and select the
  required business process in the list.</p>
 <img border='1' src='#SITE#images/en/bp/5.png' alt='Launching a business process for a document' title='Launching a business process for a document' />
  <p>When a business process parameters window opens, specify the parameters and
  click <b>Start</b>.</p>
 <img border='1' title='Setting up a business process' alt='Setting up a business process' src='#SITE#images/en/bp/9.png' />
  <p>If a business process provides notification options, a notification will be sent to an employee when the process arrives at a point where the given employee must perform some action.   To view and perform the assigned tasks, the person can
  open the <b>Business Processes </b>tab on their personal page. </p>
 </div>
";
?>