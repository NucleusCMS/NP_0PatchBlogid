'<?php
/*
 * NP_0PatchBlogid
 *
 * Writer japan.nucleuscms.org
 * Licence : GPL
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 */
/*
 * 0.0.1 draft version
 * 0.0.2 add $archive check
 * 0.0.3 add pager check
 * 0.0.4 add PATH_INFO with pager and install scripts
 * 0.0.5 add checking chars in PATH_INFO
 * 0.0.6 bug fix ParseURL typo
 * 0.0.7 bug fix ParseURL $data
 * 0.0.8 fix handling $archive
 * 0.0.9 add event procedure PreSendContentType
 * 0.0.10 add etc. etc. etc.
 * 0.0.11 add REQUEST_URI handling
 * 0.0.12 tiny bug fix'
 * 0.0.13 add for Magical and ItemNaveEx
 * 0.0.14 branch testing code and bug fix
 * 0.0.15 bug fix handling page= in REQUEST_URI
 * 0.0.16 bug fix $manager and more
 * 0.0.17 bug fix htmlspecial chars
 *
 * 0.1.1 the first success version
 * 0.1.2 fix getEventList
 * 0.1.3 missing
 * 0.1.4 add $blogid error trigger
 * 0.1.5 fix FancyURLs MODE and Required Nucleus ver. 2.5 or later
 * 0.1.6 refactoring code
 * 0.1.7 bug fix $server_script_name and more
 * 0.1.8 bug fix proceding order in htmlsspecialchars to $server_requert_uri
 * 0.1.9 bug fix handlig $_GET['page']
 * 0.1.10 bug fix check page type
 * 0.1.11 fix typo pagetype => pageType
 * 0.1.12 change $_GET['page'] handling
 * 0.1.13 bug fix excess page=
 *
 * 0.2.1 clean up commented codes
 * 0.2.2 bug fix $server_query_string in $server_request_uri
 * 0.2.3 fix excess page= realy?
 * 0.2.5 care for loop
 *
 */
if (!function_exists('sql_table')) {
    function sql_table($name)
    {
        return 'nucleus_' . $name;
    }
}

class NP_0PatchBlogid extends NucleusPlugin
{

    function getName()
    {
        return '0PatchBlogid';
    }

    function getAuthor()
    {
        return 'japan.nucleuscms.org';
    }

    function getURL()
    {
        return 'http://japan.nucleuscms.org/';
    }

    function getVersion()
    {
        return '0.2.5';
    }

    function getMinNucleusVersion()
    {
        return 250;
    }

    function getDescription()
    {
        return 'check $blogid and $archive';
    }

    function hasAdminArea()
    {
        return 0;
    }

    function getEventList()
    {
        return array('PreSendContentType');
    }

    function supportsFeature($what)
    {
		if ($what === 'SqlTablePrefix') {
			return 1;
		}

		if ($what === 'HelpPage') {
			return 0;
		}

		return 0;
	}

    /**
     * install scripts
     * this function is written by shizuki
     *
     * @author shizuki
     */
    function install()
    {
        // this function is written by shizuki.
        //Plugins sort
        $myid = $this->getID();
        $res = sql_query('SELECT pid, porder FROM ' . sql_table('plugin'));
        while ($p = mysql_fetch_object($res)) {
            if ($p->pid == $myid) {
                sql_query(
                	sprintf(
                		'UPDATE %s SET porder=1 WHERE pid=%d'
						, sql_table('plugin')
						, $myid
					)
				);
            } else {
                sql_query(
                	sprintf(
                		'UPDATE %s SET porder = %d WHERE pid = %d'
						, sql_table('plugin')
						, $p->porder + 1
						, $p->pid
					)
				);
            }
        }
    }

    function event_PreSendContentType($arg)
    {
        global $blogid, $archivelist, $archive;
        global $HTTP_SERVER_VARS;

        if (strtolower($arg['pageType']) !== 'skin') {
			return;
		}
        // getVar for page= searching
        $get_var_page = $this->_redoMagic(getVar('page'));
        // check blogid
        if (is_numeric($blogid)) {
			$blogid = intVal($blogid);
		} else {
			$blogid = getBlogIDFromName($blogid);
		}

        // check archivelist
        if (!is_numeric($archivelist)) {
			$archivelist = getBlogIDFromName($archivelist);
		}
        if ($archivelist) {
			$blogid = $archivelist;
		}
        // archive check
        // and more
        // if (! $blogid) doError(_ERROR_NOSUCHBLOG);

        sscanf($archive, '%d-%d-%d', $y, $m, $d);
        // directed by shizuki
        if ($y && $m && $d) {
            $archive = sprintf('%04d-%02d-%02d', $y, $m, $d);
        } elseif ($y && $m && !$d) {
            $archive = sprintf('%04d-%02d', $y, $m);
        }

        // pager check for ShowBlogs
        if (isset($HTTP_SERVER_VARS['QUERY_STRING'])) {
			$server_query_string = $HTTP_SERVER_VARS['QUERY_STRING'];
		}
        if (isset($_SERVER['QUERY_STRING'])) {
			$server_query_string = $_SERVER['QUERY_STRING'];
		}
        if ($server_query_string) {
            $GETArray = explode('&', $server_query_string);
            if ($get_var_page) {
                $GETArray = array_diff($GETArray, array('page=' . $get_var_page));
                $GETArray = array_merge($GETArray, array('page=' . intGetVar('page')));
            }
            $GETArray = array_map('htmlspecialchars', $GETArray);
            $server_query_string = implode('&', $GETArray);
            $HTTP_SERVER_VARS['QUERY_STRING'] = $server_query_string;
            $_SERVER['QUERY_STRING'] = $server_query_string;
        }
        if (isset($HTTP_SERVER_VARS['REQUEST_URI'])) {
			$server_request_uri = $HTTP_SERVER_VARS['REQUEST_URI'];
		}
        if (isset($_SERVER['REQUEST_URI'])) {
			$server_request_uri = $_SERVER['REQUEST_URI'];
		}

        // checking REQUEST_URI
        if ($server_request_uri) {
            $URIArray = preg_split('/[?&]/', $server_request_uri);
            // setting var for proceding
            $server_script_name = array_shift($URIArray);
            // for XSS in any place
            $URIArray = array_map('htmlspecialchars', $URIArray);

            $server_query_string = implode('&', $URIArray);
            if ($server_query_string) {
                $GETArray = explode('&', $server_query_string);
                if ($get_var_page) {
                    $GETArray = array_diff($GETArray, array('page=' . $get_var_page));
                    $GETArray = array_merge($GETArray, array('page=' . intGetVar('page')));
                }
                $GETArray = array_map('htmlspecialchars', $GETArray);
                $server_query_string = implode('&', $GETArray);
            }
            // for fancyURL URI check REQUEST_URI if pager use it
            if (preg_match('/page[\/_0-9]/', $server_script_name)) {
                $pathArray = explode('/', $server_script_name);
                for ($i = 0; $i < count($pathArray); $i++) {
                    if ($pathArray[$i] === 'page') {
                        $i++;
                        if ($i < count($pathArray)) {
                            $pathArray[$i] = intVal($pathArray[$i]);
                            // ItemNaviEX breadcrumbslist fix
                            $_GET['page'] = intVal($pathArray[$i]);
                        }
                        $i++;
                        if ($i < sizeof($pathArray)) $trush = array_pop($pathArray);
                    }
                }
                $server_script_name = implode('/', $pathArray);
            }

            //for magicalURL searching page_ in $server_script_name
            if (strpos($server_script_name, 'page_') !== false) {
                $temp_info = explode('page_', $server_script_name);
                $server_script_name = $temp_info[0] . 'page_' . (int)$temp_info[1];
                // ItemNaviEX breadcrumbslist fix
                $_GET['page'] = (int)$temp_info[1];
                // for magical no use last slash
                $server_script_name = trim($server_script_name, '/');
            }
            $server_script_name = preg_replace('|[^a-z0-9-~+_.?#=&;/,:@%]|i', '', $server_script_name);
            if ($server_query_string) {
                $server_request_uri = $server_script_name . '?' . $server_query_string;
            } else {
                $server_request_uri = $server_script_name;
            }
            $HTTP_SERVER_VARS['REQUEST_URI'] = $server_request_uri;
            $_SERVER['REQUEST_URI'] = $server_request_uri;
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$_SERVER['HTTP_USER_AGENT'] = preg_replace('/[<>]/', ''
				, $_SERVER['HTTP_USER_AGENT']);
		}
        if (isset($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
			$HTTP_SERVER_VARS['HTTP_USER_AGENT'] = preg_replace('/[<>]/', ''
				, $HTTP_SERVER_VARS['HTTP_USER_AGENT']);
		}
        // for ItemNaviEX
        if (isset($_GET['page'])) {
			$_GET['page'] = intVal($_GET['page']);
		}
    }

    /**
     * redo magic
     * compare for getstring
     * @return string getOriginalString
     * @var $str
     */
    function _redoMagic($str)
    {
        return get_magic_quotes_gpc() ? addslashes($str) : $str;
    }
}
