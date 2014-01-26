<div style='font-family: Arial, Helvetica, sans-serif'>
<h1>DISCLAIMER</h1>
<p>This code is provided AS-IS. It is the result of work done exploring the Google Glass platform and is inspired by code provided along with the Google Glass Mirror API documentation available @ https://developers.google.com/glass/develop/mirror/quickstart/php. For a Quick Start guide to using the Google Glass Mirror API, visit https://developers.google.com/glass/develop/mirror/quickstart/.</p>
<h1>REQUIREMENTS</h1>
<ul>
  <li>CakePHP framework, available at <a href="http://cakephp.org">http://cakephp.org</a></li>
  <li>Google PHP Client API </li>
  <li>
    A working knowledge of coding with CakePHP. The CakePHP documentation can be found here: <a href="http://book.cakephp.org/2.0/en/index.html" target="_blank">http://book.cakephp.org/2.0/en/index.html</a></li>
    <li>A Google Console Project with OAuth credentials. To setup your google console project, visit <a href="http://cloud.google.com" target="_blank">http://cloud.google.com</a>.</li>
    <li>The Google_Oauth2Service class, available in the <a href="https://code.google.com/p/google-api-php-client/" target="_blank">Google API Client Library for PHP</a> library </li>
    <li>The Google_Client class, also available in the <a href="https://code.google.com/p/google-api-php-client/" target="_blank">Google API Client Library for PHP</a> library</li>
    <li>The Google_MirrorService class, also available in the <a href="https://code.google.com/p/google-api-php-client/" target="_blank">Google API Client Library for PHP</a> library</li>
  </li>
</ul>
<h1>INSTALLATION</h1>
<p>Copy the component to your <span class="code">/&lt;Application Root&gt;/app/Controller/Component/</span> directory, or any other directory bootstrapped to search for components.</p>
<p>Update the paths in the component file to those for each of the required classes. BE SURE TO MAKE THE PATH TO THE SQLITE DATBASE WRITABLE BY YOUR WEB USER ACCOUNT.</p>
<p>Replace the paths to both the Google_Oauth2Service and Google_Client class files with the correct path on your server - e.g. <span class='code'>/var/www/libraries/google/google-api-client/src/contrib/&lt;ClassFile&gt;</span> assuming that is where you have the Google API library installed on your server</p>
<p>That's it! You're ready to start using the component within your CakePHP controllers. </p>
<h1>USING THE COMPONENT</h1>
<p>Include the component name in your $components array </p>
<ul>
  <blockquote class='code'> public $components = array('GoogleOauth2'); </blockquote>
</ul>
<p>Next, use the <a href='#connect'><span class='fxn'>connect()</span></a> method to initialize the component when you are ready to attempt authentication against Google ID. See the method reference <a href='#connect'>below</a> for accepted arguments.</p>
<h2>LOADING COMPONENTS ON THE FLY</h2>
<p>You can load the component on the fly in your CakePHP application using the following syntax:</p>
<blockquote class="code">$this-&gt;GoogleAuth = $this-&gt;Components-&gt;load('GoogleGlass'); </blockquote>
<h1>FUNCTIONS</h1>
<blockquote>
  <h3 id='connect'><span class='fxn'>connect</span>($controller, $config, $scopes, $api_mode)</h3>
  <p> This method is most likey
    be well used within your project. It initializes your authentication attempt againt the user's Google ID, and accepts the following arguments: </p>
  <blockquote>
    <p><span class='var'><strong>controller</strong></span>: Use the $this variable to pass your controller into the component for controller-level callbacks</p>
    <p><strong>config</strong>: This is an associative array with the following parameters: </p>
    <blockquote>
      <p><span class='var'><strong>client_id</strong></span>: Your Google API client ID</p>
      <p><span class='var'><strong>client_secret</strong></span>: Your Google API secret</p>
      <p><span class='var'><strong>redirect_url</strong></span>: Your success redirect url once the authentication action is completed. This url MUST be included in the list of accepted callbacks in your google console</p>
    </blockquote>
    <p><span class='var'><strong>api_mode</strong></span>: This is a boolean variable. If set to true, the component will return an associative array with a <em>success</em> index that indicates whether the auth attempt was successful or not. This will be useful if you are authenticating via an API and would like to parse the associate array to a JSON string (for instance) instead of the auto-redirect action.</p>
  </blockquote>
  <h3 id='IsReady'><span class='fxn'>isReady</span>()</h3>
  <p> This returns true or false after a <a href='#connect'><span class='fxn'>connect</span>()</a> call for a success or failure to authenticate. </p>
  <h3 id='getAuthUrl'><span class='fxn'>getAuthUrl</span>()</h3>
  <p> Returns the authentication Url against Google's OAuth API. Is useful if you are handling redirection manually. In this case, the <em>api_mode<em> variable of your <a href='#connect'><span class='fxn'>connect</span>()</a> function can
    come in handy to turn off the auto-redirect when authentication is needed.</p>
  <h3 id='getUser'><span class='fxn'>getUser</span>()</h3>
  <p> Will return an associate array of the session user data. </p>
  <h3 id='getUserId'><span class='fxn'>getUserId</span>()</h3>
  <p> Will return the Google User ID from the session user array. </p>
  <h3 id='cleanUser'><span class='fxn'>cleanUser</span>()</h3>
  <p> Useful for logging out. Call in your <em>logout()</em> controller function to delete the google user data from the session. </p>
  <h3 id='getTokens'><span class='fxn'>getTokens</span>()</h3>
  <p> Returns a JSON string of the tokens returned on successful authentication and stored in the session array. </p>
  <h3>init_db()</h3>
  <p>Function for initializing the sqlite database. </p>
  <h3>get_credentials($userid)</h3>
  <p>Will return a JSON string with the credentials from the Google_Client authentication in the <em>connect()</em> method. </p>
  <ul>
    <li><strong>userid:</strong> This is the Google ID of your authenticated user, stored in the session.</li>
  </ul>
  <h3>store_credentials($userid, $token) </h3>
  <p>Will store the <em>$token</em> passed against the <em>$userid</em> in the credentials table. USE WITH CAUTION - PREFERRABLY WITHIN THE authenticate() PRIVATE METHOD ONLY.</p>
  <ul>
    <li><strong>userid:</strong> This is the Google ID of your authenticated user, stored in the session.</li>
    <li><strong>$token:</strong> The JSON format string returned after a successul authentication</li>
  </ul>
  <h3>verify_credentials($controller, $credentials, $api_mode)</h3>
  <p>Used by the <em>authenticate</em> method to verify the session-stored credentials when a page is re-loaded.</p>
  <ul>
    <li><strong>controller: </strong>Pass your host controller using the <em>$this</em> variable in CakePHP</li>
    <li><strong>credentials:</strong> This is the JSON format string returned as the token which you exchange for your <em>code</em> in the OAuth flow.</li>
    <li><strong>api_mode:</strong> used to control the auto-redirect function of the <em>authenticate</em>() and <em>connect</em>() methods. Will return an associate array with a <em>success </em>index instead.</li>
  </ul>
  <h3>bootstrap_new_user() *Customize</h3>
  <p>This method will instantiate a new user as well as insert a <em>welcome </em>slide in their Glass Timeline. BE SURE TO CHANGE THE PARAMETERS HERE TO THOSE FOR YOUR PURPOSES.</p>
  <h3>insert_contact($contact_name, $display_name, $icon_url)</h3>
  <p>Inserts a contact for the authenticated user</p>
  <ul>
    <li><strong>contact_name: </strong>The name of the contact to be inserted</li>
    <li><strong>display_name: </strong>The display name.</li>
    <li><strong>icon_url: </strong>The web link for an icon to represent this contact.</li>
  </ul>
  <h3>subscribe_to_notifications($collection, $user_token, $callback_url) </h3>
  <p><strong>collection:</strong> A string representing the collection you want to insert a notificaiton to e.g. <em>&quot;timeline&quot;</em></p>
  <p><strong>user_token:</strong> as returned by the <em>getTokens()</em> method</p>
  <p><strong>callback_url:</strong> the resource link for Google glass regarding this notification</p>
  <h3>insert_timeline_item($timeline_item <em>[, $content_type, $attachment ] </em>)</h3>
  <p>inserts a pre-built timeline item into the user's glass timeline. The <em>$timeline_item</em> variable must be of type <em>Google_TimelineItem</em>. See the documentation for the Glass Mirror API for details: <a href="https://developers.google.com/glass/v1/reference/timeline" target="_blank">https://developers.google.com/glass/v1/reference/timeline</a></p>
  <ul>
    <li><strong>timeline_item:</strong> An instance of the Google_TimelineItem class with parameters populated using the GET methods.</li>
    <li><strong>content_type (optional):</strong> The MIME type of the timeline item. For a list of allowed MIME types, refer to the Mirror API Documentation: <a href="https://developers.google.com/glass/v1/reference/timeline/insert" target="_blank">https://developers.google.com/glass/v1/reference/timeline/insert</a>. For details, review the Mirror API documentation, specifically the functions in the Google_TimelineItem class, defined in the Google_MirrorService file in the Google PHP API: <a href="https://code.google.com/p/google-api-php-client/" target="_blank">https://code.google.com/p/google-api-php-client/</a>. </li>
    <li><strong>attachment (optional):</strong> An attachment for the timeline item. For details, review the Mirror API documentation, specifically the functions in the Google_TimelineItem class, defined in the Google_MirrorService file in the Google PHP API: <a href="https://code.google.com/p/google-api-php-client/" target="_blank">https://code.google.com/p/google-api-php-client/</a>. </li>
  </ul>
  <h3>delete_timeline_item($service, $item_id)</h3>
  <p>Deletes an item from the user's timeline. </p>
  <ul>
    <li><strong>service:</strong> An instance of the Google_MirrorService class as returned by the <em>getService()</em> method.</li>
    <li><strong>item_id:</strong>The ID for the item to be deleted.</li>
  </ul>
  <h3>delete_contact($contact_id)</h3>
  <p>Delete a contact.</p>
  <ul>
    <li><strong>contact_id:</strong> The ID for the stored contact.</li>
  </ul>
</blockquote>

  <h1 id='QA'>QUESTIONS?</h1>
  <p> Reach out @ <a href="https://twitter.com/uchechilaka" title="WebsiteInAPage on Twitter" target="_blank">https://twitter.com/websiteinapage</a>. I'll do my best to respond in a timely fasion. </p>
</div>