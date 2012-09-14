<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Plugin 'DLF: Pageview' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_pageview extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/pageview/class.tx_dlf_pageview.php';

	/**
	 * Adds JavaScript for the viewer
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function addJS() {

		// Add JavaScript files to header.
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_olJS'] = '	<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/pageview/dlfOL.js"></script>';

		// Add localization file for OpenLayers.
		if ($GLOBALS['TSFE']->lang) {

			$langFile = t3lib_extMgm::siteRelPath($this->extKey).'lib/OpenLayers/lib/OpenLayers/Lang/'.strtolower($GLOBALS['TSFE']->lang).'.js';

			if (file_exists($langFile)) {

				$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_olJS_lang'] = '	<script type="text/javascript" src="'.$langFile.'"></script>';

			}

		}

		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_olJS_viewer'] = '	<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/pageview/viewer.js"></script>';

		// Set "onbeforeunload" handler on body tag.
//		$GLOBALS['TSFE']->pSetup['bodyTagAdd'] = 'onbeforeunload="javascript:dlfViewer.saveData();"';

	}

	/**
	 * Gets the required information about an image
	 *
	 * @access	protected
	 *
	 * @param	integer		$page: The page number (defaults to $this->piVars['page'])
	 *
	 * @return	string		The JSON encoded image data
	 */
	protected function getImageData($page = 0) {

		// Save parameter for logging purposes.
		$_page = $page;

		// Cast to integer for security reasons.
		$page = intval($page);

		// Set default value if not set.
		if (!$page && $this->piVars['page']) {

			$page = $this->piVars['page'];

		}

		$imageData = array ();

		$fileGrps = t3lib_div::trimExplode(',', $this->conf['fileGrps']);

		foreach ($fileGrps as $fileGrp) {

			$fileGrp = strtolower($fileGrp);

			if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp])) {

				$fileGrpUrl = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp]);

				// Check file's existence.
				$headers = @get_headers($fileGrpUrl);

				if (is_array($headers) && preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $headers[0])) {

					$fileGrpSize = getimagesize($fileGrpUrl);

					$imageData[] = array (
						'width' => $fileGrpSize[0],
						'height' => $fileGrpSize[1],
						'url' => $fileGrpUrl
					);

				} else {

					if (TYPO3_DLOG) {

						t3lib_div::devLog('[tx_dlf_pageview->getImageData('.$_page.')] File not found: "'.$fileGrpUrl.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

					}

				}

			}

		}

		return $imageData;

	}

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Disable caching for this plugin.
		$this->setCache(FALSE);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL || $this->doc->numPages < 1) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values for page if not set.
			$this->piVars['page'] = t3lib_div::intInRange($this->piVars['page'], 1, $this->doc->numPages, 1);

		}

		$content .= $this->showViewer();

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Initializes the viewer
	 *
	 * @access	protected
	 *
	 * @return	string		Viewer code ready for output
	 */
	protected function showViewer() {

		$this->addJS();

		// Set plugin variable defaults.
//		$this->piVars['double'] = (!empty($this->piVars['double']) ? 1 : 0);

//		$this->piVars['showOcrOverlay'] = (!empty($this->piVars['showOcrOverlay']) ? 1 : 0);

		// Configure double-page layout.
//		if ($this->piVars['double']) {
//
//			// Check if current page is the last one.
//			if ($this->piVars['page'] = $this->doc->numPages) {
//
//				if ($this->doc->numPages > 1) {
//
//					$this->piVars['page'] = $this->piVars['page'] - 1;
//
//				} else {
//
//					// The document has just one page.
//					$this->piVars['double'] = 0;
//
//				}
//
//			}
//
//		}

		// Build HTML code.
		$viewer = '<div id="tx-dlf-map"><div id="tx-dlf-lefttarget"></div><div id="tx-dlf-righttarget"></div></div>';

		// Get values for viewer initialization.
		$imageDataLeft = $this->getImageData();

//		$imageDataRight = ($this->piVars['double'] ? $this->getImageData($this->piVars['page'] + 1) : array ());
//
//		$doublePageView = $this->piVars['double'];
//
//		$userName = (!empty($GLOBALS['TSFE']->fe_user->user['name']) ? $GLOBALS['TSFE']->fe_user->user['name'] : (!empty($GLOBALS['TSFE']->fe_user->user['username']) ? $GLOBALS['TSFE']->fe_user->user['username'] : ''));
//
//		$userId = (!empty($GLOBALS['TSFE']->fe_user->user['uid']) ? $GLOBALS['TSFE']->fe_user->user['uid'] : 0);
//
//		$options = array ('showOcrOverlay' => $this->piVars['showOcrOverlay']);

		$addImages = array ();

		foreach ($imageDataLeft as $imageData) {

			$addImages[] = 'dlfViewer.addImage('.$imageData['width'].','.$imageData['height'].',"'.$imageData['url'].'");';

		}

		// !!!ATTENTION PLEASE: THE ID WITHIN THE SCRIPT ELEMENT IS IMPORTANT AND SHOULD STAY IN FUTURE RELEASES!!!
		$viewer .= '
		<script id="tx-dlf-pageview-initViewer" type="text/javascript">
		///* <![CDATA[ */
		dlfViewer = new Viewer();
		'.implode("\n", $addImages).'
		dlfViewer.run();
		/* ]]> */
		</script>';

		return $viewer;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php']);
}

?>