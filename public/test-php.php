<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "Laravel Require: PHP >= 8.2<br>";
if (version_compare(phpversion(), '8.2.0', '<')) {
    echo "<span style='color: red; font-weight: bold;'>Error: PHP version is lower than 8.2. Laravel 11 requires PHP 8.2 or higher. Please upgrade the PHP version in your Hostinger panel.</span>";
} else {
    echo "<span style='color: green; font-weight: bold;'>PHP version is compatible!</span>";
}
