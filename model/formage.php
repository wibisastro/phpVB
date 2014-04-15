<?
/*
Author		: Wibisono Sastrodiwiryo
Date		: 23-Nov-06, 16:4  
Copyleft	: eGov Lab UI
Contact		: wibi@alumni.ui.ac.id
Version		: 1.0.1

# ---- ver 1.1, 25-Dec-06, ubah help dari popup java ke text biasa
# ---- ver 1.1.1, 26-Dec-06, tambah prefix name pada listbox_date
# ---- ver 1.1.2, 26-Dec-06, perbaiki validator untuk listbox_date
# ---- ver 1.1.3, 2-Apr-07, tambah listbox_hour dan listbox_minute
# ---- ver 1.1.4, 16-Apr-07, tambah getgroup
*/

class formage extends db_connection {
	function validator($exec="",$name="",$caption="",$type="") {
		static $counter,$fields;
		$counter++;
		if ($exec) {
			$result="<script language=javascript>
			function Validator(theForm) {
				";
			if (is_array($fields)) {
				while (list($key,$val)=each($fields)) {
					if ($val[type] == "checkbox") {
						$result.="if (theForm[\"$val[name]\"].checked == 0)
						{
							alert(\"Field \\\" $val[caption] \\\" is still Empty\");
							theForm[\"$val[name]\"].focus();
							return (false);
						}
						";
					} elseif ($val[type] == "date" || $val[type] == "array" || $val[type] == "dbrow" || $val[type] == "fn" ) {
						$result.="if (theForm.$val[name].selectedIndex == 0)
						{
							alert(\"Field \\\" $val[caption] \\\" is still Empty\");
							theForm.$val[name].focus();
							return (false);
							}
						";
					} elseif ($val[type] == "radio") {
						$result.="  var i,cek;
						  for (var i = 0; i < theForm.$val[name].length; i++) {
						   if (theForm.".$val[name]."[i].checked == 0) {cek = false;}
						   else {cek = true; i=theForm.$val[name].length;} 
						  }
						  if (cek == false) {alert(\"Belum pilih $val[caption]\");theForm.".$val[name]."[0].focus(); return (false);}
						";
					} else {
						$result.="if (theForm.$val[name].value == \"\")
						{
							alert(\"Field \\\" $val[caption] \\\" is still Empty\");
							theForm.$val[name].focus();
							return (false);
							}
							";
					}
				}
			}
			if ($exec == 1) {
				$result.="return (true);
				";
				$result.="}
				</script>
				";
			}
		} else {
			$counter++;
			$fields[$counter][name]=$name;
			$fields[$counter][type]=$type;
			$fields[$counter][caption]=$caption;
		}
	return $result;
	}

	function button($button, $onclick="",$class="formbutton") {
		if (is_array($button)) {
			while (list($key,$val)=each($button)) {
				$result.="<input type=\"submit\" name=\"cmd\" value=\"$val\" ";
				if ($onclick) {$result.="onclick=\"return $onclick\"";}
				$result.=" class=\"$class\" />\n";
			}
		} else {
			$result.="<input type=\"submit\" name=\"cmd\" value=\"$button\" ";
			if ($onclick) {$result.="onclick=\"return $onclick\"";}
			$result.=" class=\"$class\" />\n";
		}
	return $result;
	}

	function button_java($button,$onclick="",$class="formbutton",$name="") {
		if ($name) {$name="name=\"$name\" id=\"$name\"";}
		if (is_array($button)) {
			while (list($key,$val)=each($button)) {
				$result.="<input type=\"button\" $name value=\"$val\" ";
				if ($onclick) {$result.="onclick=\"return $onclick\"";}
				$result.=" class=\"$class\" />\n";
			}
		} else {
			$result.="<input type=\"button\" $name value=\"$button\" ";
			if ($onclick) {$result.="onclick=\"return $onclick\"";}
				$result.=" class=\"$class\" />\n";
		}
	return $result;
	}

	function checkbox ($name, $value, $checked="",$java="") {
		$result ="<input type=\"checkbox\" ";
		$result.="name=\"$name\" ";
		$result.="value=\"$value\" ";
		if ($checked) {$result.="checked";}
		$result.=" $java />";
	return $result;
	}

	function checkbox_checker ($fields, $checked) {
		$data1=explode(",", $fields);
		$data2=explode(",", $checked);
		while (list($key,$val)=each($data1)) {
			reset($data2);
			while (list($key1,$val1)=each($data2)) {
				if ($val == $val1) {$result[$val]=1;break;}
			}
		}
	return $result;
	}

	function form ($action, $hidden, $onsubmit="",$name="",$target="") {
		if (!$name) {$name="theForm";}
		if ($target) {$target="target=\"$target\"";}
		$result="<form $target action=\"$action\" method=\"post\" id=\"$name\" name=\"$name\" ";
		if ($onsubmit) {$result.="onsubmit=\"return $onsubmit\"";}
		$result.="class=\"cssform\">\n";
		if ($hidden) {
			while (list($key,$val)=each($hidden)) {$result.="<input type=\"hidden\" name=\"$key\" value=\"$val\" />\n";}
		}
	return $result;
	}

	function listbox_array ($name, $list, $default="", $associative="",$class="") {
		if (!$class) {$class="formfield";}
		$result="<select name=\"$name\" size=\"1\" class=\"$class\">\n";
		$result.="<option value=\"\"></option>\n";
		while (list($key, $val) = each($list)) {
			if ($associative) {
				if ($default  == $key) {$selected="selected=\"selected\"";} else {$selected="";}
				$result.="<option value=\"$key\" $selected>$val</option>\n";
			} else {
				if ($default  == $val) {$selected="selected=\"selected\"";} else {$selected="";}
				$result.="<option value=\"$val\" $selected>$val</option>\n";
			}
		}
		$result.="</select>\n";
	return $result;
	}

	function listbox_field ($mysql_link, $name, $default=0) {
		$result="<select name=\"$name\" size=\"1\" class=\"formfield\">\n";
		$result.="<option value=\"\">select $name</option>\n";
		while (list($key,$val)=mysql_fetch_array($mysql_link)) {
			if ($default  == $key) {$selected="selected";} else {$selected="";}
			$result.="<option value=\"$key\" $selected>$val</option>\n";
		}
		$result.="</select>\n";
	return $result;
	}

	function listbox_date ($name, $default=0) {
		$result="<select name=\"$name\" size=\"1\" class=\"formfield\">\n";
			$result.="<option value=\"0\" >date</option>\n";
		for ($d=1;$d<=31;$d++) {
			if ($default  == $d) {$selected="selected=\"selected\"";} else {$selected="";}
			$result.="<option value=\"$d\" $selected>$d</option>\n";
		}
		$result.="</select>\n";
	return $result;
	}

	function listbox_month ($name, $default=0) {
		$result="<select name=\"$name\" size=\"1\" class=\"formfield\">\n";
			$result.="<option value=\"0\" >month</option>\n";
		for ($m=1;$m<=12;$m++) {
			if ($default  == $m) {$selected="selected=\"selected\"";} else {$selected="";}
			$result.="<option value=\"$m\" $selected>".date(M, mktime(0,0,0,$m,1,2000))."</option>\n";
		}
		$result.="</select>\n";
	return $result;
	}

	function listbox_year ($name, $start, $end, $default=0) {
		if (!$default) $default=date("Y");
		$result="<select name=\"$name\" size=\"1\" class=\"formfield\">\n";
			$result.="<option value=\"0\" >year</option>\n";
		for ($y=$start;$y<=$end;$y++) {
			if ($default  == $y) {$selected="selected=\"selected\"";} else {$selected="";}
			$result.="<option value=\"$y\" $selected>$y</option>\n";
		}
		$result.="</select>\n";
	return $result;
	}

	function listbox_hour ($name, $default=0) {
		$result="<select name=\"$name\" size=\"1\" class=\"formfield\">\n";
			$result.="<option value=\"0\" >hh</option>\n";
		for ($d=1;$d<=24;$d++) {
			if ($default  == $d) {$selected="selected=\"selected\"";} else {$selected="";}
			$result.="<option value=\"$d\" $selected>$d</option>\n";
		}
		$result.="</select>\n";
	return $result;
	}

	function listbox_minute ($name, $default=0) {
		$result="<select name=\"$name\" size=\"1\" class=\"formfield\">\n";
			$result.="<option value=\"0\" >mm</option>\n";
		for ($d=1;$d<=60;$d++) {
			if ($default  == $d) {$selected="selected=\"selected\"";} else {$selected="";}
			$result.="<option value=\"$d\" $selected>$d</option>\n";
		}
		$result.="</select>\n";
	return $result;
	}

	function radio ($name, $value, $checked="") {
		$result ="<input type=\"radio\" ";
		$result.="name=\"$name\" ";
		$result.="value=\"$value\" ";
		if ($checked) {$result.="checked";}
		$result.=">\n";
	return $result;
	}

	function radio_checker ($fields, $checked) {
		$data=explode(",", $fields);
		while (list($key,$val)=each($data)) {
			if ($val == $checked) {$result[$val]=1;}
			else {$result[$val]=0;}
		}
	return $result;
	}

	function textarea ($name, $row=5, $cols=20, $value="", $maxlength="",$formname="") {
		if (!$formname) {$formname="theForm";}
		$result.="<textarea name=\"$name\" ";
		$result.="rows=\"$row\" ";
		$result.="cols=\"$cols\" ";
		if ($maxlength) {
			$result.="onChange=\"check_length(this, $maxlength)\" ";
			$result.="onKeyUp=\"check_length(this, $maxlength)\" ";
		}
		$result.=" class=\"formfield\">$value</textarea>";
	return $result;
	}

	function textbox ($type, $name, $size=0, $maxlength=0, $value="",$java="",$class="formfield") {
		$result ="<input type=\"$type\" ";
		$result.="name=\"$name\" ";
		$result.="id=\"$name\" ";
		$result.="size=\"$size\" ";
		$result.="maxlength=\"$maxlength\" ";
		if ($value) {$result.="value=\"$value\"";}
		if (!$class) {$class="formfield";}
		$result.="$java class=\"$class\" />";
	return $result;
	}

	function listbox_filter($name) {
		global $PHP_SELF,$page;
		$result="<script language=JavaScript>
		function $name(form) {
			var go = (form.options[form.selectedIndex].value);
			location.href=\"$PHP_SELF?page=$page&cmd=Filter&column=$name&filter=\"+go;
		}
		window.onerror=null;
		</script>";
	return $result;
	}
}
?>