<?php

/**
 * MY_Model
 *
 * @author localdisk <info@localdisk.org>
 * @property CI_DB_active_record $db
 */
class MY_Model extends CI_Model {

    /**
     * table name
     * 
     * @var string
     */
    protected $_table;

    /**
     * This model's default primary key or unique identifier.
     * Used by the get(), update() and delete() functions.
     */
    protected $_primary_key = 'id';

    /**
     * constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $clazz = get_class($this);
        $this->_table = strtolower($clazz) . 's';
    }

    /**
     * insert
     * 
     * @return integer 
     */
    public function insert() {
        $now = $this->now();
        $this->db->set(array('created_at' => $now, 'updated_at' => $now));
        $ret = $this->db->insert($this->_table, $this);
        if ($ret === FALSE) {
            return FALSE;
        }
        return $this->db->insert_id();
    }

    /**
     * update
     * 
     * @param integer|string $id
     */
    public function update($id, $data = null) {
        if ($data === null) {
            $data = $this;
        }
        $ret = $this->db->update($this->_table, $data, array($_primary_key => $id));
        if ($ret === FALSE) {
            return FALSE;
        }
    }

    /**
     * delete
     * 
     * @param integer|strng $id 
     */
    public function delete($id) {
        $this->db->delete($this->_table, array($_primary_key => $id));
    }

    /**
     * find_all
     * 
     * @return array
     */
    public function find_all() {
        return $this->db->get($this->_table)->result();
    }

    /**
     * find_list
     * 
     * @param  integer|string $limit
     * @return array
     */
    public function find_list($limit = 10) {
        return $this->db->limit($limit)->order_by($_primary_key)->get($this->_table)->result();
    }

    /**
     * find
     * 
     * @param  integer|string $id
     * @return stdClass
     */
    public function find($id) {
        $ret = $this->db->where(array($this->_primary_key => $id))->get($this->_table)->row();
        return $ret;
    }

    /**
     * now
     * 
     * @return string
     */
    public function now() {
        return date('Y-m-d H:i:s');
    }
}