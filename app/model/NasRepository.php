<?php
namespace Nas;
use Nette;

/**
 * 
 * 
 * @author Jiri Eger <jiri@eger.us>
 */
class NasRepository extends Nette\Object
{
    /** @var \DibiConnection */
    private $db;

    public function __construct(\DibiConnection $connection)
    {
        $this->db = $connection;
    }
	
	/**
	 * Vraci data z tabulky nas
	 */
    public function getNasData($values=NULL, $op='')
    {
		$fluent = $this->db->select('*')->from('nas');
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
	 * Vlozi data do tabulky nas
	 */
	public function addNasData($values) {
		try {
			$this->db->insert('nas', $values)->execute();
			return true;
		} catch (\DibiException $e) {
			return false;
		}
		
	}
	
	/**
	 * Maze data z tabulky nas
	 */
	public function delNasData($row,$values) {
		try {
			$this->db->delete('nas')->where($row)->in($values)->execute();
			return TRUE;
		} catch (\DibiException $e) {
			return FALSE;
		}
	}
	
	/**
	 * Aktualizace dat v tabulce nas
	 */
	public function updateNasData($id,$values) {
		try {
			unset($values->id);
			unset($values->hash);
			$this->db->update('nas',$values)->where('id = %i', $id)->execute();
			return $this->db->affectedRows();
		} catch (\DibiException $e) {
			return FALSE;
		}
	}
	
}