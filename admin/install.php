<?php

define ('OWL_ROOT', '/var/owl-php');
require (OWL_ROOT . '/OWLinstaller.php');

//OWLinstaller::installTables('/var/owl-php/owl.tables.sql', false);

$_id = OWLinstaller::installApplication('OWL', 'owladmin', OWL_VERSION, 'Oveas Web Library for PHP', 'https://github.com/oveas/owl-php', 'Oscar van Eijk', 'LGPL');

OWLinstaller::addConfig($_id, 'locale', 'date', 'd-M-Y');
OWLinstaller::addConfig($_id, 'locale', 'time', 'H:i');
OWLinstaller::addConfig($_id, 'locale', 'datetime', 'd-M-Y H:i:s');
OWLinstaller::addConfig($_id, 'locale', 'log_date', 'd-m-Y');
OWLinstaller::addConfig($_id, 'locale', 'log_time', 'H:i:s.u');
OWLinstaller::addConfig($_id, 'locale', 'lang', 'en-UK');
OWLinstaller::addConfig($_id, 'session', 'lifetime', '1440');
OWLinstaller::addConfig($_id, 'session', 'pwd_minstrength', '2');
OWLinstaller::addConfig($_id, 'session', 'check_ip', 'true');
OWLinstaller::addConfig($_id, 'session', 'default_user', 'anonymous', true);
OWLinstaller::addConfig($_id, 'logging', 'log_form_data', 'true');
OWLinstaller::addConfig($_id, 'user', 'default_group', 'standard');
OWLinstaller::addConfig($_id, 'session', 'default_rights_all', '1', true);
OWLinstaller::addConfig($_id, 'mail', 'driver', 'RawSMTP');

OWLinstaller::addRights($_id
	,array(
		 'readpublic'		=> 'Allowed to see all content that has been either unmarked, or marked as public'
		,'readanonymous'	=> 'Allowed to see anonymous only content'
		,'readregistered'	=> 'Allowed to see all content that has been marked for registered users'
		,'modpassword'		=> 'Allowed to change own password'
		,'modemail'			=> 'Allowed to change own email address'
		,'modusername'		=> 'Allowed to change own username'
		,'moduserconfig'	=> 'Allowed to change own configuration settings'
		,'modgroupconfig'	=> 'Allowed to change configuration settings of the primary group'
		,'modapplconfig'	=> 'Allowed to change application config settings for OWL'
		,'addmembers'		=> 'Allowed to add members to the primary group'
		,'addgroups'		=> 'Allowed to add new groups to OWL'
		,'managegroupusers'	=> 'Allowed to manage users in the primary group'
		,'manageusers'		=> 'Allowed to manage all users in OWL'
		,'installapps'		=> 'Allowed to install new applications'
	)
);

OWLinstaller::addGroups($_id
	,array(
		 'nogroup'		=> 'Default group for anonymous users'
		,'standard'		=> 'Default group for all registered users'
		,'groupadmin'	=> 'Group administrators for the primary group'
		,'superadmin'	=> 'OWL super administrators'
	)
);

OWLinstaller::addGroupRights($_id
	,'nogroup'
	,array(
		 'readpublic'
		,'readanonymous'
	)
);

OWLinstaller::addGroupRights($_id
	,'standard'
	,array(
		 'readpublic'
		,'readregistered'
		,'modpassword'
		,'modemail'
		,'moduserconfig'
	)
);

OWLinstaller::addGroupRights($_id
	,'groupadmin'
	,array(
		 'addmembers'
		,'managegroupusers'
		,'modgroupconfig'
	)
);

OWLinstaller::addGroupRights($_id
	,'superadmin'
	,array(
		 'installapps'
		,'manageusers'
		,'addgroups'
		,'modapplconfig'
	)
);

OWLinstaller::addUser($_id, 'anonymous', '', '', 'nogroup');
OWLinstaller::addUser($_id, 'owl', 'owluser', 'owl@localhost.default', 'standard');
OWLinstaller::addUser($_id, 'root', 'owladmin', 'root@localhost.default', 'superadmin', array('groupadmin', 'standard'));

OWLinstaller::enableApplication($_id);
OWLloader::getClass('OWLrundown.php', OWL_ROOT);