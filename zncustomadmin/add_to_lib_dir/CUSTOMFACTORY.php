<?php
/* 
 * 
 */
class ADMINFACTORY extends BaseActions
{
	var $template;
	
	function AllowedActions()
	{
		return array(
			'if',
			'else',
			'endif',
			'elseif',
			'ifnot',
			'elseifnot',
			'charset',              //_CHARSET
			'sitename',             //$CONF['SiteName']
			'adminurl',             //baseUrl
			'extrahead',            //$extrahead
			'membername',           //$member->getDisplayName()
			'indexurl',             //$CONF['IndexURL']
			'nucleusversion',       //getNucleusVersion()
			'nucleuspatchlevel',    //getNucleusPatchLevel()
			'nucleusversionstring', //$nucleus['version']
			'quickmenu',            //
			'skinfile',             //
			'donate',               //donate
			'thisyear',             //
		);
	}
	function checkCondition($field, $name='', $value = '')
	{
		global $member, $manager;
		
		$condition = 0;
		switch($field) {
				
			case 'loggedin':
				$condition = $this->_ifMember($name);
				break;
			case 'hasplugin':
				$condition = $this->_ifHasPlugin($name, $value);
				break;
			default:
				$condition = $manager->pluginInstalled('NP_' . $field) && $this->_ifPlugin($field, $name, $value);
				break;
		}
		return $condition;
	}
	function _ifMember($name = '')
	{
		global $member;
		switch ($name)
		{
			case 'superadmin':
				$condition = $member->isLoggedIn() && $member->isAdmin();
				break;
			case '':
				$condition = $member->isLoggedIn();
				break;
		}
		return $condition;
	}
	function _ifHasPlugin($name, $value)
	{
		global $manager;
		$condition = false;
		// (pluginInstalled method won't write a message in the actionlog on failure)
		if ($manager->pluginInstalled('NP_'.$name)) {
			$plugin =& $manager->getPlugin('NP_' . $name);
			if ($plugin != NULL) {
				if ($value == "") {
					$condition = true;
				} else {
					list($name2, $value2) = explode('=', $value, 2);
					if ($value2 == "" && $plugin->getOption($name2) != 'no') {
						$condition = true;
					} else if ($plugin->getOption($name2) == $value2) {
						$condition = true;
					}
				}
			}
		}
		return $condition;
	}
	function _ifPlugin($name, $key = '', $value = '')
	{
		global $manager;

		$plugin =& $manager->getPlugin('NP_' . $name);
		if (!$plugin) return;

		$params = func_get_args();
		array_shift($params);

		return call_user_func_array(array(&$plugin, 'doIf'), $params);
	}
	function setTemplate(&$template)
	{
		$this->template = $template;
	}
	function parse_charset()
	{
		echo _CHARSET;
	}
	function parse_sitename()
	{
		global $CONF;
		echo htmlspecialchars($CONF['SiteName']);
	}
	function parse_adminurl()
	{
		global $CONF;
		echo htmlspecialchars($CONF['AdminURL']);
	}
	function parse_extrahead()
	{
		echo $this->template['extrahead'];
	}
	function parse_membername()
	{
		global $member;
		echo $member->getDisplayName();
	}
	function parse_indexurl()
	{
		global $CONF;
		echo $CONF['IndexURL'];
	}
	function parse_nucleusversion()
	{
		echo getNucleusVersion();
	}
	function parse_nucleuspatchlevel()
	{
		echo getNucleusPatchLevel();
	}
	function parse_nucleusversionstring()
	{
		global $nucleus;
		echo $nucleus['version'];
	}
	function parse_quickmenu()
	{
		global $action, $member, $manager;
		$qmenu = $this->template['qmenu'];
		
		if (($action != 'showlogin') && ($member->isLoggedIn())) {
			echo $this->template['sqmenuhead'];
			
			$aPluginExtras = array();
			$manager->notify(
				'QuickMenu',
				array(
					'options' => &$aPluginExtras
				)
			);
			if (count($aPluginExtras) > 0)
			{
				foreach ($aPluginExtras as $aInfo)
				{
					//echo '<li><a href="'.htmlspecialchars($aInfo['url']).'" title="'.htmlspecialchars($aInfo['tooltip']).'">'.htmlspecialchars($aInfo['title']).'</a></li>';
					//<li><a href="<%url%>" title="<%tooltip%>"><%title%></a></li>
					$qInfo = array(
						'url'     => htmlspecialchars($aInfo['url']    , ENT_QUOTES),
						'tooltip' => htmlspecialchars($aInfo['tooltip'], ENT_QUOTES),
						'title'   => htmlspecialchars($aInfo['title']  , ENT_QUOTES),
					);
					echo TEMPLATE::fill($qmenu, $qInfo);
				}
			}
			
			echo $this->template['sqmenufoot'];
		}
	}
	function parse_skinfile($filename)
	{
		global $CONF;
		echo $CONF['SkinsURL'] . $this->template['IncludePrefix'] . $filename;
	}
	function parse_donate($linktext = '')
	{
		$u        = 'http://nucleuscms.org/donate.php';
		$linktext = htmlspecialchars($linktext);
		$l = ($linktext) ? '<a href="'.$u.'" title="'.$linktext.'">'.$linktext.'</a>' : $u;
		echo $l;
	}
	function parse_thisyear()
	{
		echo date('Y');
	}
}

/* 
 * 
 */
class PAGEFACTORY extends basePAGEFACTORY
{
	
	function PAGEFACTORY($blogid)
	{
		parent::PAGEFACTORY($blogid);
	}
	/**
	 * 
	 */
	function getTemplateFor($type)
	{
		//$this->type   : 'admin' | 'bookmarklet'
		//$this->method : 'add'   | 'edit'
		
		global $manager;
		
		$blogid = intRequestVar('blogid');
		$blogid = ($blogid) ? $blogid : getBlogIDFromItemID(intRequestVar('itemid'));
		
		if ($manager->pluginInstalled('NP_znCustomAdmin'))
		{
			$znca         = & $manager->getPlugin('NP_znCustomAdmin');
			$templateName = $znca->getBlogOption($blogid, $this->type.'_'.$this->method);
			$template     = & $manager->getTemplate($templateName);
			$partName     = strtoupper($this->type).'_'.strtoupper($this->method);
			$itemTemplate = $template[$partName];
			$itemTemplate = ($znca->getOption('custom_flag') != 'no') ? $itemTemplate : '';
			$itemTemplate = ($itemTemplate) ? $itemTemplate : $this->zncaGetTemplateFor($this->type, $this->method);
			//echo '$templateName : '.$templateName.', $partName : '.$partName.', $blogid : '.$blogid.', $this->type : '.$this->type.', $this->method : '.$this->method;
			
			//
			$defines    = trim($template['DEFINE']);
			$defineList = array_map('trim', preg_split("/[\r\n]+/", $defines));
			foreach ($defineList as $val)
			{
				if (trim($val))
				{
					list($def_name, $def_val) = explode(':', $val);
					define($def_name, $def_val);
				}
			}
		}
		else
		{
			$itemTemplate = $this->zncaGetTemplateFor($this->type, $this->method);
		}
		$this->actions  = array_merge($this->actions, $this->getOriginalActions());
		
		$this->template = $template;
		$this->template['IncludePrefix'] = $template['PREFIX'];
		
		return $itemTemplate;
	}
	/**
	 * 標準機能からテンプレート取得
	 */
	function zncaGetTemplateFor($type, $method)
	{
		/*
		$formfactory         = & new PAGEFACTORY(0);
		$formfactory->type   = $type;
		$formfactory->method = $method;
		return $formfactory->getTemplateFor('');
		*/
		return parent::getTemplateFor('');
	}
	/**
	 * 
	 */
	function getOriginalActions()
	{
		//
		return array(
			'defaultcategory',       //hidden
			'currentblogcategories', // (showNewCatFlag, tabindex)
			'wysiwyg',               //wysiwygJavaScript
			'pluginform',            //
			'pluginitemoption',      //
			'znitemfieldex',         //NP_znItemFieldEX
			'znitemfieldexpresence', //NP_znItemFieldEX
			'customhelplinklib',     //
			'customhelplink',        //
			'skinfile',              //
			'author',                //<%contents(author)%>
		);
	}
	/**
	 * 
	 */
	function parse_author($which)
	{
		switch($which)
		{
			case 'realname':
				$itemid = $this->variables['itemid'];
				$query  = "SELECT mrealname AS result FROM ".sql_table('item')." AS i,".sql_table('member')." AS m  WHERE m.mnumber=i.iauthor AND inumber=".intval($itemid);
				$disp   = quickQuery($query);
				echo htmlspecialchars($disp, ENT_QUOTES);
				break;
		}
	}
	/**
	 * 
	 */
	function parse_wysiwyg($id)
	{
		$id = preg_replace('/[\'"<>]/', '', $id);
		echo '<script language="JavaScript">generate_wysiwyg("'.$id.'");</script>';
	}
	/**
	 * 
	 */
	function parse_pluginform($plugName)
	{
		global $manager;
			
		switch ($this->method) {
			case 'add':
				if (!$manager->pluginInstalled($plugName)) return;
				$plugin = & $manager->getPlugin($plugName);
				if (method_exists($plugin, 'event_AddItemFormExtras')) {
					call_user_func(
						array(&$plugin, 'event_AddItemFormExtras'), 
						array(
							'blog' => &$this->blog
						)
					);
				}
				break;
			case 'edit':
				if (!$manager->pluginInstalled($plugName)) return;
				$plugin = & $manager->getPlugin($plugName);
				if (method_exists($plugin, 'event_EditItemFormExtras')) {
					call_user_func(
						array(&$plugin, 'event_EditItemFormExtras'), 
						array(
							'variables' => $this->variables,
							'blog' => &$this->blog,
							'itemid' => $this->variables['itemid']
						)
					);
				}
				break;
		}
	}
	/**
	 * 
	 */
	function parse_pluginitemoption($plugName)
	{
		global $itemid, $manager;
		if (!$manager->pluginInstalled($plugName)) return;
		
		$context = 'item';
		$contextid = $itemid;
		// get all current values for this contextid
		// (note: this might contain doubles for overlapping contextids)
		$aIdToValue = array();
		$res = sql_query('SELECT oid, ovalue FROM ' . sql_table('plugin_option') . ' WHERE ocontextid=' . intval($contextid));
		while ($o = mysql_fetch_object($res)) {
			$aIdToValue[$o->oid] = $o->ovalue;
		}
		// get list of oids per pid
		$query = 'SELECT * FROM ' . sql_table('plugin_option_desc') . ',' . sql_table('plugin')
			   . ' WHERE opid=pid and ocontext=\''.addslashes($context).'\' and pfile=\''.$plugName.'\' ORDER BY porder, oid ASC';
		$res = sql_query($query);
		$aOptions = array();
		while ($o = mysql_fetch_object($res)) {
			if (in_array($o->oid, array_keys($aIdToValue)))
				$value = $aIdToValue[$o->oid];
			else
				$value = $o->odef;
			array_push($aOptions, array(
				'pid' => $o->pid,
				'pfile' => $o->pfile,
				'oid' => $o->oid,
				'value' => $value,
				'name' => $o->oname,
				'description' => $o->odesc,
				'type' => $o->otype,
				'typeinfo' => $o->oextra,
				'contextid' => $contextid,
				'extra' => ''
			));
		}
		global $manager;
		$manager->notify('PrePluginOptionsEdit',array('context' => $context, 'contextid' => $contextid, 'options'=>&$aOptions));
		$iPrevPid = -1;
		foreach ($aOptions as $aOption) {
			// new plugin?
			if ($iPrevPid != $aOption['pid']) {
				$iPrevPid = $aOption['pid'];
			}
			echo '<tr>';
			listplug_plugOptionRow($aOption);
			echo '</tr>';
		}
	}
	/**
	 * 
	 */
	function parse_znitemfieldex($fname)
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_znItemFieldEX')) return;
		$znItemFieldEX = & $manager->getPlugin('NP_znItemFieldEX');
		
		$itemid = intRequestVar('itemid'); //0
		$tname  = "item_b".intval($this->blog->getID());
		
		//
		$sql_str = "SELECT * FROM ".$znItemFieldEX->table_table.$tname." WHERE id=".$itemid;
		$qid = sql_query($sql_str);
		if ($qid and @mysql_num_rows($qid) > 0) $row_item = mysql_fetch_array($qid);
		
		$ftid = $znItemFieldEX->getIDFromTableName($tname);
		$sql_str = "SELECT * FROM ".$znItemFieldEX->table_fields." WHERE ftid='".$ftid."' AND fname='".$fname."' ORDER BY forder";
		$qid = sql_query($sql_str);
		$row = mysql_fetch_array($qid);
		echo ($row) ? '' : 'specified field name was not found.<br />';
		if ($row["ftype"] == 'Image' && $this->ifexImgJsFlag === FALSE) {
			$znItemFieldEX->printImgJs();
			$this->ifexImgJsFlag = TRUE;
		}
		
		$znItemFieldEX->EXFieldForm($row, $row_item, 10000);
	}
	/**
	 * 
	 */
	function parse_znitemfieldexpresence() //NP_znItemFieldEX
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_znItemFieldEX')) return;
		$znItemFieldEX = & $manager->getPlugin('NP_znItemFieldEX');
		
		$itemid = intRequestVar('itemid'); //0
		$tname  = "item_b".intval($this->blog->getID());
		$znItemFieldEX->EXFieldPresenceForm($tname, $itemid);
	}
	/**
	 * 
	 */
	function parse_defaultcategory()
	{
		$catid = intval($this->blog->getDefaultCategory());
		echo '<input type="hidden" name="catid" value="'.$catid.'" />';
	}
	/**
	 * 
	 */
	function parse_currentblogcategories($showNewCat = 0, $startidx = 0)
	{
		if ($this->variables['catid']) {
			$catid = $this->variables['catid'];         // on edit item
		} else {
			$catid = $this->blog->getDefaultCategory(); // on add item
		}
		$this->selectCategory('catid', $catid, $startidx, $showNewCat, $this->blog->getID());
	}
	/**
	 * 独自ヘルプを使用する場合に必要となるJavaScriptライブラリの読み込み（<body>直後が理想）
	 */
	function parse_customhelplinklib()
	{
		global $CONF;
		echo '<script type="text/javascript" src="'.htmlspecialchars($CONF['PluginURL'], ENT_QUOTES).'zncustomadmin/wz_tooltip.js"></script>';
	}
	/**
	 * <%customhelplink(helpid, BGCOLOR:#ffffff&BORDERCOLOR:#c0b070)%>
	 
	 */
	function parse_customhelplink($helpid, $params='BGCOLOR:#ffffff&BORDERCOLOR:#c0b070')
	{
		$params     = explode('&', $params);
		$paramArray = array();
		foreach($params as $val)
		{
			list($pkey, $pval) = explode(':', $val);
			$paramArray[] = $pkey.", '".$pval."'";
		}
		$helpid = preg_replace('/[\'"<>]/', '', $helpid);
		echo " onmouseover=\"TagToTip('".$helpid."', ".implode(',', $paramArray).")\"";
	}
	/**
	 * 
	 */
	function parse_skinfile($filename)
	{
		global $CONF;
		echo $CONF['SkinsURL'] . $this->template['IncludePrefix'] . $filename;
	}
	/**
	 * 
	 */
	function selectCategory($name, $selected = 0, $tabindex = 0, $showNewCat = 0, $iForcedBlogInclude)
	{
		global $member;
		echo '<select name="',$name,'" tabindex="',$tabindex,'">';
		//
		if ($showNewCat) {
			if ($member->blogAdminRights($iForcedBlogInclude))
				echo '<option value="newcat-',$iForcedBlogInclude,'">',_ADD_NEWCAT,'</option>';
		}
		$categories = sql_query('SELECT cname, catid FROM '.sql_table('category').' WHERE cblog=' . $iForcedBlogInclude . ' ORDER BY cname ASC');
		while ($oCat = mysql_fetch_object($categories)) {
			if ($oCat->catid == $selected)
				$selectText = ' selected="selected" ';
			else
				$selectText = '';
			echo '<option value="',$oCat->catid,'" ', $selectText,'>',htmlspecialchars($oCat->cname),'</option>';
		}
		echo '</select>';
	}
}
?>