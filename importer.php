#!/usr/bin/php
<?php

$db_conf = include './phinx.php';

$arguments = getopt("d:f:v::h::");

if (array_key_exists('h', $arguments)) {
    exit("Run script --> php importer.php -d 'folder_path' -f 'file_name' -v 'verbose, print debug'");
}
echo "<pre>" . print_r($arguments, 1) . "</pre>" . PHP_EOL;
if (array_key_exists('v', $arguments)) echo "<pre>" . print_r($arguments, 1) . "</pre>" . PHP_EOL;

if (empty($arguments) && !array_key_exists('d', $arguments) && !array_key_exists('f', $arguments)) {
    exit("Could not get target folder (-f) value of command line option\n");
}

if (!is_dir($arguments['d'])) {
    exit("The directory ".$arguments['d']." doesn't exist\n");
}

if (!file_exists('./'.$arguments['d'] . DIRECTORY_SEPARATOR . $arguments['f'])) {
    exit("The file ".$arguments['d'] . DIRECTORY_SEPARATOR . $arguments['f']." doesn't exist\n");
}

$row = 1;
$notCompliantLines = [];
$compliantLines = [];
if (($handle = fopen($arguments['d'] . DIRECTORY_SEPARATOR . $arguments['f'], "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        if ($row > 1) {
            if (array_key_exists('v', $arguments)) echo "$num fields in line $row\n";
            $res = validateRow($data, $arguments);
            if (array_key_exists('error', $res)) {
                $notCompliantLines[$row] = $res;
            } else {
                $compliantLines[$row] = $res;
            }
            $row++;
            for ($c=0; $c < $num; $c++) {
                if (array_key_exists('v', $arguments)) echo $data[$c] . "\t";
            }
            if (array_key_exists('v', $arguments)) echo "\n";
        } else {
            $row++;
        }

    }
    fclose($handle);
}

if (array_key_exists('v', $arguments)) echo print_r($compliantLines, 1) . "\n";
if (array_key_exists('v', $arguments)) echo print_r($notCompliantLines, 1) . "\n";

putRows($compliantLines, $db_conf);

function validateRow($row, $arguments) {
    $outputRow = [];
    if (array_key_exists('v', $arguments)) echo print_r($row, 1) . "\n";
    //check name
    if (array_key_exists(0,$row) && $row[0] !== '' && $row[0] != null) {
        $outputRow['first_name'] = $row[0];
    } else {
        return ['error' => 'first_name ' . ' not compliant'];
    }
    if (array_key_exists(1,$row) && $row[1] !== '' && $row[1] != null) {
        $outputRow['last_name'] = $row[1];
    } else {
        return ['error' => 'last_name ' . ' not compliant'];
    }
    if (array_key_exists(2,$row) && $row[2] !== '' && $row[2] != null && filter_var($row[2], FILTER_VALIDATE_EMAIL)) {
        $outputRow['email'] = $row[2];
    } else {
        return ['error' => 'email ' . $row[2] .' not compliant'];
    }

    if (array_key_exists(3,$row) && $row[3] !== '' && $row[3] != null) {
        $outputRow['username'] = $row[3];
    } else {
        return ['error' =>  'username ' . ' not compliant'];
    }

    if (array_key_exists(4,$row) && $row[4] !== '' && $row[4] != null && preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.* )(?=.*[^a-zA-Z0-9]).{4,}/", $row[4])) {
        $outputRow['password'] = $row[4];
    } else {
        return ['error' => 'password ' . $row[4] .' not compliant'];
    }

    if (array_key_exists(5,$row) && $row[5] !== '' && $row[5] != null && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$row[5])) {
        if (validateDate($row[5], 'Y-m-d')) {
            $outputRow['birthday'] = $row[5];
        } else {
            return ['error' => 'birthday ' . ' not compliant'];
        }
    } else {
        return ['error' => 'birthday ' . ' not compliant'];
    }

    return $outputRow;

}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && strtolower($d->format($format)) === strtolower($date);
}

function putRows($rows, $db_conf) {

    $mysqli = new mysqli(
            $db_conf['environments']['development']["host"],
            $db_conf['environments']['development']["user"],
        $db_conf['environments']['development']["pass"],
        $db_conf['environments']['development']["name"]);

// Check connection
    if ($mysqli -> connect_errno) {
        echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
        exit();
    }

    foreach ($rows as $id => $row) {
        $firsName = mysqli_real_escape_string($mysqli,$row['first_name']);
        $lastName = mysqli_real_escape_string($mysqli,$row['last_name']);
        $now_date = new DateTime();
        $now_date = $now_date->format('Y-m-d');
        $sql = "INSERT INTO user (
                  first_name, 
                  last_name, 
                  email, 
                  username, 
                  password, 
                  birthday,
                  createdAt,
                  createdBy
                  )
                VALUES (
                        '$firsName', 
                        '$lastName', 
                        '".$row['email']."',
                        '".$row['username']."',
                        '".password_hash($row['password'], PASSWORD_DEFAULT)."',
                        '".$row['birthday']."',
                        '$now_date',
                        'importer')
                        ";

        echo $sql;
        if (mysqli_query($mysqli, $sql)) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($mysqli);
        }
    }


    $mysqli -> close();


}