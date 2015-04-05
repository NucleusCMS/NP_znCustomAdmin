<?php

class ADMIN extends baseADMIN
{
	/**
	 * pagehead
	 */
	function pagehead($extrahead = '')
	{
		global $manager;
		$params = array('extrahead' => &$extrahead, 'action' => $this->action);
		$manager->notify('AdminPrePageHead', $params);
		$this->adminfactoryParser('defaultPagehead', 'HEADER', $extrahead);
	}
	/**
	 * pagefoot
	 */
	function pagefoot()
	{
		global $manager;
		$params = array('action' => $this->action);
		$manager->notify('AdminPrePageFoot', $params);
		$this->adminfactoryParser('defaultPagefoot', 'FOOTER');
	}
	/**
	 * createitem
	 */
	function action_createitem()
	{
		global $manager, $member;
		$blogid = intRequestVar('blogid');
		
		$member->teamRights($blogid) or $this->disallow();
		$memberid = $member->getID();
		$blog =& $manager->getBlog($blogid);
		
		$this->pagehead();
		$formfactory = new PAGEFACTORY($blogid);
		$formfactory->createAddForm('admin');
		$this->pagefoot();
	}
	/**
	 * itemedit
	 */
	function action_itemedit()
	{
		global $member, $manager;
		$itemid = intRequestVar('itemid');
		
		$member->canAlterItem($itemid) or $this->disallow();
		
		$item =& $manager->getItem($itemid,1,1);
		$blogid = getBlogIDFromItemID($itemid);
		$blog =& $manager->getBlog($blogid);
		
		$params = array('item' => &$item);
		$manager->notify('PrepareItemForEdit', $params);
		if ($blog->convertBreaks()) {
			$item['body'] = removeBreaks($item['body']);
			$item['more'] = removeBreaks($item['more']);
		}
		
		$this->pagehead();
		$formfactory = new PAGEFACTORY($blog->getID());
		$formfactory->createEditForm('admin', $item);
		$this->pagefoot();
	}
	
	/**
	 * parse
	 */
	function adminfactoryParser($defaultStrFunc, $mode, $extrahead = '')
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_znCustomAdmin'))
		{
			echo call_user_func(array(&$this, $defaultStrFunc), $extrahead);
			return;
		}
		$znca         = & $manager->getPlugin('NP_znCustomAdmin');
		$templateName = $znca->getOption('adminskin');
		$template     = & $manager->getTemplate($templateName);
		$customStr    = ($znca->getOption('custom_flag') != 'no') ? $template[$mode] : '';
		$content      = ($customStr) ? $customStr : call_user_func(array(&$this, $defaultStrFunc), $extrahead);
		$parseTemplate = array(
			'IncludePrefix' => $template['PREFIX'],
			'extrahead'     => $extrahead,
			'sqmenuhead'    => $template['QUICKMENU_HEADER'],
			'qmenu'         => $template['QUICKMENU'],
			'sqmenufoot'    => $template['QUICKMENU_FOOTER'],
		);
		$actions = new ADMINFACTORY('');
		$parser  = new PARSER(ADMINFACTORY::AllowedActions(), $actions);
		$actions->setTemplate($parseTemplate);
		$parser->parse($content);
		
		//echo '[['.hsc($extrahead, ENT_QUOTES).']]';
	}
	/**
	 * defaultPagehead
	 */
	function defaultPagehead($extrahead = '')
	{
		global $member, $nucleus, $CONF;
		$baseUrl = hsc($CONF['AdminURL']);
		$defaultHeader = '
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset='._CHARSET.'" />
				<title>'.hsc($CONF['SiteName']).' - Admin</title>
				<link rel="stylesheet" title="Nucleus Admin Default" type="text/css" href="'.$baseUrl.'styles/admin.css" />
				<link rel="stylesheet" title="Nucleus Admin Default" type="text/css" href="'.$baseUrl.'styles/addedit.css" />
				<script type="text/javascript" src="'.$baseUrl.'javascript/edit.js"></script>
				<script type="text/javascript" src="'.$baseUrl.'javascript/admin.js"></script>
				<script type="text/javascript" src="'.$baseUrl.'javascript/compatibility.js"></script>
				<meta http-equiv="Pragma" content="no-cache" />
				<meta http-equiv="Cache-Control" content="no-cache, must-revalidate" />
				<meta http-equiv="Expires" content="-1" />
				'.$extrahead.'
			</head>
			<body>
			<div class="header">
			<h1>'.hsc($CONF['SiteName']).'</h1>
			</div>
			<div id="container">
			<div id="content">
			<div class="loginname">';
		$defaultHeader .= ($member->isLoggedIn()) 
			? 
				_LOGGEDINAS . ' ' . $member->getDisplayName()
				." - <a href='index.php?action=logout'>" . _LOGOUT. "</a>"
				. "<br /><a href='index.php?action=overview'>" . _ADMINHOME . "</a> - "
			: 
				'<a href="index.php?action=showlogin" title="Log in">' . _NOTLOGGEDIN . '</a> <br />';
		$defaultHeader .= "<a href='".$CONF['IndexURL']."'>"._YOURSITE."</a><br />(";
		$defaultHeader .= ($member->isLoggedIn() && $member->isAdmin())
			? 
				'<a href="http://nucleuscms.org/version.php?v='
				.getNucleusVersion().'&amp;pl='.getNucleusPatchLevel().'" title="Check for upgrade">Nucleus CMS '.$nucleus['version'].'</a>'
			: 
				'Nucleus CMS '.$nucleus['version'];
		$defaultHeader .= ')</div>';
		return $defaultHeader;
	}
	/**
	 * defaultPagefoot
	 */
	function defaultPagefoot()
	{
		global $action, $member, $manager;
		
		$defaultFooter = '';
		if ($member->isLoggedIn() && ($action != 'showlogin'))
		{
			$defaultFooter .= '<h2>'._LOGOUT.'</h2><ul>'.
				'<li><a href="index.php?action=overview">'._BACKHOME.'</a></li><li><a href="index.php?action=logout">'._LOGOUT.'</a></li></ul>';
		}
		$defaultFooter .= '
			<div class="foot">
				<a href="http://nucleuscms.org/">Nucleus CMS</a> &copy; 2002-'.date('Y').' The Nucleus Group - <a href="http://nucleuscms.org/donate.php">Donate!</a>
			</div>
			</div><!-- content -->
			<div id="quickmenu">';
		// ---- user settings ----
		if (($action != 'showlogin') && ($member->isLoggedIn())) {
			$defaultFooter .= '
				<ul><li><a href="index.php?action=overview">'._QMENU_HOME.'</a></li></ul>
				<h2>'._QMENU_ADD.'</h2>
				<form method="get" action="index.php"><div>
				<input type="hidden" name="action" value="createitem" />';

			$showAll = requestVar('showall');
			if (($member->isAdmin()) && ($showAll == 'yes')) {
				// Super-Admins have access to all blogs! (no add item support though)
				$query =  'SELECT bnumber as value, bname as text'
					   . ' FROM ' . sql_table('blog')
					   . ' ORDER BY bname';
			} else {
				$query =  'SELECT bnumber as value, bname as text'
					   . ' FROM ' . sql_table('blog') . ', ' . sql_table('team')
					   . ' WHERE tblog=bnumber and tmember=' . $member->getID()
					   . ' ORDER BY bname';
			}
			$template['name'] = 'blogid';
			$template['tabindex'] = 15000;
			$template['extra'] = _QMENU_ADD_SELECT;
			$template['selected'] = -1;
			$template['shorten'] = 10;
			$template['shortenel'] = '';
			$template['javascript'] = 'onchange="return form.submit()"';
			ob_start();
			showlist($query, 'select', $template);
			$defaultFooter .= ob_get_contents();
			ob_end_clean();
			
			$defaultFooter .= '
				</div></form>
				<h2>' . $member->getDisplayName(). '</h2>
				<ul>
				<li><a href="index.php?action=editmembersettings">'._QMENU_USER_SETTINGS.'</a></li>
				<li><a href="index.php?action=browseownitems">'._QMENU_USER_ITEMS.'</a></li>
				<li><a href="index.php?action=browseowncomments">'._QMENU_USER_COMMENTS.'</a></li>
				</ul>';
			
			// ---- general settings ----
			if ($member->isAdmin()) {
				$defaultFooter .= '
					<h2>'._QMENU_MANAGE.'</h2>
					<ul>
					<li><a href="index.php?action=actionlog">'._QMENU_MANAGE_LOG.'</a></li>
					<li><a href="index.php?action=settingsedit">'._QMENU_MANAGE_SETTINGS.'</a></li>
					<li><a href="index.php?action=usermanagement">'._QMENU_MANAGE_MEMBERS.'</a></li>
					<li><a href="index.php?action=createnewlog">'._QMENU_MANAGE_NEWBLOG.'</a></li>
					<li><a href="index.php?action=backupoverview">'._QMENU_MANAGE_BACKUPS.'</a></li>
					<li><a href="index.php?action=pluginlist">'._QMENU_MANAGE_PLUGINS.'</a></li>
					</ul>
					<h2>'._QMENU_LAYOUT.'</h2>
					<ul>
					<li><a href="index.php?action=skinoverview">'._QMENU_LAYOUT_SKINS.'</a></li>
					<li><a href="index.php?action=templateoverview">'._QMENU_LAYOUT_TEMPL.'</a></li>
					<li><a href="index.php?action=skinieoverview">'._QMENU_LAYOUT_IEXPORT.'</a></li>
					</ul>';
			}

			$aPluginExtras = array();
			$params = array('options' => &$aPluginExtras);
			$manager->notify('QuickMenu', $params);
			if (count($aPluginExtras) > 0) {
				$defaultFooter .= '
					<h2>'. _QMENU_PLUGINS. '</h2>
					<ul>';
				foreach ($aPluginExtras as $aInfo)
				{
					$defaultFooter .= '<li><a href="'.hsc($aInfo['url']).'" title="'.hsc($aInfo['tooltip']).'">'.hsc($aInfo['title']).'</a></li>';
				}
				$defaultFooter .= '</ul>';
			}

		} else if (($action == 'activate') || ($action == 'activatesetpwd')) {

			$defaultFooter .= '<h2>'. _QMENU_ACTIVATE. '</h2>'. _QMENU_ACTIVATE_TEXT;
		} else {
			// introduction text on login screen
			$defaultFooter .= '<h2>'. _QMENU_INTRO. '</h2>'. _QMENU_INTRO_TEXT;
		}
		$defaultFooter .= '
			</div>
			<!-- content / quickmenu container -->
			</div>
			</body>
			</html>';
		echo $defaultFooter;
	}
}
?>