<?php
/**
 * Installer main english strings declaration file
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     installer
 * @since       2.3.0
 * @author      Haruki Setoyama  <haruki@planewave.org>
 * @author      Kazumi Ono <webmaster@myweb.ne.jp>
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Taiwen Jiang <phppp@users.sourceforge.net>
 * @author      dugris <dugris@frxoops.org>
 * @version     $Id$
 */

// _LANGCODE: en
// _CHARSET : UTF-8
// Translator: XOOPS Translation Team


// Configuration check page
define("SERVER_API", "Server API");
define("PHP_EXTENSION", "%s extension");
define("CHAR_ENCODING", "Character encoding");
define("XML_PARSING", "XML parsing");
define("REQUIREMENTS", "Requirements");
define("_PHP_VERSION", "PHP version");
define("RECOMMENDED_EXTENSIONS", "Recommended extensions");
define("RECOMMENDED", "Recommended");
define("CURRENT", "Current");
define("COMPOSER","Composer");
define("COMPOSER_ENVIRONMENT",'Composer required. <a href="http://getcomposer.org/" target="_blank">http://getcomposer.org/</a>');

define("RECOMMENDED_EXTENSIONS_MSG", "These extensions are not required for normal use, but may be necessary to exploit
    some specific features (like the multi-language or RSS support). Thus, it is recommended to have them installed.");
define("NONE", "None");
define("SUCCESS", "Success");
define("WARNING", "Warning");
define("FAILED", "Failed");
define("ENABLE", "Enable");

// Titles (main and pages)
define("XOOPS_INSTALL_WIZARD", "XOOPS Setup Wizard");

define("LANGUAGE_SELECTION", "Language selection");
define("LANGUAGE_SELECTION_TITLE", "Choose your language"); // L128
define("INTRODUCTION", "Introduction");
define("INTRODUCTION_TITLE", "Welcome to the XOOPS installation assistant"); // L0
define("CONFIGURATION_CHECK", "Configuration check");
define("CONFIGURATION_CHECK_TITLE", "Checking your server configuration");
define("PATHS_SETTINGS", "Paths settings");
define("PATHS_SETTINGS_TITLE", "Paths settings");
define("DATABASE_DRIVER", "Database driver");
define("DATABASE_DRIVER_TITLE", "Database driver selection");
define("DATABASE_CONNECTION", "Database connection");
define("DATABASE_CONNECTION_TITLE", "Database connection");
define("DATABASE_CONFIG", "Database configuration");
define("DATABASE_CONFIG_TITLE", "Database configuration");
define("CONFIG_SAVE", "Configuration save");
define("CONFIG_SAVE_TITLE", "Saving your system configuration");
define("INITIAL_SETTINGS", "Initial settings");
define("INITIAL_SETTINGS_TITLE", "Please enter your initial settings");
define("DATA_INSERTION", "Data insertion");
define("DATA_INSERTION_TITLE", "Saving your settings to the database");
define("WELCOME", "Welcome");
define("WELCOME_TITLE", "Welcome to your XOOPS site"); // L0
define("HELP_BUTTON_ALT", "Turn on help messages");



define("XOOPS_ROOT_PATH_LABEL", "XOOPS documents root physical path");
define("XOOPS_ROOT_PATH_HELP", "Physical path to the XOOPS documents (served) directory WITHOUT trailing slash");

define("XOOPS_LIB_PATH_LABEL", "XOOPS library directory");
define("XOOPS_LIB_PATH_HELP", "Physical path to the XOOPS library directory WITHOUT trailing slash, for forward compatibility. Locate the folder out of " . XOOPS_ROOT_PATH_LABEL . " to make it secure.");
define("XOOPS_DATA_PATH_LABEL", "XOOPS datafiles directory");
define("XOOPS_DATA_PATH_HELP", "Physical path to the XOOPS datafiles (writable) directory WITHOUT trailing slash, for forward compatibility. Locate the folder out of " . XOOPS_ROOT_PATH_LABEL . " to make it secure.");

define("XOOPS_URL_LABEL", "Website location (URL)"); // L56
define("XOOPS_URL_HELP", "Main URL that will be used to access your XOOPS installation"); // L58

define("LEGEND_DRIVER", "Server driver");
define("LEGEND_CONNECTION", "Server connection");
define("LEGEND_DATABASE", "Database"); // L51

define("DB_HOST_LABEL", "Server hostname"); // L27
define("DB_HOST_HELP", "Hostname of the database server. If you are unsure, <em>localhost</em> works in most cases"); // L67
define("DB_USER_LABEL", "User name"); // L28
define("DB_USER_HELP", "Name of the user account that will be used to connect to the database server"); // L65
define("DB_PASS_LABEL", "Password"); // L52
define("DB_PASS_HELP", "Password of your database user account"); // L68
define("DB_NAME_LABEL", "Database name"); // L29
define("DB_NAME_HELP", "The name of database on the host. The installer will attempt to create the database if not exist"); // L64
define("DB_CHARSET_LABEL", "Database character set");
define("DB_CHARSET_HELP", "MySQL includes character set support that enables you to store data using a variety of character sets and perform comparisons according to a variety of collations.");
define("DB_COLLATION_LABEL", "Database collation");
define("DB_COLLATION_HELP", "A collation is a set of rules for comparing characters in a character set.");
define("DB_PREFIX_LABEL", "Table prefix"); // L30
define("DB_PREFIX_HELP", "This prefix will be added to all new tables created to avoid name conflicts in the database. If you are unsure, just keep the default"); // L63
define("DB_PCONNECT_LABEL", "Use persistent connection"); // L54
define("DB_PCONNECT_HELP", "Default is 'No'. Leave it blank if you are unsure"); // L69
define("DB_DATABASE_LABEL", "Database");
define("DB_DRIVER_LABEL", "Driver");
define("DB_DRIVER_HELP", "Select the driver to use to communicate with your database.");
define("DB_PORT_LABEL", "Port");
define("DB_PORT_HELP", "The connection port for your database. This is often optional.");
define("DB_SOCK_LABEL", "Socket");
define("DB_SOCK_HELP", "UNIX Socket used to connect to the server. Only specify if required.");
define("DB_PATH_LABEL", "Path");
define("DB_PATH_HELP", "The full filesystem path to the database file.");
define("DB_SERVICE_LABEL", "Service");
define("DB_SERVICE_HELP", "Use service mode");
define("DB_POOLED_LABEL", "Pooled");
define("DB_POOLED_HELP", "Use pooled connections");
define("DB_PROTOCOL_LABEL", "Protocol");
define("DB_PROTOCOL_HELP", "Connection protocol");
define("DB_AVAILABLE_LABEL", "Available databases");
define("DB_AVAILABLE_HELP", "You can select a database from this list, or enter a new name to create a new one.");

define("LEGEND_ADMIN_ACCOUNT", "Administrator account");
define("ADMIN_LOGIN_LABEL", "Admin login"); // L37
define("ADMIN_EMAIL_LABEL", "Admin e-mail"); // L38
define("ADMIN_PASS_LABEL", "Admin password"); // L39
define("ADMIN_CONFIRMPASS_LABEL", "Confirm password"); // L74

// Buttons
define("BUTTON_PREVIOUS", "Previous"); // L42
define("BUTTON_NEXT", "Next"); // L47

// Messages
define("XOOPS_FOUND", "%s found");
define("CHECKING_PERMISSIONS", "Checking file and directory permissions..."); // L82
define("IS_NOT_WRITABLE", "%s is NOT writable."); // L83
define("IS_WRITABLE", "%s is writable."); // L84

define("XOOPS_PATH_FOUND", "Path found.");

define("SAVED_MAINFILE", "Saved settings in mainfile.php");
define("SAVED_MAINFILE_MSG", "The installer has saved the specified settings to <em>mainfile.php</em> and <em>secure.php</em>. Press <em>next</em> to go to the next step.");
define("DATA_ALREADY_INSERTED", "XOOPS data found in database.<br />Press <em>next</em> to go to the next step.");
define("DATA_INSERTED", "Initial data have been inserted into database.<br />Press <em>next</em> to go to the next step.");


// Error messages
define("ERR_COULD_NOT_ACCESS", "Can not access the folder.");
define("ERR_NO_XOOPS_FOUND", "No instalable XOOPS found.");
define("ERR_INVALID_EMAIL", "Invalid Email"); // L73
define("ERR_REQUIRED", "Information is required."); // L41
define("ERR_PASSWORD_MATCH", "The two passwords do not match");
define("ERR_NEED_WRITE_ACCESS", "The server must be given write access to the following files and folders<br />(i.e. <em>chmod 777 directory_name</em> on a UNIX/LINUX server)<br />If they are not available or not created correctly, please create manually and set proper permissions.");
define("ERR_NO_DATABASE", "Could not create database. Contact the server administrator for details."); // L31
define("ERR_NO_DBCONNECTION", "Could not connect to the database server."); // L106
define("ERR_WRITING_CONSTANT", "Failed writing constant %s."); // L122
define("ERR_NO_CREATEDB", "Cannot create database on this platform.");

define("ERR_ALREADY_INSTALLED", "mainfile.php or secure.php (in the data folder of the XOOPS data directory) already exists, so this site is already installed. To install again, check that the paths you entered are this site's, delete both files and restart the installer.");
define("ERR_COPY_MAINFILE", "Could not copy the distribution file to mainfile.php");
define("ERR_WRITE_MAINFILE", "Could not write into mainfile.php. Please check the file permission and try again.");
define("ERR_READ_MAINFILE", "Could not open mainfile.php for reading");

define("ERR_COPY_SECURE", "Could not copy into secure.php the distribution file: ");
define("ERR_READ_SECURE","Could not open secure.php for reading");
define("ERR_WRITE_SECURE","Could not write into secure.php. Please check the file permission and try again.");

define("ERR_INVALID_DBCHARSET", "The charset '%s' is not supported.");
define("ERR_INVALID_DBCOLLATION", "The collation '%s' is not supported.");
define("ERR_CHARSET_NOT_SET", "Default character set is not set for XOOPS database.");


define("_INSTALL_CHARSET", "UTF-8");
define("_LANGCODE", "en-US");

define("SUPPORT", "Supports");

define("LOGIN", "Authentication");
//define("LOGIN_TITLE", "Authentication");
//define("USER_LOGIN", "Administrator Login");
//define("USERNAME", "Username :");
define("PASSWORD", "Password :");

define("ICONV_CONVERSION", "Character set conversion");
define("ZLIB_COMPRESSION", "Zlib Compression");
define("IMAGE_FUNCTIONS", "Image functions");
define("IMAGE_METAS", "Image meta data (exif)");
define("CURL_HTTP", "Client URL Library (cURL)");

define("ADMIN_EXIST", "The administrator account already exists.<br />Press <strong>next</strong> to go to the next step.");

define("CONFIG_SITE", "Site configuration");
define("CONFIG_SITE_TITLE", "Site configuration");
define("MODULES", "Modules installation");
define("MODULES_TITLE", "Modules installation");
define("EXTENSIONS", "Extensions installation");
define("EXTENSIONS_TITLE", "Extensions installation");
define("THEME", "Select theme");
define("THEME_TITLE", "Choose the default theme");

define("INSTALLED_MODULES", "The following modules have been installed.<br />Press <strong>next</strong> to go to the next step.");
define("NO_MODULES_FOUND", "No modules found.<br />Press <strong>next</strong> to go to the next step.");
define("NO_INSTALLED_MODULES", "No module installed.<br />Press <strong>next</strong> to go to the next step.");

define("INSTALLED_EXTENSION", "The following extensions have been installed.<br />Press <strong>next</strong> to go to the next step.");
define("NO_EXTENSION_FOUND", "No extensions found.<br />Press <strong>next</strong> to go to the next step.");
define("NO_INSTALLED_EXTENSION", "No extension installed.<br />Press <strong>next</strong> to go to the next step.");


define("THEME_NO_SCREENSHOT", "No screenshot found");

define("IS_VALOR", " => ");

// password message
define("PASSWORD_LABEL", "Password strength : ");
define("PASSWORD_DESC", "Password not entered");
define("PASSWORD_GENERATOR", "Password generator");
define("PASSWORD_GENERATE", "Generate");
define("PASSWORD_COPY", "Copy");

define("PASSWORD_VERY_WEAK", "Very Weak");
define("PASSWORD_WEAK", "Weak");
define("PASSWORD_BETTER", "Better");
define("PASSWORD_MEDIUM", "Medium");
define("PASSWORD_STRONG", "Strong");
define("PASSWORD_STRONGEST", "Strongest");
