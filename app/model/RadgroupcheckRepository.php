<?php
namespace Radgroupcheck;
use Nette;

/**
 * 
 * 
 * @author Jiri Eger <jiri@eger.us>
 */
class RadgroupcheckRepository extends Nette\Object
{
    /** @var \DibiConnection */
    private $db;

    public function __construct(\DibiConnection $connection)
    {
        $this->db = $connection;
    }
	
	/**
	 * Vraci data z tabulky radgroupcheck
	 */
    public function getRadgroupcheckData($values=NULL, $op='')
    {
		$fluent = $this->db->select('*')->from('radgroupcheck');
		if(isset($values))
		{
			$fluent = $fluent->where($op, $values)->fetchAll();
		}
		foreach ($fluent as $key) {
			$key->hash = md5(serialize($key));
		}
		return $fluent;
	}

	
	/**
	 * Vraci data z tabulky radgroupcheck
	 */
    public function getRadhuntgroupDataByGroup($id)
    {
		$fluent = $this->db->select('rh.*, g.groupname AS groupname')
								->from('[radhuntgroup] rh')
								->join('[radgroupcheck] g')
								->on('rh.groupname=g.groupname')
								->where('g.id=%i',$id);		
		return $fluent;
	}

	public function getRadhuntgroupData($values, $op='')
    {
		$fluent = $this->db->select('*')->from('radhuntgroup')
										->where($op, $values);
		return $fluent;
	}

	/**
	 * Vlozi data do tabulky radgroupcheck
	 */
	public function addRadgroupcheckData($values) {
		try {
			$this->db->insert('radgroupcheck', $values)->execute();
			return true;
		} catch (\DibiException $e) {
			return false;
		}
		
	}

	/**
	 * Vlozi data do tabulky radhuntgroup
	 */
	public function addRadhuntgroupData($values) {
		try {
			$this->db->insert('radhuntgroup', $values)->execute();
			return true;
		} catch (\DibiException $e) {
			return false;
		}
		
	}

	/**
	 * Maze data z tabulky radgroupcheck
	 */
	public function delRadgroupcheckData($row,$value) {
		try {
			$this->db->delete('radgroupcheck')->where($row.' = %s', $value)->execute();
			return TRUE;
		} catch (\DibiException $e) {
			return FALSE;
		}
	}
	

	/**
	 * Maze data z tabulky radhuntgroup
	 */
	public function delRadhuntgroupData($row,$value) {
		try {
			$this->db->delete('radhuntgroup')->where($row.' = %s', $value)->execute();
			return TRUE;
		} catch (\DibiException $e) {
			return FALSE;
		}
	}

	
	/**
	 * Aktualizace dat v tabulce radgroupcheck
	 */
	public function updateRadgroupcheckData($id,$values) {
		try {
			unset($values->id);
			unset($values->groupname);
			unset($values->hash);
			$this->db->update('radgroupcheck',$values)->where('id = %i', $id)->execute();
			return $this->db->affectedRows();
		} catch (\DibiException $e) {
			return FALSE;
		}
	}

	public function getGroupName($id) {
		try {
			return $this->db->select('groupname')->from('radgroupcheck')->where('id = %i', $id)->fetchSingle();
		} catch (\DibiException $e) {
			return FALSE;
		}
	}

	public function hasGroupChilds($groupname) {
		try {
			if ($this->db->select('groupname')->from('radusergroup')->where('groupname = %s', $groupname)->count())
			{
				return TRUE;
			} else {
				return FALSE;
			}
		} catch (\DibiException $e) {
			return TRUE;
		}
	}
	
}