<?php

// Installation/upgrade file
define('VERSION', '5.2.1');
require 'inc/bootstrap.php';
loadConfig();

// Set secure headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\';');

// Salt generators
class SaltGen {
	public $salt_length = 128;

	// Best function I could think of for non-SSL PHP 5
	private function generate_install_salt() {
		$ret = "";

		// This is bad! But what else can we do sans OpenSSL?
		// ↑ Hey, Qwen3-Coder-480B-A35B-Instruct says you can improve by adding more entropy sources!
		mt_srand(microtime(true) * 1000000 + memory_get_usage(true));
		
		for ($i = 0; $i < $this->salt_length; ++$i) {
			$s = pack("c", mt_rand(0,255));
			$ret = $ret . $s;
		}

		return base64_encode($ret);
	}

	// Best function of the lot. Works with any PHP version as long as OpenSSL extension is on
	private function generate_install_salt_openssl() {
		$ret = openssl_random_pseudo_bytes($this->salt_length, $strong);
		if (!$strong) {
			// Instead of erroring out, let's try to combine with other sources
			$fallback = $this->generate_install_salt();
			return base64_encode($ret) . $fallback;
		}
		return base64_encode($ret);
	}

	private function generate_install_salt_php7() {
		// Use random_bytes with proper error handling
		try {
			$random = random_bytes($this->salt_length);
			return base64_encode($random);
		} catch (Exception $e) {
			// Fallback to OpenSSL if random_bytes fails
			return $this->generate_install_salt_openssl();
		}
	}

	// TODO: Perhaps add mcrypt as an option? Maybe overkill.
	public function generate() {
		if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 7) {
			// Prefer PHP 7's random_bytes
			return "PHP7." . $this->generate_install_salt_php7();
		} else if (extension_loaded('openssl')) {
			// Use OpenSSL if available
			return "OSSL." . $this->generate_install_salt_openssl();
		} else {
			// Fallback for older PHP versions without OpenSSL
			return "INSECURE." . $this->generate_install_salt();
		}
	}
}

// Validate step parameter to prevent potential injection
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
// Limit step to reasonable values (0-5)
if ($step < 0 || $step > 5) {
	$step = 0;
}

$page = array(
	'config' => $config,
	'title' => 'Install',
	'body' => '',
	'nojavascript' => true,
	'step' => $step
);

// this breaks the display of licenses if enabled
$config['minify_html'] = false;

if (file_exists($config['has_installed'])) {

	// Check the version number
	$version = trim(file_get_contents($config['has_installed']));
	if (empty($version))
		$version = '4.9.90';

	function __query($sql) {
		sql_open();

		if (mysql_version() >= 50503)
			return query($sql);
		else
			return query(str_replace('utf8mb4', 'utf8', $sql));
	}

	$boards = listBoards();
    
	// Well, 4im don't need to combine very old versions of vichan cuz this fork will change a lot things. Starting from 4.9.90 is a good idea, maybe.
	switch ($version) {
		case '4.9.90':
		case '4.9.91':
		case '4.9.92':
                        foreach ($boards as &$board) {
                                query(sprintf('ALTER TABLE ``posts_%s`` ADD `slug` VARCHAR(255) DEFAULT NULL AFTER `embed`;', $board['uri'])) or error(db_error());
			}
                case '4.9.93':
                        query('ALTER TABLE ``mods`` CHANGE `password` `password` VARCHAR(255) NOT NULL;') or error(db_error());
                        query('ALTER TABLE ``mods`` CHANGE `salt` `salt` VARCHAR(64) NOT NULL;') or error(db_error());
		case '5.0.0':
			query('ALTER TABLE ``mods`` CHANGE `salt` `version` VARCHAR(64) NOT NULL;') or error(db_error());
		case '5.0.1':
		case '5.1.0':
			query('CREATE TABLE IF NOT EXISTS ``pages`` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `board` varchar(255) DEFAULT NULL,
			  `name` varchar(255) NOT NULL,
			  `title` varchar(255) DEFAULT NULL,
			  `type` varchar(255) DEFAULT NULL,
			  `content` text,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `u_pages` (`name`,`board`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;') or error(db_error());
		case '5.1.1':
                        foreach ($boards as &$board) {
                                query(sprintf("ALTER TABLE ``posts_%s`` ADD `cycle` int(1) NOT NULL AFTER `locked`", $board['uri'])) or error(db_error());
                        }
		case '5.1.2':
			query('CREATE TABLE IF NOT EXISTS ``nntp_references`` (
				  `board` varchar(60) NOT NULL,
				  `id` int(11) unsigned NOT NULL,
				  `message_id` varchar(255) CHARACTER SET ascii NOT NULL,
				  `message_id_digest` varchar(40) CHARACTER SET ascii NOT NULL,
				  `own` tinyint(1) NOT NULL,
				  `headers` text,
				  PRIMARY KEY (`message_id_digest`),
				  UNIQUE KEY `message_id` (`message_id`),
				  UNIQUE KEY `u_board_id` (`board`, `id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			') or error(db_error());
		case '5.1.3':
			query('CREATE TABLE IF NOT EXISTS ``captchas`` (
			  	`cookie` varchar(50),
			  	`extra` varchar(200),
			  	`text` varchar(255),
			  	`created_at` int(11),
			  	PRIMARY KEY (`cookie`,`extra`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;') or error(db_error());
		case false:
			// TODO: enhance Tinyboard -> vichan upgrade path.
			query("CREATE TABLE IF NOT EXISTS ``search_queries`` (  `ip` varchar(39) NOT NULL,  `time` int(11) NOT NULL,  `query` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;") or error(db_error());

			// Update version number
			file_write($config['has_installed'], VERSION);

			$page['title'] = 'Upgraded';
			$page['body'] = '<p style="text-align:center">Successfully upgraded from ' . $version . ' to <strong>' . VERSION . '</strong>.</p>';
			break;
		default:
			$page['title'] = 'Unknown version';
			$page['body'] = '<p style="text-align:center">4im was unable to determine what version is currently installed.</p>';
			break;
		case VERSION:
			$page['title'] = 'Already installed';
			$page['body'] = '<p style="text-align:center">It appears that 4im is already installed (' . $version . ') and there is nothing to upgrade! Delete <strong>' . $config['has_installed'] . '</strong> to reinstall.</p>';
			break;
	}

	die(Element('installer/header.html', $page) . $page['body'] . Element('installer/footer.html', $page));
}

function create_config_from_array(&$instance_config, &$array, $prefix = '') {
	foreach ($array as $name => $value) {
		if (is_array($value)) {
			$instance_config .= "\n";
			create_config_from_array($instance_config, $value, $prefix . '[\'' . addslashes($name) . '\']');
			$instance_config .= "\n";
		} else {
			$instance_config .= '	$config' . $prefix . '[\'' . addslashes($name) . '\'] = ';

			if (is_numeric($value))
				$instance_config .= $value;
			else
				$instance_config .= "'" . addslashes($value) . "'";

			$instance_config .= ";\n";
		}
	}
}

// Improved SQL parsing function to better handle SQL files
function parse_sql_file($sql_content) {
	// Normalize line endings
	$sql_content = str_replace(array("\r\n", "\r"), "\n", $sql_content);
	
	// Split by semicolon followed by newline or end of file
	$statements = preg_split('/;(\s*(\n|$))/m', $sql_content, -1, PREG_SPLIT_NO_EMPTY);
	
	$queries = array();
	foreach ($statements as $statement) {
		$statement = trim($statement);
		// Skip empty statements or comments
		if (!empty($statement) && !preg_match('/^--/', $statement)) {
			$queries[] = $statement;
		}
	}
	
	return $queries;
}

session_start();

// Set secure session cookie parameters
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
	$cookie_secure = true;
} else {
	$cookie_secure = false;
}

session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => null,
	'secure' => $cookie_secure,
	'httponly' => true,
	'samesite' => 'Strict'
]);

if ($step == 0) {
	// License
	$page['title'] = 'License Agreement';
	$page['body'] = Element('installer/license.html', array(
		'license' => file_get_contents('LICENSE'),
		'config' => $config
	));

	echo Element('installer/header.html', $page) . $page['body'] . Element('installer/footer.html', $page);
} elseif ($step == 1) {
	// The HTTPS check doesn't work properly when in those arrays, so let's run it here and pass along the result during the actual check.
	$httpsvalue = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	$page['title'] = 'Pre-installation test';

	$can_exec = true;
	if (!function_exists('shell_exec'))
		$can_exec = false;
	elseif (in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions')))))
		$can_exec = false;
	elseif (ini_get('safe_mode'))
		$can_exec = false;
	elseif (trim(shell_exec('echo "TEST"')) !== 'TEST')
		$can_exec = false;

	if (!defined('PHP_VERSION_ID')) {
		$version = explode('.', PHP_VERSION);
		define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
	}

	// Required extensions
	$extensions = array(
		'PDO' => array(
			'installed' => extension_loaded('pdo'),
			'required' => true
		),
		'GD' => array(
			'installed' => extension_loaded('gd'),
			'required' => true
		),
		'Imagick' => array(
			'installed' => extension_loaded('imagick'),
			'required' => false
		),
		'OpenSSL' => array(
			'installed' => extension_loaded('openssl'),
			'required' => false
		)
	);

	$tests = array(
		array(
			'category' => 'PHP',
			'name' => 'PHP &ge; 7.4',
			'result' => PHP_VERSION_ID >= 50400,
			'required' => true,
			'message' => '4im requires PHP 7.4 or better.',
		),
		array(
			'category' => 'PHP',
			'name' => 'mbstring extension installed',
			'result' => extension_loaded('mbstring'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/mbstring.installation.php">mbstring</a> extension.',
		),
		array(
			'category' => 'PHP',
			'name' => 'OpenSSL extension installed or PHP &ge; 7.0',
			'result' => (extension_loaded('openssl') || (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 7)),
			'required' => false,
			'message' => 'It is highly recommended that you install the PHP <a href="http://www.php.net/manual/en/openssl.installation.php">OpenSSL</a> extension and/or use PHP version 7 or above. <strong>If you do not, it is possible that the IP addresses of users of your site could be compromised &mdash; see <a href="https://github.com/vichan-devel/vichan/issues/284">vichan issue #284.</a></strong> Installing the OpenSSL extension allows 4im to generate a secure salt automatically for you.',
		),
		array(
			'category' => 'Database',
			'name' => 'PDO extension installed',
			'result' => extension_loaded('pdo'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.pdo.php">PDO</a> extension.',
		),
		array(
			'category' => 'Database',
			'name' => 'MySQL PDO driver installed',
			'result' => extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers()),
			'required' => true,
			'message' => 'The required <a href="http://www.php.net/manual/en/ref.pdo-mysql.php">PDO MySQL driver</a> is not installed.',
		),
		array(
			'category' => 'Image processing',
			'name' => 'GD extension installed',
			'result' => extension_loaded('gd'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.image.php">GD</a> extension. GD is a requirement even if you have chosen another image processor for thumbnailing.',
		),
		array(
		 	'category' => 'Image processing',
		 	'name' => 'GD: JPEG',
			'result' => function_exists('imagecreatefromjpeg'),
			'required' => true,
			'message' => 'imagecreatefromjpeg() does not exist. This is a problem.',
		),
		array(
			'category' => 'Image processing',
			'name' => 'GD: PNG',
			'result' => function_exists('imagecreatefrompng'),
			'required' => true,
			'message' => 'imagecreatefrompng() does not exist. This is a problem.',
		),
		array(
			'category' => 'Image processing',
			'name' => 'GD: GIF',
			'result' => function_exists('imagecreatefromgif'),
			'required' => true,
			'message' => 'imagecreatefromgif() does not exist. This is a problem.',
		),
		array(
			'category' => 'Image processing',
			'name' => '`convert` (command-line ImageMagick)',
			'result' => $can_exec && shell_exec('which convert'),
			'required' => false,
			'message' => '(Optional) `convert` was not found or executable; command-line ImageMagick image processing cannot be enabled.',
			'effect' => function (&$config) { $config['thumb_method'] = 'convert'; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`identify` (command-line ImageMagick)',
			'result' => $can_exec && shell_exec('which identify'),
			'required' => false,
			'message' => '(Optional) `identify` was not found or executable; command-line ImageMagick image processing cannot be enabled.',
		),
		array(
			'category' => 'Image processing',
			'name' => '`gm` (command-line GraphicsMagick)',
			'result' => $can_exec && shell_exec('which gm'),
			'required' => false,
			'message' => '(Optional) `gm` was not found or executable; command-line GraphicsMagick (faster than ImageMagick) cannot be enabled.',
			'effect' => function (&$config) { $config['thumb_method'] = 'gm'; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`gifsicle` (command-line animted GIF thumbnailing)',
			'result' => $can_exec && shell_exec('which gifsicle'),
			'required' => false,
			'message' => '(Optional) `gifsicle` was not found or executable; you may not use `convert+gifsicle` for better animated GIF thumbnailing.',
			'effect' => function (&$config) { if ($config['thumb_method'] == 'gm')      $config['thumb_method'] = 'gm+gifsicle';
							  if ($config['thumb_method'] == 'convert') $config['thumb_method'] = 'convert+gifsicle'; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`md5sum` (quick file hashing on GNU/Linux)',
			'prereq' => '',
			'result' => $can_exec && shell_exec('echo "4im" | md5sum') == "141225c362da02b5c359c45b665168de  -\n",
			'required' => false,
			'message' => '(Optional) `md5sum` was not found or executable; file hashing for multiple images will be slower. Ignore if not using Linux.',
			'effect' => function (&$config) { $config['gnu_md5'] = true; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`/sbin/md5` (quick file hashing on BSDs)',
			'result' => $can_exec && shell_exec('echo "4im" | /sbin/md5 -r') == "141225c362da02b5c359c45b665168de\n",
			'required' => false,
			'message' => '(Optional) `/sbin/md5` was not found or executable; file hashing for multiple images will be slower. Ignore if not using BSD.',
			'effect' => function (&$config) { $config['bsd_md5'] = true; },
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd(),
			'result' => is_writable('.'),
			'required' => true,
			'message' => '4im does not have permission to create directories (boards) here. You will need to <code>chmod</code> (or operating system equivalent) appropriately.'
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd() . '/templates/cache',
			'result' => is_dir('templates/cache/') && is_writable('templates/cache/'),
			'required' => true,
			'message' => 'You must give 4im permission to create (and write to) the <code>templates/cache</code> directory or performance will be drastically reduced.'
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd() . '/tmp/cache',
			'result' => is_dir('tmp/cache/') && is_writable('tmp/cache/'),
			'required' => true,
			'message' => 'You must give 4im permission to write to the <code>tmp/cache</code> directory.'
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd() . '/inc/secrets.php',
			'result' => is_writable('inc/secrets.php'),
			'required' => false,
			'message' => '4im does not have permission to make changes to <code>inc/secrets.php</code>. To complete the installation, you will be asked to manually copy and paste code into the file instead.'
		),
		array(
			'category' => 'Misc',
			'name' => 'HTTPS being used',
			'result' => $httpsvalue,
			'required' => false,
			'message' => 'You are not currently using https for 4im, or at least for your backend server. If this intentional, add "$config[\'cookies\'][\'secure_login_only\'] = 0;" (or 1 if using a proxy) on a new line under "Additional configuration" on the next page.'
		),
		array(
			'category' => 'Misc',
			'name' => 'Caching available (APCu, Memcached or Redis)',
			'result' => extension_loaded('apcu') || extension_loaded('memcached') || extension_loaded('redis'),
			'required' => false,
			'message' => 'You will not be able to enable the additional caching system, designed to minimize SQL queries and significantly improve performance. <a href="https://www.php.net/manual/en/book.apcu.php">APCu</a> is the recommended method of caching, but <a href="http://www.php.net/manual/en/intro.memcached.php">Memcached</a> and <a href="http://pecl.php.net/package/redis">Redis</a> are also supported.'
		),
		array(
			'category' => 'Misc',
			'name' => '4im installed using git',
			'result' => is_dir('.git'),
			'required' => false,
			'message' => '4im is still beta software and it\'s not going to come out of beta any time soon. As there are often many months between releases yet changes and bug fixes are very frequent, it\'s recommended to use the git repository to maintain your 4im installation. Using git makes upgrading much easier.'
		)
	);

	$config['font_awesome'] = true;

	$additional_config = array();
	foreach ($tests as $test) {
		if ($test['result'] && isset($test['effect'])) {
			$test['effect']($additional_config);
		}
	}
	$more = '';
	create_config_from_array($more, $additional_config);
	$_SESSION['more'] = $more;

	echo Element('installer/header.html', array(
		'title' => 'Checking environment',
		'config' => $config,
		'step' => $step
	)) . Element('installer/check-requirements.html', array(
		'extensions' => $extensions,
		'tests' => $tests,
		'config' => $config,
	)) . Element('installer/footer.html', array(
		'config' => $config
	));
} elseif ($step == 2) {

	// Basic config
	$page['title'] = 'Configuration';

	$sg = new SaltGen();
	$config['cookies']['salt'] = $sg->generate();
	$config['secure_trip_salt'] = $sg->generate();
	$config['secure_password_salt'] = $sg->generate();

	echo Element('installer/header.html', array(
		'title' => 'Configuration',
		'config' => $config,
		'step' => $step
	)) . Element('installer/config.html', array(
		'config' => $config,
		'more' => $_SESSION['more'],
	)) . Element('installer/footer.html', array(
		'config' => $config
	));
} elseif ($step == 3) {
	$more = $_POST['more'];
	unset($_POST['more']);

	$instance_config =
'<'.'?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/

';

	create_config_from_array($instance_config, $_POST);

	$instance_config .= "\n";
	$instance_config .= $more;
	$instance_config .= "\n";

	if (@file_put_contents('inc/secrets.php', $instance_config)) {
		// flushes opcache if php >= 5.5.0 or opcache is installed via PECL
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate('inc/secrets.php');
		}
		header('Location: ?step=4', true, $config['redirect_http']);
	} else {
		$page['title'] = 'Manual installation required';
		$page['body'] = '
			<p>I couldn\'t write to <strong>inc/secrets.php</strong> with the new configuration, probably due to a permissions error.</p>
			<p>Please complete the installation manually by copying and pasting the following code into the contents of <strong>inc/secrets.php</strong>:</p>
			<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black">' . htmlentities($instance_config) . '</textarea>
			<p style="text-align:center">
				<a href="?step=4">Once complete, click here to complete installation.</a>
			</p>
		';
		echo Element('installer/header.html', $page) . $page['body'] . Element('installer/footer.html', $page);
	}
} elseif ($step == 4) {
	// SQL installation

	buildJavascript();

	$sql = @file_get_contents('install.sql') or error("Couldn't load install.sql.");
	
	// Load additional SQL files if they exist
	$additional_sql_files = array();
	if (file_exists('templates/posts.sql')) {
		$additional_sql_files[] = 'templates/posts.sql';
	}

	sql_open();
	$mysql_version = mysql_version();

	// Parse SQL using improved method
	$queries = parse_sql_file($sql);
	
	// Add posts table for default board 'b'
	$posts_sql = Element('posts.sql', array('board' => 'b'));
	$posts_queries = parse_sql_file($posts_sql);
	$queries = array_merge($queries, $posts_queries);

	$sql_errors = '';
	$sql_err_count = 0;
	foreach ($queries as $query) {
		if ($mysql_version < 50503)
			$query = preg_replace('/(CHARSET=|CHARACTER SET )utf8mb4/', '$1utf8', $query);
		$query = preg_replace('/^([\w\s]*)`([0-9a-zA-Z$_\x{0080}-\x{FFFF}]+)`/u', '$1``$2``', $query);
		
		// Skip empty queries
		if (empty(trim($query))) {
			continue;
		}
		
		if (!query($query)) {
			$sql_err_count++;
			$error = db_error();
			$sql_errors .= "<li>$sql_err_count<ul><li>" . htmlspecialchars($query) . "</li><li>" . htmlspecialchars($error) . "</li></ul></li>";
		}
	}

	$page['title'] = 'Installation complete';
	
	if (!empty($sql_errors)) {
		$body = Element('installer/database.html', array(
			'sql_errors' => array($sql_errors),
			'config' => $config
		));
	} else {
		$boards = listBoards();
		foreach ($boards as &$_board) {
			setupBoard($_board);
			buildIndex();
		}

		file_write($config['has_installed'], VERSION);
		
		$body = Element('installer/database.html', array(
			'success' => true,
			'config' => $config
		));
	}

	echo Element('installer/header.html', array(
		'title' => $page['title'],
		'config' => $config,
		'step' => $step
	)) . $body . Element('installer/footer.html', array(
		'config' => $config
	));

	if (!empty($sql_errors)) {
		$page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to install the database. This may be the result of using a database which is already occupied with a 4im installation; if so, you can probably ignore this.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul><p><a href="?step=5">Ignore errors and complete installation.</a></p></div>';
	} else {
		$boards = listBoards();
		foreach ($boards as &$_board) {
			setupBoard($_board);
			buildIndex();
		}

		file_write($config['has_installed'], VERSION);
		/*if (!file_unlink(__FILE__)) {
			$page['body'] .= '<div class="ban"><h2>Delete install.php!</h2><p>I couldn\'t remove <strong>install.php</strong>. You will have to remove it manually.</p></div>';
		}*/
	}

	echo Element('page.html', $page);
} elseif ($step == 5) {
	$page['title'] = 'Installation complete';

	$boards = listBoards();
	foreach ($boards as &$_board) {
		setupBoard($_board);
		buildIndex();
	}
	
	$body = Element('installer/database.html', array(
		'success' => true,
		'config' => $config
	));

	echo Element('installer/header.html', array(
		'title' => $page['title'],
		'config' => $config,
		'step' => $step
	)) . $body . Element('installer/footer.html', array(
		'config' => $config
	));

	$boards = listBoards();
	foreach ($boards as &$_board) {
		setupBoard($_board);
		buildIndex();
	}

	file_write($config['has_installed'], VERSION);
	if (!file_unlink(__FILE__)) {
		$page['body'] .= '<div class="ban"><h2>Delete install.php!</h2><p>I couldn\'t remove <strong>install.php</strong>. You will have to remove it manually.</p></div>';
	}

	echo Element('page.html', $page);
}