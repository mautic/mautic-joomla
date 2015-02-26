Mautic-Joomla plugin
====================

This [Joomla](http://joomla.org) Plugin lets you add the [Mautic](http://mautic.org) tracking gif to your Joomla website and embed Mautic forms in Joomla content.

### Form embed

To embed a Mautic form into Joomla content, insert this code snippet:

	{mauticform ID}

ID is the identifier of the Mautic form you want to embed. You can see the ID of the form in the URL of the form detail. For example for www.yourmautic.com/forms/view/1, ID = 1.

## Developer notes

### Release of the new version

This plugin uses Joomla Update Server which notifies the Joomla admin about availability of new versions of this plugin. To do that, update the version tag in mautic.xml in the master branch and then update version tag at updateserver.xml in the gh-pages branch accordingly.

[Current updateserver.xml](http://mautic.github.io/mautic-joomla/updateserver.xml)
