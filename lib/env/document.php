<?php namespace Gov2lib\env;
/********************************************************************
*	Date		: Sunday, November 22, 2009
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 2 -> 27-Mar-07, 23:17 

# ---- ver 2.1, 02-Apr-07, gabung head dan body ke doc
# ---- ver 2.2, 29-Apr-09, tambah mainmenu dropdown
# ---- ver 2.3, 25-Agu-09, tambah pagetitle di navpath
# ---- ver 2.4, 28-Sep-09, tambah buildsql
# ---- ver 2.5, 22-Nov-09, tambah buildcolumn
# ---- ver 2.6, 28-Jul-11, tambah parse_request
# ---- ver 2.7, 21-Sep-11, perbaikan error_message
# ---- ver 3.0, 15 April 2014, downgrade untuk publikasi kpu
# ---- ver 4.0, 21 September 2017, menggunakan namespace untuk dipakai dengan standard PSR-4
*/

class document {
	function __construct () {
		global $ses;
		$this->body=array();
		$this->body['_SESSION']=$_SESSION;
		$this->body['_SERVER']=$_SERVER;
//		$this->leftside("general/sidenav.php");
/*
		if ($ses->cookies["item_perpage"]) {$this->item_perpage=$ses->cookies["item_perpage"];}
		else {$this->item_perpage=12;}
		$this->querystring(getenv("QUERY_STRING"));
		*/
	}
	
	function body ($var,$val) {
		$this->body[$var]=$val;
	} 

	function lnk ($link, $text="", $class="", $java="",$title="",$target="",$id="") {
		if ($class) {$class=" class=\"$class\"";}
		if (!$text) {$text=$link;}
		if ($target) {$target=" target=\"$target\"";}
		if ($title) {$title=" title=\"$title\"";}
		if ($id) {$id=" id=\"$id\"";}
		return "<a href=\"$link\"$class $java$title$target$id>$text</a>";
	}

	function txt ($key,$style="") {
		#---24-Dec-06, 15:0 -> tambah paramater $class
		#---25-Dec-06, 14:15 -> perbaiki fungsi 
		#---15 April 2014 -> downgrade gak kepake dulu
		global $tbl_text;
		list($db_server_id,$db_name)=$this->connect_db();
		if ((INT)$key) {$where="WHERE text_id=$key";}
		else {$where="WHERE name='$key'";}
		$query ="SELECT text,text_id,type FROM $tbl_text $where";
		$buffer=$this->read_db($db_name, $query,$db_server_id) or die("text:".mysql_error());
		$result=mysql_fetch_object($buffer);
		if ($result->text) {
			if ($style) {$result="<span class=$style>$result->text</span>";}
			elseif ($result->type != "button" && $result->type != "label") {$result="<span class=\"$result->type\">$result->text</span>";}
			else {$result=$result->text;}
		} else {$result="<sub>notexist:</sub> $key";}
	return $result;
	}

	function img ($src="", $alt="", $url="") {
		if ($url) {return "<img border=\"0\" src=\"$url\" alt=\"$alt\" />";}
		else {return "<img border=\"0\" src=\"".imgurl."/$src\" alt=\"$alt\" />";}
	}

	function querystring($querystring) {
		if ($querystring) $qsses=explode("&", $querystring);
		for ($a=0;$a<=sizeof($qsses)-1;$a++) {$qsitem=explode("=", $qsses[$a]);$qsarray[$qsitem[0]]=$qsitem[1];}
		$a=0;
		if (is_array($qsarray)) {
			while (list($key,$val)=each($qsarray))  {
				if ($key != "page" && $key != "seq") {$qsresult[$a]=$key."=".$val;$a++;}
			}
			if (is_array($qsresult)) {$this->querystring=implode("&", $qsresult);}
		}
	}

	function leftside($val) {
		global $doc;
		$index=sizeof($this->leftside);
		$index++;
		if (file_exists(viwpath."/$val")) {$this->leftside[$index]=viwpath."/$val";}
		else {$this->leftside[$index]=$val;}
	}

	function rightside($val,$predefine="") {
		static $index;
		if (!$index) {$index=5;}
		if ($predefine) {$index=$predefine;}
		$index++;
		if (file_exists(viwpath."/$val")) {$this->rightside[$index]=viwpath."/$val";}
		else {$this->rightside[$index]=$val;}
	}

	function content($val) {
		$index=sizeof($this->content);
		$index++;
		if (file_exists(viwpath."/$val")) {$this->content[$index]=viwpath."/$val";}
		else {$this->content[$index]=$val;}
	}

	function tab($val) {
		$index=sizeof($this->tab);
		$index++;
		$this->tab[$index]=viwpath."/$val";
	}

	function strip_java($data) {
		return eregi_replace("<[ \n\r]*script[^>]*>.*<[ \n\r]*/script[^>]*>","",$data);
	}

	function error_message ($message="") {
		global $ses;
		if ($ses->error || $this->error) {
			if ($ses->error && !$this->doctype) {
				if ($ses->error=="NotLogin") {
					if (!$this->pagetitle) {
						$this->pagetitle="Please Login First...";
					}
				} else {
					$this->error=$ses->error;
					$this->error_message=sprintf(strip_tags($this->txt($ses->error)),$message);
					$this->pagetitle="Authentication Failed";
				}
				if ($ses->error!="NotAuthorized") {
					$this->button=array("Login");
				}
			} elseif ($this->doctype=="public" && $ses->error!="NotLogin") {
				if ($ses->error=="NotMember") {
					unset($this->doctype);
					$this->status="Please Connect";
				}
				$this->error=$ses->error;
				$this->error_message=sprintf(strip_tags($this->txt($ses->error)),$message);
			} elseif ($this->doctype=="activation" && !$this->error) {
//				$this->rightside("general/login.php",1);
			} elseif ($this->doctype=="public" && $ses->error=="NotLogin") {
//				$this->rightside("general/login.php",1);
			} else {
				$this->pagetitle="Operation Failed";

				$this->error_message=sprintf(strip_tags($this->txt($this->error)),$message);
			}
		}
	}

	function error_message_ajax ($div="") {
		global $ses;
		if (!$div) {$div="tab_alert";}
		$this->div_error=$div;
		if ($ses->error || $this->error) {
			if ($ses->error) {
				$this->action=$this->subdomainurl."/login.php";
				$this->button=array("Login");
				$this->error=$ses->error;
				$this->error_message=$this->txt("Error$ses->error");
			} else {
				$this->error=$this->error;
				$this->error_message=$this->txt("Error$this->error");
			}
		}
	}

	function error_message_search ($message="") {
		global $ses;
		if ($ses->error || $this->error) {
			if ($ses->error) {
				$this->error=$ses->error;
				$this->error_message=sprintf(strip_tags($this->txt($ses->error)),$message);
			} else {
				$this->error_message=sprintf(strip_tags($this->txt($this->error)),$message);
			}
		}
	}

	function sitemap ($parent) {
		$parent+=0;
		$index=$this->sitemap_thread($parent);
		if (is_array($index)) {
			$result.="<ul>\n";
			while (list($key, $val) = each($index)) {
				$result.="<li>$val";$c++;
				if ($c < sizeof($index) + 1) {$result.=$this->sitemap($key);} else {break;}
				$result.="</li>\n";
			}
			$result.="</ul>\n";
		}
	return $result;
	}

	function pagination($records,$target="",$url="") {
		global $page,$PHP_SELF,$header;
		if (!$url) {$url=$PHP_SELF;}
		$page+=0;
		$pages=floor($records/$this->item_perpage);
		$mod=$records%$this->item_perpage;
		if ($mod) $pages++;
		if (!$page) $page++;
		if ($page>$pages) {$page=$pages;}

#-----------Paging 

		for ($a=1;$a<=$pages;$a=$a+5) {
			if ($page>=$a && $page<=$a+4) {
				$int_start=$a;
				$int_end=$a+4;
				if ($int_end>$pages) $int_end=$pages;
			}
		}

		$querystring=$this->querystring;
		if ($querystring) {$querystring="&$querystring";}

		for ($p=$int_start;$p<=$int_end;$p++) {
			if ($page==$p) {
				$pagelink[$p]="<span class=\"menu\"><u>$p</u></span>";
			} else {
				$pagelink[$p]=$this->lnk("$url?page=$p$querystring",$p,"menucurrent","","",$target);
			}
		}

		if (is_array($pagelink)) $paging=implode("&nbsp;&nbsp;",$pagelink);

#-----------Big Scroll 

		if ($int_start>1) {
			$prev_scroll=$int_start-1;
			$prev_scroll=$this->lnk("$url?page=$prev_scroll$querystring",$this->img("pointer_back_fast.gif")."&nbsp;","menucurrent","","",$target);
			$start_page=$this->lnk("$url?page=1$querystring","&nbsp;1&nbsp;","menucurrent","","",$target);
		}
		if ($int_end<$pages) {
			$next_scroll=$int_end+1;
			$next_scroll=$this->lnk("$url?page=$next_scroll$querystring","&nbsp;".$this->img("pointer_fast.gif"),"menucurrent","","",$target);
			$end_page=$this->lnk("$url?page=$pages$querystring",$pages,"menucurrent","","",$target);
		}

#-----------Page Scroll 
		$prev_page=$page-1;
		$next_page=$page+1;

		if ($page>1) {$page_nav =$this->lnk("$url?page=$prev_page$querystring",$this->img("pointer_back.gif")." Prev","menucurrent","","",$target);}
		else {$page_nav.="<font color=\"silver\">".$this->img("pointer_back.gif")." Prev</font>";}
		$page_nav.="&nbsp;|&nbsp;";
		if ($page<$pages) {$page_nav.=$this->lnk("$url?page=$next_page$querystring","Next ".$this->img("pointer.gif"),"menucurrent","","",$target);}
		else {$page_nav.="<font color=\"silver\">Next ".$this->img("pointer.gif")."</font>";}

		if (!$page) {$page=1;}
		$start=($page-1)*$this->item_perpage+1;
		$end=$start+$this->item_perpage-1;
		if ($end>$records) {$end=$records;}

		$result ="<table width=100% border=0 cellpadding=0 cellspacing=0><tr><td nowrap><span class=\"str\">Item $start-$end / $records</span></td>";
		if ($records>$this->item_perpage) $result.="<td width=100% nowrap><div align=\"center\"> <span class=\"str\">Page:</span><span class=\"menu\">$start_page $prev_scroll $paging $next_scroll $end_page</span></div></td>";
		if ($records>$this->item_perpage) $result.="<td align=right nowrap><span class=menu>$page_nav</span></td>";
		$result.="</tr></table>";
	if ($records>0) return $result;
	}

	function sortlist($sort,$page_id="") {
		global $ses;
		if (!$ses->cookies["sortmethod_$page_id"]) {$ses->cookies["sortmethod_$page_id"]="ASC";}
		elseif ($ses->cookies["sortmethod_$page_id"]=="ASC") {$ses->cookies["sortmethod_$page_id"]="DESC";}
		else {$ses->cookies["sortmethod_$page_id"]="ASC";}
		$ses->cookies["sort_$page_id"]=$sort;
		$ses->save_cookie();
	}

	function set_pagerow($item_perpage){
		global $ses;
		$ses->cookies["item_perpage"]=$item_perpage;
		$ses->save_cookie();
	}

	function filter($column,$val,$page_id=""){
		global $nav_id,$ses;
		if ($page_id) {$nav_id=$page_id;}
		if ($val=="all") {$val="";}
		$ses->cookies["filter_$nav_id"."_$column"]=$val;
		$ses->save_cookie();
	}

	function menu_editdel($page=1,$key,$val,$item_del,$url){
		$result.="<td align=\"center\">";
		$result.=$this->lnk("$url?cmd=edit&page=$page&$key=$val", "Edit","menu")."<br \>";
		$result.=$this->lnk("$url?cmd=del&page=$page&$key=$val", "Del","menu");
		$result.="</td>";
	return $result;
	}

	function selectall($name,$value) {
		global $field;
		$result="
				<a href=\"javascript:setCheckboxes(true)\" class=menu>All</a> / 
				<a href=\"javascript:setCheckboxes(false)\"  class=menu>Empty</a>
				&nbsp;".$this->img("pointer_up.gif")."&nbsp;
				 <SCRIPT LANGUAGE=\"JavaScript\">
				<!--
				function setCheckboxes(do_check)
				{
					var elts      = document.".$field->formname.".elements['$name'];
					var elts_cnt  = elts.length;
					for (var i = 0; i < elts_cnt; i++) {
						elts[i].checked = do_check;
					} 
				} 
				//-->
				</SCRIPT>
			";
	return $result;
	}

	function table_panel($name,$val,$button) {
		global $field;
		$result="<table width=\"100%\" cellpadding=0 cellspacing=0>
			  <tr>
				<td>".$this->setpage_row()."</td>
				<td align=\"right\">".$field->button($button).$this->selectall($name,$val)."</td>
			  </tr>
			  </table>";
		return $result;
	}

	function setpage_row() {
		global $PHP_SELF,$ses;
		$result="<script language=\"JavaScript\">
		function setPageRow(form) {
			var go = (form.options[form.selectedIndex].value);
			location.href=\"$PHP_SELF?cmd=SetPageRow&item_perpage=\"+go;
		}
		window.onerror=null;
		</script>";
		for ($a=9;$a<=99;$a=$a+10) {
			if ($ses->cookies["item_perpage"] == $a) {$selected="selected";}
			else {$selected="";}
			$b.="<option value=$a $selected>$a</option>";
		}
		$result.="<select onChange=\"setPageRow(this)\" class=\"formfield\">$b</select> ";
		$result.="<span class=\"menu\">Item per Page</span> ";
	return $result;
	}

	function sort_method($sortby,$table){
		global $ses;
		if ($sortby==$ses->cookies["sort_$table"]) {
			if ($ses->cookies["sortmethod_$table"]=="ASC") {$result=$this->img("pointer_up.gif");}
			else {$result=$this->img("pointer_down.gif");}
		}
	return $result;
	}

	function breadcrumb($data,$prefix) {
		global $PHP_SELF,$doc;
		ksort($data);
		$prefix=substr($prefix,0,-6);
		$result="$prefix » ";
		while (list($key,$val)=each($data)) {
			$c++;
			$result.=$doc->lnk("$PHP_SELF?cmd=$val[cmd]&$val[qs]",$val["caption"]);
			if ($c < sizeof($data)) {$result.=" » ";}
		}
		$result.="</div>";
	return $result;
	}	
}
?>