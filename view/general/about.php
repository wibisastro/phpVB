<div class="str">
<h4>Features</h4>
<p>
We have been trying to make programming style ideal to standard.<br />
<UL>
	<li>Valid XHTML 1.0</li>
	<li>Valid CSS 2</li>
	<li>MVC approach</li>
	<li>register_globals = off ready</li>
	<li>Layout by CSS</li>
	<li>Basic HTML Class</li>
	<li>Formage Class</li>
	<li>Error message system</li>
</UL>
</p>

<h4>Layout Components</h4>
<p>
Each component has its own method to control them.<br />
<UL>
	<li>Three section: top-section, content and footer</li>
	<li>The content section consists of two fixed column: left-column and content</li>
	<li>Navigation bar on the left column</li>
	<li>Main menu on the top section</li>
	<li>Page title on the top section</li>
</UL>
</p>

<h4>Architecture</h4>
<p>
We are using our own architectural pattern similar to 
<a href="http://en.wikipedia.org/wiki/Model-view-controller" target="_blank">Model-View-Controller</a> (MVC). 
MVC is the most used architecture in web applications.
</p><p>
In phpFramewerk we encapsulate data and functions (model) in classes.
Data is stored in variables on the config file or 
in the future use of phpFramewerk it will be stored in MySQL and the classes files are stored in model folder.
The presentation (view) is in CSS, javascript and HTML files. 
</p>
<p>
phpFramewerk is designed to keep the code in controller files as simple as possible so that the control flows between user interface, 
data handling and the display responds to the user will remain ease.
</p>

<h4>Control Flows</h4> 
In phpFramewerk we handle control flows as follows:
<p>
<ol>
<li>The user triggered events with the user interface such as clicks or presses a button.</li> 
<li>A controller handles the request from the user interface by evaluate <br />
	the $_GET[cmd] or $_POST[cmd] variable.</li>
<li>The controller processes the event in a way appropriate to the user's action 
   (this is where Controller accesses the Model such as Add,Read,Edit and Delete). </li>
<li>The controller accesses the view to render an appropriate response 
	(this is where the data from the model displayed within the HTML). </li>
<li>The user interface waits for further events triggered by the users. 
</ol>
</p>


<h4>Directory Structure</h4>
After you extract the package in a folder <B>.</B> then you will find the structure as follows:
<p>
We put database configuration files in folder <B>./conf</B><br />
We put all view files in folder <B>./view</B><br />
We put all classes in folder <B>./model</B><br />
We put all images in folder <B>./web/images</B><br />
We put all JavaScript in folder <B>./web/js</B><br />
We put all CSS in folder <B>./web/css</B><br />
And all controller files in root <B>./</B><br />
You can move the conf, view and model folder to non-accessible 
folder on your server to reduce security risk.
</p>

<h4>Configuration</h4>

<p>When you open the config.php file the configuration should explain it self.</p>

<h4>Application Abstraction</h4>
We combine OOP and procedural progamming style.
So the application will behave based on following aspects. 
<p>
<ol>
	<li><B>Structure</B><p>
	The application structured as conf-object-control-view.
	When a controller file is executed the script will read the config first (located in folder conf).
	On the conf all configuration will sets and construct 2 new objects $doc-> and $frm->.
	</p>
	</li>
	<li><B>Objects</B><p>
	All objects were put on classes located in folder Model.
	We currently use these 2 object (document and forms) as basic methods for all operation in the application.
	We will add more object in the future to provide more method as needed.
	</p>
	</li>
	<li><B>Control</B><p>
	In a controller file there are 3 steps.
		<UL>
		<LI>Initialization (init)<br />
		In init step you can construct additional object if needed 
		or you can set any other parameter (e.g pagetitle or sidebar navigation).
		</li>
		<LI>Processes (proc)<br />
		In proc step the script will do the processes by evaluate the variable <br />
		$_GET[cmd] or $_POST[cmd]. This variable represent typically user actions. 
		</li>
		<LI>View (view)<br />
		After the control step take all necessary action to provide appropriate response parameter then the view step do the display task based on parameter given by the control step.
		</li>
		</UL>	
	</li>
	</p>
	<p>This controller file responsible for user interactions. 
	A controller file is accessible by the browser.
	You create a page by create a controller file.
	In common use one controller handle one page but you can make one controller file handle many pages.
	</p>
	<li><B>View</B>
	<p>
	All view files are located in folder view except the CSS file is located in the root.
	The basic file is body.php, this file is the template of the application. 
	You need to know how the CSS script works to improve the design.
	</p><p>
	Do not change the body.php file, 
	changes to this file will affect the layout of the web 
	application unless you know what you're doing.
	</p>
	<p>
	Actually the view folder consists of not only presentation files but also some content files which is not stored on database nor config files.
	The files for greetings or response could be located in here.
	</p>
	</li>
</ol>
</p>

<h4>Tutorial</h4>
We will provide manual and tutorials in our Wiki:
<a href="http://doc.gov2.cs.ui.ac.id" target="_blank">http://doc.gov2.cs.ui.ac.id</a>
</div>