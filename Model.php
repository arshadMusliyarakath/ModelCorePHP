<?php
trait DBConfig {
    protected $host = 'localhost';
    protected $port = '3306';
    protected $dbname = 'Skilledin';
    protected $username = 'root';
    protected $password = '';

    public function connect() {
        try {
            $db = new PDO("mysql:host={$this->host};port={$this->port};dbname={$this->dbname}", $this->username, $this->password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}

class Model
{
    use DBConfig;
    protected $tableName;
    protected $db;
    protected $conditions = '';
    protected $join = '';
    protected $selectArgs = '';
    protected $groupArgs = '';
    protected $orderArgs = '';
    protected $setName = '';
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
        $this->db = $this->connect();
    }

    public function where($keys = null, $values = null)
    {   
        $this->conditions = '';
        if(is_array($keys)){
            foreach ($keys as $key => $value) {
                $this->conditions = $this->conditions.$key." = '".$value."' AND ";
                
            }
            $this->conditions = substr($this->conditions, 0, -5);
        }
        else{
            $this->conditions = "$keys = '$values'";
        }
        $this->conditions = 'WHERE '.$this->conditions;
        return $this;
    }

    public function select(){
        $this->selectArgs = '';
        $arguments =  func_get_args();
        foreach ($arguments as  $arg) {
            $this->selectArgs = $this->selectArgs."$arg".", ";
        }
        $this->selectArgs= substr($this->selectArgs, 0, -2);
        return $this;
    }

    public function join($data){
        $this->join = '';
        foreach ($data as $value) {
            $this->join = $this->join." LEFT JOIN $value[0] ON $value[0].$value[1] = $this->tableName.$value[2]";
        }
        return $this;
    }

    public function groupBy($data){
        $this->groupArgs = '';
        $this->groupArgs = "GROUP BY ".$data;
        return $this;
    }

    public function orderBy($data){
        $this->orderArgs = '';
        $this->orderArgs = "ORDER BY ".$data;
        return $this;
    }

    public function setName($data){
        $this->setName = '';
        $as = ', ';
        foreach ($data as $key => $value) {
            $as = $as.$key." as ".$value.", ";
        }
        $this->setName = substr($as, 0, -2);
        return $this;
    }
    
    public function getPrimaryKey()
    {
        $query = "SHOW KEYS FROM $this->tableName WHERE Key_name = 'PRIMARY'";
        $stmt = $this->db->query($query);

        if ($stmt) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($data) > 0) {
                $primaryKeyColumn = $data[0]['Column_name'];
                return $primaryKeyColumn;
            }
        }
        return null;
    }

    public function fetchAll($query){
        $stmt = $this->db->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function prepare($query)
    {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                return ($this->db->lastInsertId() > 0) ? $this->db->lastInsertId() : 1;
            } else {
                return 0;
            }
        } catch (PDOException $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public function find($id)
    {
        $primaryKey = $this->getPrimaryKey();
        $query = "SELECT * FROM $this->tableName WHERE $primaryKey = $id";
        return $this->fetchAll($query);
    }

    public function get()
    {   
        $where = (!empty($this->conditions)) ? $this->conditions : '';
        $args = (!empty($this->selectArgs)) ? $this->selectArgs : '*';
        $join = (!empty($this->join)) ? $this->join : '';
        $group = (!empty($this->groupArgs)) ? $this->groupArgs : ''; 
        $order = (!empty($this->orderArgs)) ? $this->orderArgs : '';
        $setName = (!empty($this->setName)) ? $this->setName : ''; 
        $query = "SELECT $args $setName FROM $this->tableName $join $where $group $order";
        return $this->fetchAll($query);
        //return $query;
    }

    public function delete()
    {
        $query = "DELETE FROM $this->tableName $this->conditions";
        return $this->prepare($query);
    }

    public function create($data)
    {
        $into = '';
        $values = '';
        foreach ($data as $key => $value) {
            $into = $into.$key.", ";
            $values = $values."'".$value."', ";
        }
        $into = substr($into, 0, -2);
        $values = substr($values, 0, -2);
        $query = "INSERT INTO $this->tableName ($into) VALUES ($values)";
        return $this->prepare($query);
    }

    public function update($data)
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set = $set.$key." = '".$value."', ";
        }
        $set = substr($set, 0, -2);
        $query = "UPDATE $this->tableName SET $set $this->conditions";
        return $this->prepare($query);
    }
}

// $project = new Model('projects');
// $task = new Model('tasks');
// $entry = new Model('entry');


// echo "<pre>";
// $projects = $project->select('project_name')->get();
// var_dump($projects);


// $user = new Model('user');
// $user->find($primaryKey);
// $user->create($data);
// $user->where('name', 'Arshad')->get(); // can passed array : $data
// $user->where('name', 'Arshad')->update(); // can passed array : $data
// $user->where('name', 'Arshad')->delete(); // can passed array : $data

// $data = [
//     array('projects', 'project_id', 'project_id'),
//     array('tasks', 'task_id', 'task_id')
// ];
//var_dump($entry->join($data)->select('projects.status as fgdfg')->get());
// var_dump($entry->join($data)->where('projects.status','0')->setName(array('projects.status' => 'projectStatus','tasks.status' => 'taskStatus'))->get());