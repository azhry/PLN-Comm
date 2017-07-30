<?php 

/**
 * DBHelper.php
 * Created and documented by Azhary Arliansyah 28/07/2017
 * Helper class to handle database processing logic using securely PDO prepared statement
 */

class DBHelper
{
	// declare $db as static attribute
	private static $db;

	/**
	 * This method is used for connecting to database
	 */
	public static function connect($host, $username, $password, $database)
	{
		try 
		{
			self::$db = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password);
			self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) 
		{
			echo $e->getMessage();
		}
	}

	/**
	 * This method is used for retrieving data
	 */
	public static function select($table, $columns = ['*'], $conditions = [])
	{
		// construct sql string
		$columns 		= implode(',', $columns);
		$cond_keys 		= array_keys($conditions);
		$cond_values	= array_values($conditions);
		$cond_count		= count($conditions);
		$sql			= 'SELECT ' . $columns . ' FROM ' . $table;
		if ($cond_count > 0) 
		{
			$sql .= ' WHERE ';
		}
		for ($i = 0; $i < $cond_count; $i++)
		{
			$sql .= $cond_keys[$i] . '=:' . $cond_keys[$i];
			if ($i < $cond_count - 1)
			{
				$sql .= ' AND ';
			}
		}

		try 
		{
			// prepare sql with prepared statement
			$stmt = self::$db->prepare($sql);
			for ($i = 0; $i < $cond_count; $i++)
			{
				// bind values to avoid sql injection
				$stmt->bindparam(':' . $cond_keys[$i], $cond_values[$i]);
			}

			// execute statement
			$stmt->execute();

			// return the result as array of associative array
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} 
		catch (PDOException $e)
		{
			// exception is thrown, return false
			echo $e->getMessage();
			return FALSE;
		}
	}


	/**
	 * This method is used for retrieving one row of data
	 */
	public static function select_row($table, $columns = ['*'], $conditions = [])
	{
		// construct sql string
		$columns 		= implode(',', $columns);
		$cond_keys 		= array_keys($conditions);
		$cond_values	= array_values($conditions);
		$cond_count		= count($conditions);
		$sql			= 'SELECT ' . $columns . ' FROM ' . $table;
		if ($cond_count > 0) 
		{
			$sql .= ' WHERE ';
		}
		for ($i = 0; $i < $cond_count; $i++)
		{
			$sql .= $cond_keys[$i] . '=:' . $cond_keys[$i];
			if ($i < $cond_count - 1)
			{
				$sql .= ' AND ';
			}
		}

		try 
		{
			// prepare sql with prepared statement
			$stmt = self::$db->prepare($sql);
			for ($i = 0; $i < $cond_count; $i++)
			{
				// bind values to avoid sql injection
				$stmt->bindparam(':' . $cond_keys[$i], $cond_values[$i]);
			}

			// execute statement
			$stmt->execute();

			// return the result as associative array
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} 
		catch (PDOException $e)
		{
			// exception is thrown, return false
			echo $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * This method is used for inserting data
	 */
	public static function insert($table, $key_value_pair)
	{
		// construct sql string
		$cond_keys		= array_keys($key_value_pair);
		$cond_values	= array_values($key_value_pair);
		$cond_count		= count($key_value_pair);
		$sql 			= 'INSERT INTO ' . $table . '(' . implode(',', $cond_keys) . ') VALUES(';
		for ($i = 0; $i < $cond_count; $i++)
		{
			$sql .= ':' . $cond_keys[$i];
			if ($i < $cond_count - 1)
			{
				$sql .= ', ';
			}
			else
			{
				$sql .= ')';
			}
		}

		try
		{
			// prepare sql statement
			$stmt = self::$db->prepare($sql);
			for ($i = 0; $i < $cond_count; $i++)
			{
				// bind values to avoid sql injection
				$stmt->bindparam(':' . $cond_keys[$i], $cond_values[$i]);
			}

			// execute statement
			$stmt->execute();

			// insert success
			return TRUE;
		}
		catch (PDOException $e)
		{
			// exception is thrown, return false
			echo $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * This method is used for updating data
	 */
	public static function update($table, $key_value_pair, $conditions)
	{
		// construct sql string
		$keys 			= array_keys($key_value_pair);
		$values 		= array_values($key_value_pair);
		$count 			= count($key_value_pair);
		$cond_keys		= array_keys($conditions);
		$cond_values	= array_values($conditions);
		$cond_count		= count($conditions);
		$sql			= 'UPDATE ' . $table . ' SET ';
		for ($i = 0; $i < $count; $i++)
		{
			$sql .= $keys[$i] . '=:' . $keys[$i];
			if ($i < $count - 1)
			{
				$sql .= ', ';
			}
		}
		$sql .= ' WHERE ';
		for ($i = 0; $i < $cond_count; $i++)
		{
			$sql .= $cond_keys[$i] . '=:' . $cond_keys[$i];
			if ($i < $cond_count - 1)
			{
				$sql .= ' AND ';
			}
		}

		try
		{
			// prepare sql statement
			$stmt = self::$db->prepare($sql);
			
			// bind values to avoid sql injection
			for ($i = 0; $i < $count; $i++)
			{
				$stmt->bindparam(':' . $keys[$i], $values[$i]);
			}
			for ($i = 0; $i < $cond_count; $i++)
			{
				$stmt->bindparam(':' . $cond_keys[$i], $cond_values[$i]);
			}

			// execute statement
			$stmt->execute();

			// update success
			return TRUE;
		}
		catch (PDOException $e)
		{
			// exception is thrown, return false
			echo $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * This method is used for deleting data
	 */
	public static function delete($table, $conditions)
	{
		// construct sql string
		$cond_keys 		= array_keys($conditions);
		$cond_values	= array_values($conditions);
		$cond_count 	= count($conditions);
		$sql 			= 'DELETE FROM ' . $table . ' WHERE ';
		for ($i = 0; $i < $cond_count; $i++)
		{
			$sql .= $cond_keys[$i] . '=:' . $cond_keys[$i];
			if ($i < $cond_count - 1)
			{
				$sql .= ' AND ';
			}
		}

		try
		{
			// prepare sql statement
			$stmt = self::$db->prepare($sql);
			for ($i = 0; $i < $cond_count; $i++)
			{
				// bind values to avoid sql injection
				$stmt->bindparam(':' . $cond_keys[$i], $cond_values[$i]);
			}

			// execute statement
			$stmt->execute();

			// delete success
			return TRUE;
		}
		catch (PDOException $e)
		{
			// exception is thrown, return false
			echo $e->getMessage();
			return FALSE;
		}
	}
}