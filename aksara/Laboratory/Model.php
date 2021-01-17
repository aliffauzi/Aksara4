<?php namespace Aksara\Laboratory;
/**
 * CRUD Model
 * The global model that linked to the core, make crud easier
 *
 * @author			Aby Dahana
 * @profile			abydahana.github.io
 * @website			www.aksaracms.com
 * @since			version 4.0.0
 * @copyright		(c) 2021 - Aksara Laboratory
 */
class Model
{
	private $db;
	
	private $_list_tables;
	private $_table_exists;
	private $_field_exists;
	private $_list_fields;
	private $_field_data;
	
	private $_query;
	
	private $_distinct;
	private $_select								= array();
	private $_select_avg							= array();
	private $_select_max							= array();
	private $_select_min							= array();
	private $_select_sum							= array();
	private $_from;
	private $_table;
	private $_join									= array();
	private $_where									= array();
	private $_or_where								= array();
	private $_where_in								= array();
	private $_or_where_in							= array();
	private $_where_not_in							= array();
	private $_or_where_not_in						= array();
	private $_like									= array();
	private $_or_like								= array();
	private $_not_like								= array();
	private $_or_not_like							= array();
	private $_having								= array();
	private $_or_having								= array();
	private $_having_in								= array();
	private $_or_having_in							= array();
	private $_having_not_in							= array();
	private $_or_having_not_in						= array();
	private $_having_like							= array();
	private $_or_having_like						= array();
	private $_not_having_like						= array();
	private $_or_not_having_like					= array();
	private $_group_start;
	private $_group_end;
	private $_or_group_start;
	private $_not_group_start;
	private $_or_not_group_start;
	private $_group_by;
	private $_order_by								= array();
	private $_limit;
	private $_offset;
	private $_set									= array();
	private $_replace								= array();
	
	function __construct()
	{
		$this->db									= \Config\Database::connect();
	}
	
	public function database_config($driver = null, $hostname = null, $port = null, $username = null, $password = null, $database = null)
	{
		if('default' == $driver)
		{
			$this->connector						= new \Aksara\Libraries\Connector;
			$this->db								= $this->connector->connect();
		}
		else
		{
			ini_set('sqlsrv.ClientBufferMaxKBSize', 524288);
			
			/* set the default dsn for non-standard connection */
			$dsn									= null;
			
			if('pdo' == $driver)
			{
				$dsn								= 'dblib:host=' . $hostname . ($port ? (phpversion() <= 7.2 ? ':' : ',') . $port : '') . ';dbname=' . $database;
			}
			
			/* define config */
			$config									= array
			(
				'DSN'								=> $dsn,
				'DBDriver' 							=> $driver,
				'hostname' 							=> $hostname . ($port ? ',' . $port : null),
				'username'							=> $username,
				'password' 							=> $password,
				'database' 							=> $database
			);
			
			/* load the new database connection with the defined config */
			$this->db								= \Config\Database::connect($config);
		}
		
		return $this;
	}
	
	/**
	 * ---------------------------------------------------------------
	 * Database Helper
	 * ---------------------------------------------------------------
	 */
	/**
	 * Listing the available tables on current active
	 * database
	 */
	public function list_tables()
	{
		return $this->db->listTables();
	}
	
	/**
	 * Check the existing of table on current active
	 * database
	 */
	public function table_exists($table = null)
	{
		$table										= (strpos($table, ' ') !== false ? substr($table, 0, strpos($table, ' ')) : $table);
		
		if($this->db->tableExists($table))
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Check the field existence of selected table
	 */
	public function field_exists($field = null, $table = null)
	{
		$table										= (strpos($table, ' ') !== false ? substr($table, 0, strpos($table, ' ')) : $table);
		
		if($this->db->fieldExists($field, $table))
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * List the field of selected table
	 */
	public function list_fields($table = null)
	{
		$table										= (strpos($table, ' ') !== false ? substr($table, 0, strpos($table, ' ')) : $table);
		
		if($this->db->tableExists($table))
		{
			return $this->db->getFieldNames($table);
		}
		
		return false;
	}
	
	/**
	 * Get the table metadata and field info of selected table
	 */
	public function field_data($table = null)
	{
		$table										= (strpos($table, ' ') !== false ? substr($table, 0, strpos($table, ' ')) : $table);
		
		if($this->db->tableExists($table))
		{
			return $this->db->getFieldData($table);
		}
		
		return false;
	}
	
	/**
	 * Get the affected rows
	 */
	public function affected_rows()
	{
		return $this->db->affectedRows();
	}
	
	/**
	 * Get the last insert id
	 */
	public function insert_id()
	{
		return $this->db->insertID();
	}
	
	/**
	 * ---------------------------------------------------------------
	 * Query Builder
	 * ---------------------------------------------------------------
	 */
	/**
	 * Run the SQL command string
	 */
	public function query($query = null)
	{
		$this->_query								= $query;
		
		return $this;
	}
	
	/**
	 * Distinct field
	 */
	public function distinct($val = null)
	{
		if(!is_array($val))
		{
			$val									= array_map('trim', explode(',', $val));
		}
		
		$this->_distinct							= array_merge($this->_distinct, $val);
		
		return $this;
	}
	
	/**
	 * Select field
	 * Possible to use comma separated
	 */
	public function select($select = null, $escape = true)
	{
		if(!is_array($select))
		{
			// split selected by comma, but ignore that inside brackets
			$select									= array_map('trim', preg_split('/,(?![^(]+\))/', $select));
			
			foreach($select as $key => $val)
			{
				if(!$val || (stripos($val, '(') !== false && stripos($val, ')') !== false)) continue;
				
				$val								= explode('.', $val);
				
				if(isset($val[1]) && stripos($val[1], ' AS ') !== false)
				{
					$field							= substr($val[1], 0, strripos($val[1], ' AS '));
					
					if(!$this->field_exists($field, $val[0]))
					{
						unset($select[$key]);
					}
				}
			}
		}
		
		$this->_select								= array_merge($this->_select, $select);
		
		return $this;
	}
	
	/**
	 * Select and Sum
	 * Possible to use comma separated
	 */
	public function select_sum($select = null, $alias = null)
	{
		if(!is_array($select))
		{
			$this->_select_sum[$select]				= ($alias ? $alias : $select);
		}
		else
		{
			foreach($select as $key => $val)
			{
				$this->_select_sum[$key]			= $val;
			}
		}
		
		return $this;
	}
	
	/**
	 * Select Minimum
	 * Possible to use comma separated
	 */
	public function select_min($select = null, $alias = null)
	{
		if(!is_array($select))
		{
			$this->_select_min[$select]				= ($alias ? $alias : $select);
		}
		else
		{
			foreach($select as $key => $val)
			{
				$this->_select_min[$key]			= $val;
			}
		}
		
		return $this;
	}
	
	/**
	 * Select Maximum
	 * Possible to use comma separated
	 */
	public function select_max($select = null, $alias = null)
	{
		if(!is_array($select))
		{
			$this->_select_max[$select]				= ($alias ? $alias : $select);
		}
		else
		{
			foreach($select as $key => $val)
			{
				$this->_select_max[$key]			= $val;
			}
		}
		
		return $this;
	}
	
	/**
	 * Select Average of field
	 * Possible to use comma separated
	 */
	public function select_avg($select = null, $alias = null)
	{
		if(!is_array($select))
		{
			$this->_select_avg[$select]				= ($alias ? $alias : $select);
		}
		else
		{
			foreach($select as $key => $val)
			{
				$this->_select_avg[$key]			= $val;
			}
		}
		
		return $this;
	}
	
	/**
	 * Set the primary table
	 */
	public function from($table = null)
	{
		$this->_table								= $table;
		
		return $this;
	}
	
	/**
	 * Set the primary table
	 * It's similar to from() method
	 */
	public function table($table = null)
	{
		$this->_table								= $table;
		
		return $this;
	}
	
	/**
	 * Join table
	 * Your contribution is needed to write hint about
	 * this method
	 */
	public function join($table = null, $condition = null, $type = '', $escape = true)
	{
		if(!is_array($table))
		{
			if(isset($condition['condition']))
			{
				$this->_join_table[$table]			= $condition;
			}
			else
			{
				$this->_join[$table]				= array
				(
					'condition'						=> $condition,
					'type'							=> $type,
					'escape'						=> $escape
				);
			}
		}
		else
		{
			foreach($table as $key => $val)
			{
				$this->_join[$key]					= array
				(
					'condition'						=> $val,
					'type'							=> $type,
					'escape'						=> true
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Where
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function where($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_where[$field]				= $value;
			}
			else
			{
				$this->_where[$field]				= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_where[$key]					= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Where
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_where($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_or_where[$field]			= $value;
			}
			else
			{
				$this->_or_where[$field]			= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_where[$key]				= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Where In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function where_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(is_array($value['value']))
			{
				$this->_where_in[$field]			= $value;
			}
			else
			{
				$this->_where_in[$field]			= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_where_in[$key]				= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Where In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_where_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_or_where[$field]			= $value;
			}
			else
			{
				$this->_or_where_in[$field]			= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_where_in[$key]			= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Where Not In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function where_not_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_where_not_in[$field]		= $value;
			}
			else
			{
				$this->_where_not_in[$field]		= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_where_not_in[$key]			= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Where Not In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_where_not_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_or_where_not_in[$field]		= $value;
			}
			else
			{
				$this->_or_where_not_in[$field]		= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_where_not_in[$key]		= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_like[$field]				= $match;
			}
			else
			{
				$this->_like[$field]				= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_like[$key]					= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_or_like[$field]				= $match;
			}
			else
			{
				$this->_or_like[$field]				= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_like[$key]				= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Not Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function not_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_not_like[$field]			= $match;
			}
			else
			{
				$this->_not_like[$field]			= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_not_like[$key]				= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Not Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_not_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_or_not_like[$field]			= $match;
			}
			else
			{
				$this->_or_not_like[$field]			= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_not_like[$key]			= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Having
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function having($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_having[$field]				= $value;
			}
			else
			{
				$this->_having[$field]				= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_having[$key]				= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Having
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_having($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_or_having[$field]				= $value;
			}
			else
			{
				$this->_or_having[$field]			= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_having[$key]				= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Having In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function having_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_having_in[$field]			= $value;
			}
			else
			{
				$this->_having_in[$field]			= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_having_in[$key]				= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Having In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_having_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_or_having_in[$field]		= $value;
			}
			else
			{
				$this->_or_having_in[$field]		= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_having_in[$key]			= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Having Not In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function having_not_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_having_not_in[$field]		= $value;
			}
			else
			{
				$this->_having_not_in[$field]		= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_having_not_in[$key]			= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Having Not In
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_having_not_in($field = '', $value = '', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($value['value']))
			{
				$this->_or_having_not_in[$field]	= $value;
			}
			else
			{
				$this->_or_having_not_in[$field]	= array
				(
					'value'							=> $value,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_having_not_in[$key]		= array
				(
					'value'							=> $val,
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Having Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function having_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_having_like[$field]			= $match;
			}
			else
			{
				$this->_having_like[$field]			= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_having_like[$key]			= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Having Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_having_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_or_having_like[$field]		= $match;
			}
			else
			{
				$this->_or_having_like[$field]		= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_having_like[$key]		= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Not Having Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function not_having_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_not_having_like[$field]		= $match;
			}
			else
			{
				$this->_not_having_like[$field]		= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_not_having_like[$key]		= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Or Not Having Like
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_not_having_like($field = '', $match = '', $side = 'both', $escape = true, $case_sensitive = false)
	{
		if(!is_array($field))
		{
			if(isset($match['match']))
			{
				$this->_or_not_having_like[$field]	= $match;
			}
			else
			{
				$this->_or_not_having_like[$field]	= array
				(
					'match'							=> $match,
					'side'							=> $side,
					'escape'						=> $escape,
					'case_sensitive'				=> $case_sensitive
				);
			}
		}
		else
		{
			foreach($field as $key => $val)
			{
				$this->_or_not_having_like[$key]	= array
				(
					'match'							=> $val,
					'side'							=> 'both',
					'escape'						=> true,
					'case_sensitive'				=> false
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Limit
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function limit($limit = 0, $offset = 0)
	{
		$this->_limit								= $limit;
		
		if($offset)
		{
			$this->_offset							= $offset;
		}
		
		return $this;
	}
	
	/**
	 * Offset
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function offset($offset = null)
	{
		$this->_offset								= $offset;
		
		return $this;
	}
	
	/**
	 * Order By
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function order_by($column = null, $direction = null, $escape = true)
	{
		if(!is_array($column))
		{
			if($direction)
			{
				$this->_order_by[$column]			= array
				(
					'direction'						=> $direction,
					'escape'						=> $escape
				);
			}
			else
			{
				$column								= array_map('trim', explode(',', $column));
				
				foreach($column as $key => $val)
				{
					if(strpos($val, '(') !== false && strpos($val, ')') !== false)
					{
						$col						= $val;
					}
					else
					{
						list($col, $dir)			= array_pad(array_map('trim', explode(' ', $val)), 2, null);
					}
					
					$this->_order_by[$col]			= array
					(
						'direction'					=> ($dir ? $dir : 'asc'),
						'escape'					=> $escape
					);
				}
			}
		}
		else
		{
			foreach($column as $key => $val)
			{
				$this->_order_by[$key]				= array
				(
					'direction'						=> $val,
					'escape'						=> true
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * Group By
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function group_by($by = null)
	{
		$this->_group_by							= $by;
		
		return $this;
	}
	
	/**
	 * Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function group_start()
	{
		
		return $this;
	}
	
	/**
	 * Or Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_group_start()
	{
		
		return $this;
	}
	
	/**
	 * Not Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function not_group_start()
	{
		
		return $this;
	}
	
	/**
	 * Or Not Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_not_group_start()
	{
		
		return $this;
	}
	
	/**
	 * Group End
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function group_end()
	{
		
		return $this;
	}
	
	/**
	 * Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function group_having_start()
	{
		
		return $this;
	}
	
	/**
	 * Or Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_group_having_start()
	{
		
		return $this;
	}
	
	/**
	 * Not Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function not_group_having_start()
	{
		
		return $this;
	}
	
	/**
	 * Or Not Group Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function or_not_group_having_start()
	{
		
		return $this;
	}
	
	/**
	 * Group End
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function group_having_end()
	{
		
		return $this;
	}
	
	/**
	 * Set
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function set($key = null, $value = null, $escape = true)
	{
		if(!is_array($key))
		{
			$this->_set[$key]						= $value;
		}
		else
		{
			$this->_set								= array_merge($this->_set, $key);
		}
		
		return $this;
	}
	
	/**
	 * Insert
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function insert($table = null, $set = array(), $escape = true)
	{
		$set										= array_merge($this->_set, $set);
		
		return $this->db->table($table)->insert($set);
	}
	
	/**
	 * Insert Batch
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function insert_batch($table = null, $set = array(), $batch_size = 1, $escape = true)
	{
		$set										= array_merge($this->_set, $set);
		
		return $this->db->table($table)->insertBatch($set, $escape, $batch_size);
	}
	
	/**
	 * Update
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function update($table = null, $set = array(), array $where = array(), $limit = 1)
	{
		$set										= array_merge($this->_set, $set);
		
		foreach($where as $key => $val)
		{
			if(is_array($val) && isset($val['value']))
			{
				$where[$key]						= $val['value'];
			}
		}
		
		return $this->db->table($table)->update($set, $where, $limit);
	}
	
	/**
	 * Update Batch
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function update_batch($table = null, $set = array(), $batch_size = 1, $escape = true)
	{
		$set										= array_merge($this->_set, $set);
		
		return $this->db->table($table)->updateBatch($set, '', $batch_size);
	}
	
	/**
	 * Replace
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function replace($table = null, $set = array())
	{
		$set										= array_merge($this->_set, $set);
		
		return $this->db->table($table)->replace($set);
	}
	
	/**
	 * Delete
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function delete($table = null, $where = array(), $limit = 0, $reset_data = true)
	{
		$where										= array_merge($this->_where, $where);
		
		return $this->db->table($table)->delete($where, $limit, $reset_data);
	}
	
	/**
	 * Truncate
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function truncate($table = null)
	{
		if(!$table)
		{
			$table								= $this->_table;
		}
		
		return $this->db->table($table)->truncate();
	}
	
	/**
	 * Empty Table
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function empty_table($table = null)
	{
		if(!$table)
		{
			$table								= $this->_table;
		}
		
		return $this->db->table($table)->emptyTable();
	}
	
	/**
	 * Get number of rows
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function num_rows($table = null, $reset = true)
	{
		if($table)
		{
			$this->_table						= $table;
		}
		
		return $this->_run_query('countAllResults');
	}
	
	/**
	 * Count All
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function count_all($table = null, $reset = true)
	{
		if($table)
		{
			$this->_table						= $table;
		}
		
		return $this->_run_query('countAll');
	}
	
	/**
	 * Count All Results
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function count_all_results($table = null, $reset = true)
	{
		if($table)
		{
			$this->_table						= $table;
		}
		
		return $this->_run_query('countAllResults');
	}
	
	/**
	 * Get
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function get($table = null, $limit = 0, $offset = 0)
	{
		if($table)
		{
			$this->_table							= $table;
		}
		
		if($limit)
		{
			$this->_limit							= $limit;
		}
		
		if($offset)
		{
			$this->_offset							= $offset;
		}
		
		return $this;
	}
	
	/**
	 * Get Where
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function get_where($table = null, array $where = array(), $limit = 0, $offset = null, $reset = true)
	{
		if($table)
		{
			$this->_table							= $table;
		}
		
		foreach($where as $key => $val)
		{
			$this->_where[$key]						= array
			(
				'value'								=> (isset($val['value']) ? $val['value'] : $val),
				'escape'							=> (isset($val['escape']) ? $val['escape'] : true),
				'case_sensitive'					=> (isset($val['case_sensitive']) ? $val['case_sensitive'] : false)
			);
		}
		
		if($limit)
		{
			$this->_limit							= $limit;
		}
		
		if($offset)
		{
			$this->_offset							= $offset;
		}
		
		return $this;
	}
	
	/**
	 * Result (object format)
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function result()
	{
		return $this->_run_query('getResult');
	}
	
	/**
	 * Result (array format)
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function result_array()
	{
		return $this->_run_query('getResultArray');
	}
	
	/**
	 * Get Row
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function row($field = 1)
	{
		return $this->_run_query('getRow', $field);
	}
	
	/**
	 * Get Row Array
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function row_array($field = 1)
	{
		return $this->_run_query('getRowArray', $field);
	}
	
	/**
	 * Get Last Query
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function last_query()
	{
		return get_userdata('____last_query');
	}
	
	/**
	 * ---------------------------------------------------------------
	 * Transaction
	 * ---------------------------------------------------------------
	 */
	/**
	 * Transaction Begin
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function trans_begin()
	{
		$this->db->transBegin();
		
		return $this;
	}
	
	/**
	 * Transaction Start
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function trans_start()
	{
		$this->db->transStart();
		
		return $this;
	}
	
	/**
	 * Transaction Complete
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function trans_complete()
	{
		$this->db->transComplete();
		
		return $this;
	}
	
	/**
	 * Get Transaction Status
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function trans_status()
	{
		$this->db->transStatus();
		
		return $this;
	}
	
	/**
	 * Transaction Commit
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function trans_commit()
	{
		$this->db->transCommit();
		
		return $this;
	}
	
	/**
	 * Transaction Rolling Back
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	public function trans_rollback()
	{
		$this->db->transRollback();
		
		return $this;
	}
	
	/**
	 * Run the query of collected property
	 * Your contribution is needed to write complete hint about
	 * this method
	 */
	private function _run_query($result_type = null, $parameter = 'object')
	{
		$builder									= $this->db->table($this->_table);
		
		if($this->_query)
		{
			$output									= $builder->query($this->_query)->$result_type();
			
			$this->_reset_property();
			
			return $output;
		}
		
		if($this->_select)
		{
			// run select command
			$builder->select($this->_select);
		}
		
		if($this->_select_sum)
		{
			// run select sum command
			foreach($this->_select_sum as $key => $val)
			{
				$builder->selectSum($key, $val);
			}
		}
		
		if($this->_select_max)
		{
			// run select max command
			foreach($this->_select_max as $key => $val)
			{
				$builder->selectMax($key, $val);
			}
		}
		
		if($this->_select_min)
		{
			// run select min command
			foreach($this->_select_min as $key => $val)
			{
				$builder->selectMin($key, $val);
			}
		}
		
		if($this->_select_avg)
		{
			// run select avg command
			foreach($this->_select_avg as $key => $val)
			{
				$builder->selectAvg($key, $val);
			}
		}
		
		if($this->_join)
		{
			// run join command
			foreach($this->_join as $key => $val)
			{
				$builder->join($key, $val['condition'], $val['type'], $val['escape']);
			}
		}
		
		if($this->_where)
		{
			// run where command
			foreach($this->_where as $key => $val)
			{
				$builder->where($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_where)
		{
			// run or where command
			foreach($this->_or_where as $key => $val)
			{
				$builder->orWhere($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_where_in)
		{
			// run where in command
			foreach($this->_where_in as $key => $val)
			{
				$builder->whereIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_where_in)
		{
			// run or where in command
			foreach($this->_or_where_in as $key => $val)
			{
				$builder->orWhereIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_where_not_in)
		{
			// run where not in command
			foreach($this->_where_not_in as $key => $val)
			{
				$builder->whereNotIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_where_not_in)
		{
			// run or where not in command
			foreach($this->_or_where_not_in as $key => $val)
			{
				$builder->orWhereNotIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_like)
		{
			// run like command
			foreach($this->_like as $key => $val)
			{
				$builder->like($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_like)
		{
			// run or like command
			foreach($this->_or_like as $key => $val)
			{
				$builder->orLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_not_like)
		{
			// run not like command
			foreach($this->_not_like as $key => $val)
			{
				$builder->notLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_not_like)
		{
			// run or not like command
			foreach($this->_or_not_like as $key => $val)
			{
				$builder->orNotLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_having)
		{
			// run having command
			foreach($this->_having as $key => $val)
			{
				$builder->having($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_having)
		{
			// run or having command
			foreach($this->_or_having as $key => $val)
			{
				$builder->having($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_having_in)
		{
			// run having in command
			foreach($this->_having_in as $key => $val)
			{
				$builder->havingIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_having_in)
		{
			// run or having in command
			foreach($this->_or_having_in as $key => $val)
			{
				$builder->orHavingIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_having_not_in)
		{
			// run having not in command
			foreach($this->_having_not_in as $key => $val)
			{
				$builder->havingNotIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_having_not_in)
		{
			// run or having not in command
			foreach($this->_or_having_not_in as $key => $val)
			{
				$builder->orHavingNotIn($key, $val['value'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_having_like)
		{
			// run having like command
			foreach($this->_having_like as $key => $val)
			{
				$builder->havingLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_having_like)
		{
			// run or having like command
			foreach($this->_or_having_like as $key => $val)
			{
				$builder->orHavingLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_not_having_like)
		{
			// run not having like command
			foreach($this->_not_having_like as $key => $val)
			{
				$builder->notHavingLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_or_not_having_like)
		{
			// run or not having like command
			foreach($this->_or_not_having_like as $key => $val)
			{
				$builder->orNotHavingLike($key, $val['match'], $val['side'], $val['escape'], $val['case_sensitive']);
			}
		}
		
		if($this->_group_by)
		{
			// run group by command
			$builder->groupBy($this->_group_by);
		}
		
		if($this->_limit)
		{
			// run limit command
			$builder->limit($this->_limit, $this->_offset);
		}
		
		if($this->_order_by)
		{
			// run order by command
			foreach($this->_order_by as $key => $val)
			{
				$builder->orderBy($key, $val['direction'], $val['escape']);
			}
		}
		if(in_array($result_type, array('countAll', 'countAllResults')))
		{
			$output									= $builder->$result_type($parameter);
		}
		else
		{
			$output									= $builder->get()->$result_type($parameter);
		}
		
		// store last query to display if needed
		set_userdata('____last_query', $this->db->getLastQuery());
		
		// reset property
		$this->_reset_property();
		
		return $output;
	}
	
	/**
	 * Reset property to prevent duplicate 
	 */
	private function _reset_property()
	{
		foreach(get_class_vars(get_class($this)) as $key => $val)
		{
			if(in_array($key, array('db'))) continue;
			
			$this->$key								= (is_array($val) ? array() : null);
		}
	}
}
