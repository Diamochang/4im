<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

class PreparedQueryDebug {
	protected $query, $explain_query = false;
	
	public function __construct($query) {
		global $pdo, $config;
		$query = preg_replace("/[\n\t]+/", ' ', $query);
		
		$this->query = $pdo->prepare($query);
		if ($config['debug'] && $config['debug_explain'] && preg_match('/^(SELECT|INSERT|UPDATE|DELETE) /i', $query))
			$this->explain_query = $pdo->prepare("EXPLAIN $query");
	}
	public function __call($function, $args) {
		global $config, $debug;
		
		if ($config['debug'] && $function == 'execute') {
			if ($this->explain_query) {
				$this->explain_query->execute() or error(db_error($this->explain_query));
			}
			$start = microtime(true);
		}
		
		if ($this->explain_query && $function == 'bindValue')
			call_user_func_array(array($this->explain_query, $function), $args);
		
		$return = call_user_func_array(array($this->query, $function), $args);
		
		if ($config['debug'] && $function == 'execute') {
			$time = microtime(true) - $start;
			$debug['sql'][] = array(
				'query' => $this->query->queryString,
				'rows' => $this->query->rowCount(),
				'explain' => $this->explain_query ? $this->explain_query->fetchAll(PDO::FETCH_ASSOC) : null,
				'time' => '~' . round($time * 1000, 2) . 'ms'
			);
			$debug['time']['db_queries'] += $time;
		}
		
		return $return;
	}
}

function sql_open() {
	global $pdo, $config, $debug;
	if ($pdo)
		return true;
	
	
	if ($config['debug'])
		$start = microtime(true);
	
	if (isset($config['db']['server'][0]) && $config['db']['server'][0] == ':')
		$unix_socket = substr($config['db']['server'], 1);
	else
		$unix_socket = false;
	
	$dsn = $config['db']['type'] . ':' .
		($unix_socket ? 'unix_socket=' . $unix_socket : 'host=' . $config['db']['server']) .
		';dbname=' . $config['db']['database'];
	if (!empty($config['db']['dsn']))
		$dsn .= ';' . $config['db']['dsn'];
	try {
		$options = array(
			PDO::ATTR_TIMEOUT => $config['db']['timeout'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
		);
		if ($config['db']['persistent'])
			$options[PDO::ATTR_PERSISTENT] = true;
		$pdo = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
		
		if ($config['debug'])
			$debug['time']['db_connect'] = '~' . round((microtime(true) - $start) * 1000, 2) . 'ms';
		
		if (mysql_version() >= 50503)
			query('SET NAMES utf8mb4') or error(db_error());
		else
			query('SET NAMES utf8') or error(db_error());
		return $pdo;
	} catch(PDOException $e) {
		$message = $e->getMessage();
		
		// Remove any sensitive information
		$message = str_replace($config['db']['user'], '<em>hidden</em>', $message);
		$message = str_replace($config['db']['password'], '<em>hidden</em>', $message);
		
		// Check if we're in the installation process
		if (defined('INSTALLING') || strpos($_SERVER['SCRIPT_NAME'], 'install.php') !== false) {
			// Use the install.php error handler
			if (function_exists('install_error')) {
				install_error('Database Error', $message);
			} else {
				// If install_error is not available, show a simple error
				die('<!DOCTYPE html>
<html>
<head>
	<title>Installation Error</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
		.error { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
		h1 { color: #c62828; margin-top: 0; }
	</style>
</head>
<body>
	<div class="error">
		<h1>Database Error</h1>
		<p>' . htmlspecialchars($message) . '</p>
		<p><a href="install.php">Back to installation</a></p>
	</div>
</body>
</html>');
			}
		} else {
			// Print error
			error(_('Database error: ') . $message);
		}
	}
}

// 5.6.10 becomes 50610
function mysql_version() {
	global $pdo;
	
	$version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
	$v = explode('.', $version);
	if (count($v) != 3)
		return false;
	return (int) sprintf("%02d%02d%02d", $v[0], $v[1], is_int($v[2]) ? (int)$v[2] : 0);
}

function prepare($query) {
	global $pdo, $debug, $config;
	
	$query = preg_replace('/``('.$config['board_regex'].')``/u', '`' . $config['db']['prefix'] . '$1`', $query);
	
	sql_open();
	
	if ($config['debug'])
		return new PreparedQueryDebug($query);

	return $pdo->prepare($query);
}

function query($query) {
	global $pdo, $debug, $config;
	
	$query = preg_replace('/``('.$config['board_regex'].')``/u', '`' . $config['db']['prefix'] . '$1`', $query);
	
	sql_open();
	
	// Check if we're in the installation process
	if ((defined('INSTALLING') || strpos($_SERVER['SCRIPT_NAME'], 'install.php') !== false)) {
		try {
			// Execute the query
			$result = $pdo->query($query);
			
			// Collect debug information if needed
			if ($config['debug']) {
				$debug_info = collect_debug_info($query);
				
				$time = microtime(true) - $debug_info['start_time'];
				$debug['sql'][] = array(
					'query' => $query,
					'rows' => $result ? $result->rowCount() : 0,
					'explain' => $debug_info['explain'],
					'time' => '~' . round($time * 1000, 2) . 'ms'
				);
				$debug['time']['db_queries'] += $time;
			}
			
			return $result;
		} catch (PDOException $e) {
			$error_message = $e->getMessage();
			
			// Handle table not found error (expected during installation)
			if (strpos($error_message, 'Base table or view not found') !== false ||
			    strpos($error_message, '1146') !== false) {
				return false;
			} 
			
			// Handle connection errors
			if (strpos($error_message, 'Connection refused') !== false ||
			    strpos($error_message, 'Access denied') !== false) {
				// Mask sensitive information
				$error_message = mask_sensitive_info($error_message, $config);
				
				// Use install error handler if available
				if (function_exists('install_error')) {
					install_error('Database Connection Error', $error_message);
				} else {
					die_generic_error($error_message);
				}
			}
			
			// Handle other unexpected errors
			$error_message = mask_sensitive_info($error_message, $config);
			
			// Use install error handler if available, otherwise throw error
			if (defined('INSTALLING') && function_exists('install_error')) {
				install_error('Database Query Error', $error_message);
			} else {
				// Log the error for debugging
				if ($config['debug']) {
					error_log("Database Error: " . $error_message);
				}
				throw $e;
			}
		}
	}
	
	if ($config['debug']) {
		$start = microtime(true);
		
		// Execute the query
		$result = $pdo->query($query);
		if (!$result) {
			return false;
		}
		
		$time = microtime(true) - $start;
		
		// Collect and store debug information
		$explain = null;
		if ($config['debug_explain'] && preg_match('/^(SELECT|INSERT|UPDATE|DELETE) /i', $query)) {
			$explain_result = $pdo->query("EXPLAIN $query");
			if ($explain_result) {
				$explain = $explain_result->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		
		$debug['sql'][] = array(
			'query' => $query,
			'rows' => $result->rowCount(),
			'explain' => $explain,
			'time' => '~' . round($time * 1000, 2) . 'ms'
		);
		$debug['time']['db_queries'] += $time;
		
		return $result;
	}

	// Normal query execution without debug
	return $pdo->query($query);
}

/**
 * Collects debug information for a query
 */
function collect_debug_info($query) {
	$start_time = microtime(true);
	$explain = null;
	
	if ($GLOBALS['config']['debug_explain'] && preg_match('/^(SELECT|INSERT|UPDATE|DELETE) /i', $query)) {
		$explain_result = $GLOBALS['pdo']->query("EXPLAIN $query");
		if ($explain_result) {
			$explain = $explain_result->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	
	return array(
		'start_time' => $start_time,
		'explain' => $explain
	);
}

/**
 * Masks sensitive information in error messages
 */
function mask_sensitive_info($message, $config) {
	$message = str_replace($config['db']['user'], '<em>hidden</em>', $message);
	$message = str_replace($config['db']['password'], '<em>hidden</em>', $message);
	return $message;
}

/**
 * Displays a generic error message
 */
function die_generic_error($message) {
	die('<!DOCTYPE html>
<html>
<head>
	<title>Installation Error</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
		.error { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
		h1 { color: #c62828; margin-top: 0; }
	</style>
</head>
<body>
	<div class="error">
		<h1>Database Error</h1>
		<p>' . htmlspecialchars($message) . '</p>
		<p><a href="install.php">Back to installation</a></p>
	</div>
</body>
</html>');
}

function db_error($PDOStatement = null) {
	global $pdo, $db_error;

	if (isset($PDOStatement)) {
		$db_error = $PDOStatement->errorInfo();
		return $db_error[2];
	}

	$db_error = $pdo->errorInfo();
	return $db_error[2];
}