Mautic-Joomla plugin
====================

This [Joomla](http://joomla.org) Plugin lets you insert the [Mautic](http://mautic.org) tracking gif image to your Joomla website and embed the Mautic forms to the Joomla articles.

### Form embed

To embed a Mautic form into a Joomla article, insert this code snippet to the article content:

	{mauticform ID}

ID is identifikator of the Mautic form you want to embed. You can see the ID of the form in the URL of the form detail. For example for www.yourmautic.com/forms/view/1, ID = 1.

## Developer notes

### Release of the new version

This plugin uses Joomla Update Server which notifies Joomla admin about availability of new version of this plugin right in the Joomla administration. To do that, update version tag in mautic.xml in the master branch and then update version tag at updateserver.xml in the gh-pages brach accordingly.

[Current updateserver.xml](http://mautic.github.io/mautic-joomla/updateserver.xml)
