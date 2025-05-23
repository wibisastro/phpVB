# gov2framework
gov2 is a framework for develop government project

## Get the repo
- `clone the code from repository https://github.com/anggunwibowo/gov2framework.git`
- `or download the zip file from master branch`

## Set development environment
### Windows (Apache)
- `install packaged (XAMMP/WAMP/Laragon) recommended Laragon with PHP 8.1 version`
- `install composer refering to PHP 8.1`
- `clone or zip download from repository`
- `make sure 7-Zip already installed in your machine`
- `go to your app root C:/laragon/www/gov2framework`
  ```bash
  composer install
  ```
- `insert 3 lines into sergeytsalkov/meekrodb/db.class.php to line 92 :`
    ```php
    # gov2 conf
    public static $error_handler;
    public static $throw_exception_on_error;
    public static $throw_exception_on_nonsql_error;
    ```
- `run sql file in /gov2framework/apps/home/sql/sql.sql`
- `set credential (domain,host,user,pass,dbname) apps/home/xml/dsnSource.local.xml`
- `create local domain name e.g. gov2core.local`
   - `insert code in C:\Windows\System32\drivers\etc\hosts`
   ```bash
   127.0.0.1 gov2core.local
   ```
   - `defined configuration`
   ```conf
   <VirtualHost *:80>
    DocumentRoot "C:/laragon/www/gov2framework/public"
    ServerName gov2core.local
    <Directory "C:/laragon/www/gov2framework/public">
        AllowOverride All
        Require all granted
    </Directory>
	  ErrorLog "C:/laragon/www/gov2framework/logs/gov2core_error.log"
    CustomLog "C:/laragon/www/gov2framework/logs/gov2core_access.log" combined
    </VirtualHost>
    ```
- `start/restart your web service apache`
- `run domain gov2core.local in your browser`

### Linux Ubuntu (Apache)
- `install PHP 8.1 version, MySQL, Apache, Composer in your machine`
   ```bash
   sudo apt update && sudo apt install -y apache2 mysql-server php8.1 libapache2-mod-php8.1 php8.1-mysql php8.1-cli php8.1-curl php8.1-xml php8.1-mbstring php8.1-zip unzip curl && curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
   ```
- `enabled and start service`
  ```bash
  sudo systemctl enable apache2
  sudo systemctl start apache2
  sudo systemctl enable mysql
  sudo systemctl start mysql
  ```
- `verify installation`
  ```bash
  php -v
  sudo systemctl status apache2
  sudo systemctl status mysql
  composer -vvv about
  ```
- `make sure composer refering to PHP 8.1, if not run this code below :`
   ```bash
   nano ~/.bashrc
   ```
   - `add this line at the end then save and exit`
   ```conf
   alias composer="php8.1 /usr/local/bin/composer"
   ```
   - `clear cache composer and verify`
   ```bash
  hash -r
  composer clear-cache
  composer -vvv about 
  ```
- `check PHP 8.1 Extension are installed and enabled (check the list), if missing install it first`
   - `run this command if some of extention missing`
   ```bash
   sudo apt install php8.1-{common,cli,fpm,mbstring,xml,zip,curl,gd,sqlite3,opcache,intl,bcmath,mysqli,simplexml}
   ```
   - `enable all installed extensions automatically`
   ```bash
   sudo phpenmod -v 8.1 -s ALL *
   ```
- `clone or zip download from repository`
- `go to your app root /var/www/gov2framework`
  ```bash
  composer install
  ```
- `insert 3 lines into sergeytsalkov/meekrodb/db.class.php to line 92 :`
    ```php
    # gov2 conf
    public static $error_handler;
    public static $throw_exception_on_error;
    public static $throw_exception_on_nonsql_error;
    ```
- `run sql file in /gov2framework/apps/home/sql/sql.sql`
- `set credential (domain,host,user,pass,dbname) apps/home/xml/dsnSource.local.xml`
- `create local domain e.g. gov2core.local`
   ```bash
   # Define a new local domain
    sudo tee -a /etc/hosts <<EOF
    127.0.0.1 gov2core.local
    EOF
   ```
- `setup domain configuration`
   ```bash
    sudo touch /etc/apache2/sites-available/gov2core.local.conf
   ```
   ```bash
    sudo nano /etc/apache2/sites-available/gov2core.local.conf
   ```
   ```conf
    # domain configuration file gov2core.local.conf
    <VirtualHost *:80>
    ServerName gov2core.local
    ServerAlias www.gov2core.local
    DocumentRoot /var/www/gov2framework/public

    <Directory /var/www/gov2framework/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/gov2core_error.log
    CustomLog ${APACHE_LOG_DIR}/gov2core_access.log combined
    </VirtualHost>
   ```
   ```bash
    sudo a2enmod proxy proxy_http
    sudo a2ensite gov2core.local.conf
    sudo systemctl restart apache2
   ```
- `run domain gov2core.local in your browser`