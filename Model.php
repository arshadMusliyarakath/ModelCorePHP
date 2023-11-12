<?php
trait DBConfig {
    protected $host = 'localhost';
    protected $port = '8889';
    protected $dbname = 'Skilledin';
    protected $username = 'root';
    protected $password = 'root';

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

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
        $this->db = $this->connect();
    }

    public function all()
    {
        $query = "SELECT * FROM $this->tableName";
        $stmt = $this->db->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
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
        $query = "SELECT * FROM $this->tableName WHERE $this->conditions";
        return $this->fetchAll($query);
    }

    public function delete()
    {
        $query = "DELETE FROM $this->tableName WHERE $this->conditions";
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
        $query = "UPDATE $this->tableName SET $set WHERE $this->conditions";
        return $this->prepare($query);
    }

    public function join($data){
        $query = "SELECT * FROM $this->tableName";
        $join = '';
        foreach ($data as $value) {
            $join = $join." LEFT JOIN $value[0] ON $value[0].$value[1] = $this->tableName.$value[2]";
        }
        return $this->fetchAll($query.$join);
    }
}

$project = new Model('projects');
$task = new Model('tasks');
$entry = new Model('entry');

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
// $users->join($data);