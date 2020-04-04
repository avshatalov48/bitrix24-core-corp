<?
$MESS["TITLE"] = "Business Processes";
$MESS["CONTENT"] = "<div> Contents:
  <ul>
    <li><a href=\"#bizproc\" title=\"What is a business process?\">Business Processes</a></li>

    <li><a href=\"#tipical\" title=\"What are the most typical business processes?\">Typical Business Processes</a></li>

    <li><a href=\"#work\" title=\"How to create a business process\">Creating a Business Process</a></li>

    <li><a href=\"#perfomance\" title=\"How do I run a business process fulfilled?\">Running a Business Process</a></li>
   </ul>
  <h1><a name=\"bizproc\"></a>Business Processes</h1>

  <p>The notion of <b>business processes</b> refers to an instrument to create, maintain and manage information flows.</p>

  <p><i>A <b>Business Process</b> is the flow of information (or documents) by a defined route or scheme. A business process scheme can specify:</i></p>
  <ul>
    <li>one or more <i>entry and exit points</i> (the process start and end); </li>
    <li>a <i>sequence of actions (steps, stages, functions)</i> which will be executed by the business process algorithm. </li>
   </ul>
  <p>The real world assumes an extensive array of different information flows, the schemes ranging from very simple to very intricate ones. A simple process of publishing a document can contain a variety of possible actions and conditional forks and may require a variety of input data and user notifications.</p>

  <p><b>Business processes</b> enable a common user to create and edit any imaginable variety of combinations of information and action flow schemes. The business process editor has been developed to be as simple as possible, which means that a regular business user, not a programmer, will be able to access a broad range of functions and features. However, the very notion of business processes implies that a more-than-average level of analytical mindset and an in-depth knowledge of what is really going on inside the company must be combined together to gain the full benefit of business processes. </p>
<p>The business process designer in essence is a simple visual <b>drag and drop</b>  block creator. Business process templates are created in a specialized version of the visual editor. A business process author can specify the process steps and their sequence; highlight the details specific to the process using simple visual schemes.</p>
<p>A specific information flow is defined by the business process template, which is comprised of multiple actions. An action can be any event of your choice: creating a document; sending an e-mail message; adding a database record etc. </p>
<p>The system already contains dozens of built-in actions and some typical business processes which can be used to model most common business activities. </p>
<p>There are two most common types of business processes: </p>
 <ul>
    <li>a <b>sequential business process</b> to perform a series of consecutive actions on a document, from a predefined start point to a predefined end point; </li>
    <li>a <b>status-driven business process</b> which does not have a start or end point; the process status is changed at runtime by the workflow. Such business processes can, theoretically, finish at any stage.</li>
   </ul>

  <h2>Sequential Business Process</h2>

  <p>The sequential mode is generally used for processes having a limited and predefined lifecycle. A typical example of this is the creation and approval of a text document. Any sequential process usually includes several actions between the start and end points.</p>
  <p><img border=\"1\" alt=\"Example: simple linear process\" title=\"Example: simple linear process\" src=\"/images/bp/en/2.png\" /></p>

  <h2>Status-driven Business Process</h2>

  <p>A status-driven approach is used when a process does not have a definite time frame and can repeat or return to a given status due to the nature of the process (for example: continuous update of product documentation). A status here is not just a marker to denote the degree of document progress; rather, it describes a real world process cycle. </p>
  <p>The creation of a status-driven process template is not as simple as of a sequential process, but it opens wide possibilities to automate information processing. A typical scheme for such processes consists of several statuses which in turn include actions and status change conditions. </p>
 <img border=\"1\" alt=\"Example: process with statuses\" title=\"Example: process with statuses\" src=\"/images/bp/en/3.png\" />
  <p>Each action in a status is usually a finite sequential process.</p>

  <h1> <a name=\"tipical\"></a>Typical Business Processes</h1>

<p>The system is delivered with multiple ready to use typical business processes. You can tailor them to fit your company?s information flow by using the visual business process designer.</p>
  <h2>\"Simple Approval/Vote\" Sequential Process </h2>

  <p> Recommended when a decision is to be made by a simple majority of votes. </p>

  <h2>\"First Approval\" Sequential Process </h2>

  <p> Recommended when a single approval or response (\"I need a volunteer?\") is sufficient.</p>

  <h2>&quot;Approve Document with States&quot; Status-driven Process </h2>

  <p>Recommended when mutual agreement is required to approve a document. </p>

  <h2> &quot;Two-stage Approval&quot; Sequential Process </h2>

<p> Recommended when a document requires preliminary expert evaluation before being approved. </p>

  <h2> &quot;Expert Opinion&quot; Sequential Process </h2>

  <p> Recommended for situations when a person who is to approve or reject a document needs expert comments on it.</p>

  <h2> &quot;Read Document&quot; Sequential Process </h2>

  <p>Recommended when employees have to familiarize themselves with a document. </p>
  <p>You can view the business processes (standard and user-defined) related to a certain document type by clicking <b>More</b> button and selecting <b>Business processes</b> in the menu: </p>
  <p><img border=\"1\" src=\"/images/bp/en/4.png\" alt=\"View business processes\" title=\"View business processes\" /></p>
<p>This will open the <b>Business Process Templates</b> page in which you can edit existing and create new processes.</p>
  <p><img border=\"1\" src=\"/images/bp/en/11.png\" alt=\"Business processes page\" title=\"Business processes page\" /></p>
  <h1><a name=\"work\"></a>Creating a Business Process</h1>

  <p>To create and edit a business process, you will use a visual business process editor.</p>

  <p>Before you create a business process, you have to select the process type: sequential or status-driven, which will define the layout of the visual editor. The type can be selected using the context toolbar controls of the <b>Business Process Templates</b> form.</p>

  <p>The first step to take when creating a business process is to define the parameters. The process parameters are data that can be accessed in any command, action or condition. Having the parameters defined you can proceed and create the process.</p>
 <img border=\"1\" title=\"Setting process parameters\" alt=\"Setting process parameters\" src=\"/images/bp/en/6.png\" />

  <h2>Creating a Status-Driven Process</h2>

  <p>First of all, create and configure the required statuses using the Add State button. Then, create commands for each status. Each command represents a separate sequential process.</p>
   <img border=\"1\" src=\"/images/bp/en/7.png\" alt=\"Assigning actions in statuses\" title=\"Assigning actions in statuses\" />

  <h2>Creating a Sequential Process </h2>

  <p>When you create a sequential process, the visual editor shows a customizable set of actions.</p>

  <p>The visual editor uses the popular drag-and-drop technique to add actions. Having added a command, configure the command parameters. Each command has a unique parameters dialog.</p>
 <img border=\"1\" title=\"Adding actions in the visual editor\" alt=\"Adding actions in the visual editor\" src=\"/images/bp/en/8.png\" /><br /><br />
  <h1><a name=\"perfomance\"></a>Running a Business Process</h1>
<p>A business process can be run manually or automatically depending on its parameters. The launch mode does not affect the execution. A process can have multiple instances, each running independently. </p>
  <p>To run a business process on a specific document, click the <b>New Business Process </b> command in the document action menu and select the required business process in the list.</p>
 <img border=\"1\" src=\"/images/bp/en/5.png\" alt=\"Launching a business process for a document\" title=\"Launching a business process for a document\" />
<p>After the business process parameters window has opened, specify the parameters and click <b>Start</b>.</p>
 <img border=\"1\" title=\"Setting up a business process\" alt=\"Setting up a business process\" src=\"/images/bp/en/9.png\" />
  <p>If a business process provides notification options, a notification will be sent to an employee when the process arrives at a point where the employee must perform some action. To view and perform the tasks assigned by the running business process, a user can click the <b>Business Processes </b> link in the left menu under the <b>My Workspace</b> group.</p>
</div>";
?>