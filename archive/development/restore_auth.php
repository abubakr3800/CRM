<?php
// Restore authentication
echo "Restoring authentication...\n";

if (file_exists('api/accounts_data.php.backup')) {
    copy('api/accounts_data.php.backup', 'api/accounts_data.php');
    unlink('api/accounts_data.php.backup');
    echo "Restored accounts_data.php\n";
}

if (file_exists('api/projects_data.php.backup')) {
    copy('api/projects_data.php.backup', 'api/projects_data.php');
    unlink('api/projects_data.php.backup');
    echo "Restored projects_data.php\n";
}

if (file_exists('api/contacts_data.php.backup')) {
    copy('api/contacts_data.php.backup', 'api/contacts_data.php');
    unlink('api/contacts_data.php.backup');
    echo "Restored contacts_data.php\n";
}

echo "Authentication restored!\n";
?>

