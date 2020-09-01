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
class NP_0PatchBlogid extends NucleusPlugin
{
    function getEventList()
    {
        return array('PreSendContentType');
    }

    function event_PreSendContentType($arg)
    {
        global $blogid, $archivelist, $archive;

        if (strtolower($arg['pageType']) !== 'skin') {
			return;
		}

        $get_var_page = $this->_redoMagic(getVar('page'));

        if (is_numeric($blogid)) {
			$blogid = intVal($blogid);
		} else {
			$blogid = getBlogIDFromName($blogid);
		}

        if (!is_numeric($archivelist)) {
			$archivelist = getBlogIDFromName($archivelist);
		}
        if ($archivelist) {
			$blogid = $archivelist;
		}

        sscanf($archive, '%d-%d-%d', $y, $m, $d);
        if ($y && $m && $d) {
            $archive = sprintf('%04d-%02d-%02d', $y, $m, $d);
        } elseif ($y && $m && !$d) {
            $archive = sprintf('%04d-%02d', $y, $m);
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
            $_SERVER['QUERY_STRING'] = $server_query_string;
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
            if (preg_match('/page[\/_0-9]/', $server_script_name)) {
                $pathArray = explode('/', $server_script_name);
                foreach ($pathArray as $i => $iValue) {
                    if ($iValue === 'page') {
                        $i++;
                        if ($i < count($pathArray)) {
                            $pathArray[$i] = intVal($iValue);
                            $_GET['page'] = intVal($iValue);
                        }
                        $i++;
                        if ($i < sizeof($pathArray)) $trush = array_pop($pathArray);
                    }
                }
                $server_script_name = implode('/', $pathArray);
            }

            if (strpos($server_script_name, 'page_') !== false) {
                $temp_info = explode('page_', $server_script_name);
                $server_script_name = $temp_info[0] . 'page_' . (int)$temp_info[1];
                $_GET['page'] = (int)$temp_info[1];
                $server_script_name = trim($server_script_name, '/');
            }
            $server_script_name = preg_replace('|[^a-z0-9-~+_.?#=&;/,:@%]|i', '', $server_script_name);
            if ($server_query_string) {
                $server_request_uri = $server_script_name . '?' . $server_query_string;
            } else {
                $server_request_uri = $server_script_name;
            }
            $_SERVER['REQUEST_URI'] = $server_request_uri;
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$_SERVER['HTTP_USER_AGENT'] = preg_replace('/[<>]/', ''
				, $_SERVER['HTTP_USER_AGENT']);
		}
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

}
