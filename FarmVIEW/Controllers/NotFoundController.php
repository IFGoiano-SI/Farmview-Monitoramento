<?php 
namespace Controllers;

// require_once("Models/Database.php");
// require_once("Config/Helpers.php");
// require_once("Ultis/RenderView.php");

use Models\Database as Conexao;
use \PDO;
use \Ultis\RenderView;
class NotFoundController {


public function index(){
    echo 'Not Found';
}

}


?>