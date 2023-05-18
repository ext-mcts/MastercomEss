<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Configemail_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.configemails";
	}

    public function update_config_email($id)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.configemails` SET Status=1 WHERE id=$id";
        $this->db->query($sql);

        $sql = "UPDATE `mcts_extranet`.`dbo.configemails` SET Status=2 WHERE id!=$id";
        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;

    }
}