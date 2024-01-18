<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Documents_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.documents";
	}

    public function create_docs($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.documents` (EmployeeID,Doc_name,Doc_path,Doc_type) 
                VALUES ('".$data['EmployeeID']."','".$data['Doc_name']."','".$data['Doc_path']."','".$data['Doc_type']."')";
        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    public function get_document($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.documents` WHERE Document_ID=$id";
        $query=$this->db->query($sql);
		return $query->row();
    }

    public function delete_document($id)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.documents` WHERE Document_ID='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function get_emp_docs($empid)
    {
        $sql = "SELECT dt.name as doctype, Document_ID,Doc_name,Doc_path FROM mcts_extranet.`dbo.documents` d
                LEFT JOIN mcts_extranet.`dbo.documenttypes` dt ON d.Doc_type=dt.id
                WHERE EmployeeID='$empid'";
        $query=$this->db->query($sql);
		return $query->result();
    }

    // create doc suhas
    public function create_doc($data,$empid)
    {
        // $sql = "INSERT INTO `mcts_extranet`.`dbo.documents` (EmployeeID,Doc_name,Doc_path,Doc_type) 
        //         VALUES ('".$empid."','".$data['Doc_name']."','".$data['Doc_path']."','".$data['Doc_type']."')";
        // $query=$this->db->query($sql);
        // if($query)
        //     return true;
        // else
        //     return false;

        // File_type doc_bs64string doc_detail doc_name


        foreach ($data as $document) {
            // Assuming $document is an object with properties like Doc_name, Doc_path, Doc_type
        
            // Construct the SQL query for each object
        if ($document['File_type']!=null) {
            $sql = "INSERT INTO `mcts_extranet`.`dbo.documents` (EmployeeID, Doc_name, Doc_type, File_type, Doc_bs64string) 
                        VALUES ('" . $empid . "','" . $document['doc_name'] . "','" . $document['doc_detail'] . "','" . $document['File_type'] . "','" . $document['doc_bs64string'] . "')";
        
            // Execute the query for each object
            $query = $this->db->query($sql);
            if (!$query) {
                // Handle error if needed
                return false;
            }
        }
        
            // Check if the query was successful for each iteration
        }
        
            // If the loop completes without errors, return true
        return true;
    }

    public function get_doc($empid){
        $sql = "SELECT Document_ID as id, File_type, Doc_bs64string as doc_bs64string, Doc_type as doc_detail, Doc_name as doc_name FROM `mcts_extranet`.`dbo.documents` WHERE EmployeeID = '$empid'";
        $query=$this->db->query($sql);
		return $query->result();
    }
    // UploadedFile
    public function update_doc($UploadedFileData,$newFileData,$empId){
        // $sql = "SELECT Document_ID as id, File_type, Doc_bs64string as doc_bs64string, Doc_type as doc_detail, Doc_name as doc_name FROM `mcts_extranet`.`dbo.documents` WHERE EmployeeID = '$empid'";
        // $sql = "UPDATE `mcts_extranet`.`dbo.documents` SET `Doc_name` = '', `File_type` = '', `Doc_bs64string` = '' WHERE (`Document_ID` = '')"
        // $query = $this->db->query($sql);
		// return $query->result();
        foreach ($UploadedFileData as $document) {
            // Assuming $document is an object with properties like Doc_name, Doc_path, Doc_type
        
            // Construct the SQL query for each object
            $sql = "UPDATE `mcts_extranet`.`dbo.documents` SET `Doc_name` = '" . $document['doc_name'] . "', `File_type` = '" . $document['File_type'] . "',
             `Doc_bs64string` = '" . $document['doc_bs64string'] . "' WHERE (`Document_ID` = '" . $document['id'] . "')";

            // Execute the query for each object
            $query = $this->db->query($sql);
        
            // Check if the query was successful for each iteration
            // if (!$query) {
            //     // Handle error if needed
            //     return false;
            // }
        }
        if (count($UploadedFileData)>0) {
            # code...
            $this->delete_docs($UploadedFileData,$empId);
        }
        if (count($newFileData)>0) {
            # code...
            $this->create_doc($newFileData,$empId);
        }

            // If the loop completes without errors, return true
        // return true;
    }
    public function delete_docs($UploadedFileData,$empId)
    {
    // Extracting docId values from the array of objects
    $docIds = array_map(function ($document) {
        return $document['id'];
    }, $UploadedFileData);

    // Constructing the SQL query
    $sql = "DELETE FROM `mcts_extranet`.`dbo.documents` WHERE EmployeeID = '$empId' AND  Document_ID NOT IN (" . implode(',', $docIds) . ")";

    // Execute the delete query
    $query = $this->db->query($sql);

    // // Check if the query was successful
    // if ($query) {
    //     // If successful, return true
    //     return true;
    // } else {
    //     // Handle error if needed
    //     return false;
    // }
}

}