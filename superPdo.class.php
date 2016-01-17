<?php
class SPDO
{
 
   /**
   * Instance of the SPDO class
   *
   * @var SPDO
   * @access private
   * @static
   */ 
  private static $instance = null;
  
  /**
   * Instances of the PDO class Master,Replication Default
   *
   * @var PDO
   * @access private
   */ 
  private $PDOInstance = null;
  private $PDOMasterInstance = null;
  private $PDOReplicationInstance = null;
 
  /**
   * Constant: DB Hostnames
   *
   * @var string
   */
  const SQL_MASTER_HOST = 'localhost';
  const SQL_REPL_HOST = 'localhost';
 
  /**
   * Constant: DB usernames
   *
   * @var string
   */
  const SQL_MASTER_USER = 'root';
  const SQL_REPL_USER = 'root';
  
  /**
   * Constant: DB passwwords
   *
   * @var string
   */
  const SQL_MASTER_PASS = 'Pass';
  const SQL_REPL_PASS = 'Pass';
 
  /**
   * Constant: DB Name
   *
   * @var string
   */
  const SQL_DTB = 'test';
 
  /**
   * Master Construct PDO
   *
   * @see PDO::__construct()
   * @param void
   * @access private
   */	
  private function ConstructMaster()
  {
    $this->PDOMasterInstance = new PDO('mysql:dbname='.self::SQL_DTB.';host='.self::SQL_MASTER_HOST,self::SQL_MASTER_USER ,self::SQL_MASTER_PASS,
							array( 
								PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
								PDO::ATTR_PERSISTENT => true
							)
	);
	$this->PDOMasterInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$this->PDOMasterInstance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  }
  
  /**
   * Replication Construct PDO
   *
   * @see PDO::__construct()
   * @param void
   * @access private
   */	
  private function ConstructReplication()
  {
    $this->PDOReplicationInstance = new PDO('mysql:dbname='.self::SQL_DTB.';host='.self::SQL_REPL_HOST,self::SQL_REPL_USER ,self::SQL_REPL_PASS,
							array( 
								PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
								PDO::ATTR_PERSISTENT => true
							)
	);
	$this->PDOReplicationInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$this->PDOReplicationInstance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  }
	
  
  /**
   * List of Query that write on DB
   *
   * @param void
   * @access private
   * @return array() 
   */	
	private function NeedMaster()
	{
		$Action = array(
							"insert",
							"insert into",
							"update",
							"drop",
							"alter",
							"delete",
							"truncate",
							"create"
		);
		return $Action;
	}
	
  
  /**
   * Check the Query if it must be read on replica or write on master
   *
   * @param string $query The SQL query
   * @access private
   * @return True or False (True if need write to DB)
   */
	private function CheckQuery($query) 
	{
			$chr = array();
			foreach($this->NeedMaster() as $needle) {
					$res = stripos($query, $needle, 0);
					if ($res !== false){$chr[$needle] = $res;break;}
			}
			if(empty($chr)) {return false;} 	//no one of the forbidden action are detected
			else            {return true;}		// at least one of the forbidden character found
	}
	
  /**
   * Launch the query test and buid the PDO object
   *
   * @param string $query The SQL query
   * @access private
   */
	private function CheckBefore($query) 
	{
		if($this->CheckQuery($query)===true){
			if(is_null($this->PDOMasterInstance))
			{
			  $this->ConstructMaster();
			}
			$this->PDOInstance = $this->PDOMasterInstance;
		}
		else{
			if(is_null($this->PDOReplicationInstance))
			{
			  $this->ConstructReplication();
			}
			$this->PDOInstance = $this->PDOReplicationInstance;
		}
		
	}
  
 
   /**
    * Create and return the SPDO object
    *
    * @access public
    * @static
    * @param void
    * @return SPDO $instance
    */
	public static function getInstance()
	{  
		if(is_null(self::$instance))
		{
		  self::$instance = new SPDO();
		}
		return self::$instance;
	}
 
  /**
   * Execute a query SQL with PDO
   *
   * @param string $query The SQL query
   * @return PDOStatement Return the object PDOStatement
   */
  public function query($query)
  {
	$this->CheckBefore($query);	
    return $this->PDOInstance->query($query);
  }
  public function prepare($query)
  {
	$this->CheckBefore($query);
    return $this->PDOInstance->prepare($query);
  }
  public function lastInsertId()
  {
    return $this->PDOInstance->lastInsertId();
  }
}
