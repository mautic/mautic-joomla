 Mautic for Joomla 4 Package - plugin and library
====================

This [Joomla](http://joomla.org)[^1] Package lets you add the [Mautic](http://mautic.org) tracking via Javascript or gif image for noscript to your Joomla website. With the plugin you can embed forms, dynamic content, tags and gated video support into your Joomla content. If you authorize this plugin as a Mautic API application, it will be able to push data from Joomla registration form to Mautic as a Contact.

**This package is compatible with Joomla 4.x.x. and Mautic 4.x**

### Mautic Tracking

Tracking option works right after you enable the plugin, insert Base URL and save the plugin. It loads a piece of javascript code to communicate with your Mautic instance.

If it doesn't work please check also your Mautic CORS configuration settings. If the CORS option is enabled in Mautic then the base url of the website where this plugin is running must be listed in valid domains.
Otherwise you see some errors in the javascript console, that it is blocked by CORS settings.

#### Image - noscript option

Tracking images for noscript option works right after you enable the plugin, insert Base URL and save the plugin. That means it will insert 1 px gif image loaded from your Mautic instance. You can check HTML source code (CTRL + U) of your Joomla website to make sure the plugin works. You should be able to find something like this:

`<noscript><img src="http://yourmautic.com/mtracking.gif" /></noscript>`

There will be probably longer URL query string at the end of the tracking image URL. It is encoded additional data about the page (title, url, referrer, language).

### Form embed

To embed a Mautic form into Joomla content, insert this code snippet:

	{mautic type="form" ID}

ID is the identifier of the Mautic form you want to embed. You can see the ID of the form in the URL of the form detail. For example for ```www.yourmautic.com/forms/view/1```, ID = 1.

If you have trouble check also <a href="###Bug form embed">Troubleshoot form embed</a>

### Dynamic content embed

To embed Mautic dynamic content, insert this code snippet:

    {mautic type="content" slot="slot_name"}Default content here.{/mautic}

slot_name is the identifier of the slot that you specified when adding a "Request dynamic content" decision to your campaign.

### Gated video embed

Mautic gated video supports YouTube, Vimeo, and MP4. If you want to use gated video on a Vimeo video, you must manually include the Froogaloop javascript. Also, to use gated video, you must manually include the jQuery javascript library on your page.

To embed Mautic gated videos, insert this code snippet:

    {mautic type="video" height="320" width="640" src="https://www.youtube.com/watch?v=QT6169rdMdk" gate-time="15" form-id="1"}
    
Note, you must replace the src with the URL to the video. Height and width attributes are optional, and will default to the values shown if omitted. Gate time is the time, in seconds, that you would like to pause the video and display the form that you entered as the form-id. 

### Tags

To embed Mautic Tags, insert this code snippet:

    {mautic type="tags" tags="tag1,tag2,-removetag"}

You can add or remove one or multiple lead tags on specific pages using commas. For removing a tag you can use "-" sign before tag anme.

### Plugin authorization

It is possible to send Contact data to Mautic via API only after authorization with OAuth2. You can create specific authorization API credentials for each application. To do that, go to your Mautic administration and follow these steps:

1. Go to Mautic Configuration / API Settings and set 'API enabled' to 'Yes'. Save changes.
2. At the right-hand side menu where Configuration is should appear new menu item 'API Credentials'. Hit it.
3. Create new credential. Fill in 'Name' ('Joomla instance' for example) and Callback URL to ```https://{yourJoomla.com}/administrator``` (change ```{yourJoomla.com}``` with actual URL of your Joomla instance). It goes to /administrator to make sure only Joomla admins can authorize Mautic plugin. If you have modified the ` Joomla Callback Path Field` in your Joomla Plugin Settings then you have to change the subpath here accordingly. Save credentials.
4. Mautic should generate 'Client ID / Public Key' and 'Client Secret / Private Key'. Copy those two to Joomla plugin. Save the plugin. Hit the 'Authorize' button and follow instructions.

## Troubleshoot ##

### Bug form embed ###
If your forms are not embedded you can check the errors in the developer console of your browser. If you see an error like:
`/media/js/mautic-form.js not found`

then open the folder `docroot/media/js` in your Mautic instance and check if file `mautic-form.js` is available. If not, then simple copy file with command `cp mautic-form-src.js mautic-form.js`.

## Developer notes

If you want to add more Mautic features, submit PR to this plugin or use this plugin as a base and develop your own extension which could do more. MauticApiHelper.php class will configure the Mautic API for you.

### Release of the new version

This plugin uses Joomla Update Server which notifies the Joomla admin about availability of new versions of this plugin. To do that, update the version tag in mautic.xml in the master branch and then update version tag at updateserver.xml in the main branch accordingly.

[Current updateserver.xml](http://mautic.github.io/mautic-joomla/updateserver.xml)

### Integrate Mautic with another extension

To add Mautic contact generation to another extension is not hard at all. Just let the joomla admin install and configure this plugin then you can use Mautic API calls. Here is an example how to generate Mautic contacts on any form save:

```php

$apiHelper      = new \Joomla\Plugin\System\Mautic\Helper\MauticApiHelper();
/** @var \Mautic\Auth\OAuth $auth */
$auth           = $this->apiHelper->getMauticAuth();
// Check and refresh token if needed
$authIsValid    = $auth->validateAccessToken();
if ($authIsValid && $auth->accessTokenUpdated()) {
    $apiHelper->storeRefreshedToken($auth);
}
/** @var \Mautic\Api\Contacts $contactsapi */
$mauticApi      = new \Mautic\MauticApi();
$contactsApi    = $mauticApi->newApi(
    "contacts", 
    $auth, 
    $apiHelper->getMauticBaseUrl() . '/api/'
);

$contact = $contactsApi->create([
    'ipAddress' => \Joomla\Utilities\IpHelper::getIp(),
    'firstname' => $formData['firstname'],
    'lastname'  => $formData['lastname'],
    'email'     => $formData['email'],
]);
```

More information about Mautic API calls can be found at [Mautic API Library](https://github.com/mautic/api-library) which is part of this package as a library.

&#xa0;

[^1]: This package - MauticForJoomla4 - is not affiliated with or endorsed by The Joomla! Project™. It is not supported or warrantied by The Joomla! Project or Open Source Matters, Inc. Use of the Joomla!® name, symbol, logo and related trademarks is permitted under a limited license granted by Open Source Matters, Inc.

<a href="#top">Back to top</a>
