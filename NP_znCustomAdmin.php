<?php
/*
	v0.6.4 : [add] 
	v0.6.3 : [add] 
	       : [add] <%author(realname)%>
	       : [up]  
	v0.6.2 : [add] customhelplink<%customhelplink(helpid, BGCOLOR:#ffffff&BORDERCOLOR:#c0b070)%>
	       : [bug] 
	v0.6.1 : [add] NP_znSpecialTemplateParts
	       : [add] bookmarklet
	       : [bug] defaultjs
	v0.05  : if
	            doIf
	            Content-Type text/html
	v0.04  : wysiwyg
	v0.03  : ADMIN
	v0.02  : NP_ResetAdminCSS
	v0.01  : initial release
*/
class NP_znCustomAdmin extends NucleusPlugin
{
	/**
	 * 
	 * @return 
	 */
	function getName()
	{
		return 'znCustomAdmin';
	}
	/**
	 * 
	 * @return 
	 */
	function getURL()
	{
		return 'http://wa.otesei.com/NP_znCustomAdmin';
	}
	/**
	 * 
	 * @return 
	 */
	function getVersion()
	{
		return '0.6.4';
	}
	/**
	 * 
	 * @param $w 
	 * @return 
	 */
	function supportsFeature($w)
	{
		return ($w == 'SqlTablePrefix') ? 1 : 0;
	}
	/**
	 * 
	 * @return 
	 */
	function getDescription()
	{
		return ''._ZNCA1.'';
	}
	/**
	 * 
	 * @return 
	 */
	function getAuthor()
	{
		$this->languageInclude();
		return ''._ZNCA2.'';
	}
	/**
	 * 
	 * @return 
	 */
	function getEventList()
	{
		return array(
			'AdminPrePageHead', 
			'PreSendContentType', // we need to force text/html instead of application/xhtml+xml
			'PostPluginOptionsUpdate', 
		);
	}
	/**
	 * 
	 * @return 
	 */
	function getTemplateParts()
	{
		$this->languageInclude();
		return array(
			'HEADER'          => ''._ZNCA3.'', 
			'FOOTER'          => ''._ZNCA4.'', 
			'QUICKMENU_HEADER'=> ''._ZNCA5.'', 
			'QUICKMENU'       => ''._ZNCA6.'', 
			'QUICKMENU_FOOTER'=> ''._ZNCA7.'', 
			'ADMIN_ADD'       => ''._ZNCA8.' (Admin)', 
			'ADMIN_EDIT'      => ''._ZNCA9.' (Admin)', 
			'BOOKMARKLET_ADD' => ''._ZNCA8.' (Bookmarklet)', 
			'BOOKMARKLET_EDIT'=> ''._ZNCA9.' (Bookmarklet)', 
			'PREFIX'          => 'skindir'._ZNCA10.'', 
			'DEFINE'          => ''._ZNCA11.'('._ZNCA12.':'._ZNCA13.')', 
		);
	}
	/**
	 * 
	 */
	function languageInclude()
	{
		//return;
		
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		$incFile  = (file_exists($this->getDirectory().$language.'.php')) ? $language : 'english';
		include_once($this->getDirectory().$incFile.'.php');
	}
	/**
	 * 
	 */
	function install()
	{
		$this->languageInclude();
		$this->createOption('custom_flag' , ''._ZNCA14.'', 'yesno', 'yes');
		$this->createOption('adminskin'   , ''._ZNCA15.'', 'select', 'helium', 'default||helium|helium'); //|||
		$this->createOption('def_template', ''._ZNCA16.'', 'text', 'helium');
		
		//option
		$this->createBlogOption('admin_add'       , ''._ZNCA17.' (Admin)'      , 'select', 'helium', 'default||helium|helium'); //|||
		$this->createBlogOption('admin_edit'      , ''._ZNCA18.' (Admin)'      , 'select', 'helium', 'default||helium|helium'); //|||
		$this->createBlogOption('bookmarklet_add' , ''._ZNCA17.' (Bookmarklet)', 'select', 'helium', 'default||helium|helium'); //|||
		$this->createBlogOption('bookmarklet_edit', ''._ZNCA18.' (Bookmarklet)', 'select', 'helium', 'default||helium|helium'); //|||
	}
	/**
	 * 
	 * @param $data 
	 */
	function event_PostPluginOptionsUpdate($data)
	{
		if ($data['context'] != 'global' || $data['plugid'] != $this->GetID()) return;
		
		$def_template = $this->getOption('def_template');
		$this->updateTargetTemplate('def', $def_template);
	}
	/**
	 * AdminPrePageHead
	 * 
	 */
	function event_AdminPrePageHead($data)
	{
		global $manager;
		$this->action = $data['action']; //
		
		$action = $data['action'];
		if ( $action == 'blogsettings' || ($action == 'pluginoptions' && intGetVar('plugid') == $this->GetID()) ) $this->updateTargetTemplate('extra');
	}
	/**
	 * admin
	 */
	function updateTargetTemplate($mode, $def='')
	{
		$oidArray   = array();
		$oidArray[] = $this->getPluginOptionID('adminskin');
		$oidArray[] = $this->getPluginOptionID('admin_add');
		$oidArray[] = $this->getPluginOptionID('admin_edit');
		$oidArray[] = $this->getPluginOptionID('bookmarklet_add');
		$oidArray[] = $this->getPluginOptionID('bookmarklet_edit');
		
		switch ($mode)
		{
			case 'extra': //....
				$query = "
					SELECT 
						td.tdname 
					FROM 
						".sql_table('template_desc')." AS td , 
						".sql_table('template')." AS t 
					WHERE 
						td.tdnumber=t.tdesc AND 
						t.tpartname='STP_PLUGINNAME' AND 
						t.tcontent='NP_znCustomAdmin'";
				$qid = sql_query($query);
				$skinSelectStr = 'default|';
				while ($row = sql_fetch_array($qid)) $skinSelectStr .= '|'.$row['tdname'].'|'.$row['tdname'];
				sql_query("UPDATE ".sql_table("plugin_option_desc")." SET oextra='".addslashes($skinSelectStr)."' WHERE oid in (".implode(',', $oidArray).")");
				break;
			case 'def': //....
				sql_query("UPDATE ".sql_table("plugin_option_desc")." SET   odef='".addslashes($def)."'           WHERE oid in (".implode(',', $oidArray).")");
				break;
		}
	}
	/**
	 * event_PreSendContentType
	 * @param $data 
	 */
	function event_PreSendContentType($data)
	{
		//helium skin JavaScriptapplication/xhtml+xml
		if ($data['contentType'] == 'application/xhtml+xml') $data['contentType'] = 'text/html';
	}
	/**
	 * if
	 * @return 
	 */
	function doIf($para1='', $para2 = '')
	{
		//action
		return ($this->action == strtolower($para1));
	}
	/**
	 * oid
	 * @return id
	 */
	function getPluginOptionID($name)
	{
		return quickQuery("SELECT oid AS result FROM ".sql_table('plugin_option_desc')." WHERE opid=".intval($this->getID())." AND oname='".addslashes($name)."'");
	}
}
?>