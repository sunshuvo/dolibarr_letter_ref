<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/letterreference/mod_ssl_standard.php
 *  \ingroup    letterreference
 *  \brief      File of class to manage SSL numbering rules standard
 */
dol_include_once('/letterreference/core/modules/letterreference/modules_ssl.php');


/**
 *	Class to manage customer order numbering rules standard
 */
class mod_ssl_standard extends ModeleNumRefSSL
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	public $prefix='SSL';

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string name
	 */
	public $name='standard';


    /**
     *  Return description of numbering module
     *
     *  @return     string      Text with description
     */
    public function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
    }


	
	
	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
     *  Checks if the numbers already in force in the data base do not
     *  cause conflicts that would prevent this numbering from working.
	 *
	 *  @param  Object		$object		Object we need next value for
	 *  @return boolean     			false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		global $conf,$langs,$db;

		$coyymm=''; $max='';

		$posindice=4;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."letterreference_ssl";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."/%/__'";
		if ($object->ismultientitymanaged == 1) {
			$sql.= " AND entity = ".$conf->entity;
		}
		elseif ($object->ismultientitymanaged == 2) {
			// TODO
		}

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $coyymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($coyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
	public function getNextValue($object)
	{
		global $db,$conf;

		// D'abord on recupere la valeur max
		$posindice=5;
		$sql = "SELECT MAX(rowid) as maxid";
		$sql.= " FROM ".MAIN_DB_PREFIX."letterreference_ssl_seq";
		//$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."/%/__'";
		if ($object->ismultientitymanaged == 1) {
			$sql.= " AND entity = ".$conf->entity;
		}
		elseif ($object->ismultientitymanaged == 2) {
			// TODO
		}

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->maxid);
				$sql3 = "Insert into ".MAIN_DB_PREFIX."letterreference_ssl_seq () values (null)"; $resq3=$db->query($sql3);
				if($max==0){$max=1;}
			}
			else $max=1;
		}
		else
		{
			dol_syslog("mod_ssl_standard::getNextValue", LOG_DEBUG);
			return -1;
		}
		
		
		//$date=time();
		$date=$object->date_creation;
		$yymm = strftime("%y%m", $date);
		$yy = strftime("%y", $date);	
		
		//Reset sequence table
		$sql2 = "SELECT MAX(CAST(SUBSTRING(ref FROM 7 For 2) AS SIGNED)) as year FROM ".MAIN_DB_PREFIX."letterreference_ssl";
		$resq2=$db->query($sql2);
		if ($resq2)
		{
			$obj = $db->fetch_object($resq2);
			if ($obj){ $year = intval($obj->year);}
			if($year!=$yy){
				$sql5 = "TRUNCATE TABLE llx_letterreference_ssl_seq";$db->query($sql5);
				$sql6 = "DROP TABLE llx_letterreference_ssl_seq";$db->query($sql6);
				$sql7 = "CREATE TABLE llx_letterreference_ssl_seq (rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL)";$db->query($sql7);
				$sql4 = "ALTER TABLE llx_letterreference_ssl_seq AUTO_INCREMENT = 2";$db->query($sql4);

				$max=0;
			}
		
		}  
		

		
    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s", $max+1);

		dol_syslog("mod_ssl_standard::getNextValue return ".$this->prefix."/LT".$yy.$num);
		return $this->prefix."/LT".$yy.$num;
	}
}
