Slim Test

Requisiti:
 - php > 8.2
 - composer  2.7.7
 - Docker e Docker compose

Per eseguire il progetto:
1. docker-compose up -d -build
2. composer install

Per importare il csv, inserirlo in /importCsv e lanciare il comando:
 -  php importer.php -d 'folder_path' -f 'file_name' -v (for verbose) [php importer.php -h for help]