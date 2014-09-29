<?php
/*------------------------------------------------------------------------
# Mautic
# ------------------------------------------------------------------------
# @author Mautic
# @copyright Copyright (C) 2014 Mautic All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.mautic.org
-------------------------------------------------------------------------*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 *
 * @package		Joomla
 * @subpackage	System.Mautic
 */
class plgSystemMautic extends JPlugin
{
	/**
	 * This event is triggered after the framework has loaded and the application initialise method has been called.
	 *
	 * @return	void
	 *
	 */
	public function onAfterDispatch()
	{
		$document 	= JFactory::getDocument();
		$input		= JFactory::getApplication()->input;

		// Check to make sure we are loading an HTML view and there is a main component area
		if ($document->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component')
		{
			return true;
		}

		$buffer  = $document->getBuffer('component');
		$image   = $this->params->get('base_url') . '/p/page/tracker.gif';
		$buffer .= $image;

		$document->setBuffer($buffer, 'component');

		return true;
	}
}
