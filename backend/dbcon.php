<?php
// dbcon.php
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Cloudinary\Cloudinary;

// 1) Realtime Database (Firebase)
$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/strp-1cbd6-firebase-adminsdk-75ypw-a834140836.json')
    ->withDatabaseUri('https://strp-1cbd6-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();

// 2) Cloudinary Configuration for Storage
function getCloudinaryInstance()
{
    return new Cloudinary([
        'cloud' => [
            'cloud_name' => 'dxpsvvhsj',   // Replace with your actual cloud name
            'api_key'    => '541964969741136',   // Replace with your actual API key
            'api_secret' => 'cOw7zAmFFcMhvKs8MoG7HlSEEX4',   // Replace with your actual API secret
        ],
        'url' => [
            'secure' => true
        ]
    ]);
}
?>
