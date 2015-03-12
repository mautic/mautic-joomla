Mautic-Joomla plugin
====================

This [Joomla](http://joomla.org) Plugin lets you add the [Mautic](http://mautic.org) tracking gif image to your Joomla website and embed Mautic forms in Joomla content. If you authorize this plugin as the Mautic API application, plugin will be able to push data from Joomla registration form to Mautic as a Lead.

### Mautic Tracking Image

Tracking image works right after you enable the plugin, insert Base URL and save the plugin. That means it will insert 1 px gif image loaded from your Mautic instance. You can check HTML source code (CTRL + U) of your Joomla website to make sure the plugin works. You should be able to find something like this:

`<img src="http://yourmautic.com/mtracking.gif" />`

There will be probably longer URL query string at the end of the tracking image URL. It is encoded additional data about the page (title, url, referrer, language).

### Form embed

To embed a Mautic form into Joomla content, insert this code snippet:

	{mauticform ID}

ID is the identifier of the Mautic form you want to embed. You can see the ID of the form in the URL of the form detail. For example for ```www.yourmautic.com/forms/view/1```, ID = 1.

### Plugin authorization

It is possible to send Lead data to Mautic via API only after authorization. You can create specific authorization API credentials for each application. To do that, go to your Mautic administration and follow these steps:

1. Go to Mautic Configuration / API Settings and set 'API enabled' to 'Yes', leave 'API mode' to 'OAuth1'. Save changes.
2. At the right-hand side menu where Configuration is should appear new menu item 'API Credentials'. Hit it.
3. Create new credential. Fill in 'Name' ('Joomla plugin' for example) and Callback URL to ```http://{yourJoomla.com}/administrator``` (change ```{yourJoomla.com}``` with actual URL of your Joomla instance). It goes to /administrator to make sure only Joomla admins can authorize Mautic plugin. Save credentials.
4. Mautic should generate 'Consumer Key' and 'Consumer Secret' key. Copy those two to Joomla plugin. Save the plugin. Hit the 'Authorize' button and follow instructions.

## Developer notes

If you want to add more Mautic features, submit PR to this plugin or use this plugin as a base and develop your own extension which could do more. mauticApiHelper.php class will configure the Mautic API for you.

### Release of the new version

This plugin uses Joomla Update Server which notifies the Joomla admin about availability of new versions of this plugin. To do that, update the version tag in mautic.xml in the master branch and then update version tag at updateserver.xml in the gh-pages branch accordingly.

[Current updateserver.xml](http://mautic.github.io/mautic-joomla/updateserver.xml)
