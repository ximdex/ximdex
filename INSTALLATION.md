#Installing the Semantic Web CMS Ximdex

Ximdex CMS requires a Linux host, a Database server (MySQL or MariaDB) and Apache Web Server with PHP or NGINX with PHP-fpm.

You can install Ximdex CMS with Docker or using the web installer.

> Additionally, a fully manual or automated installation method can be found at install/XIMDEX_manual_installation_guidelines.md.


## A) Running Ximdex CMS using Docker composer

1. **Download Ximdex** package (https://github.com/XIMDEX/ximdex/archive/develop.zip) and expand it:
	```
  	wget --no-check-certificate https://github.com/XIMDEX/ximdex/archive/develop.zip
	unzip develop.zip
  	```
	
	or
        
	```
  	curl -L https://github.com/XIMDEX/ximdex/archive/develop.zip > develop.zip
	unzip develop.zip
  	```
	
	> You should end with a directory (i.e.: ximdex-develop) containing all the Ximdex files and directories.

2. Open a terminal where ximdex has been unzipped and run the command:

	
```
	# Launch it on the root of this repository
	docker compose up ximdex
	
	#(On OSX the command is docker-compose)
```


> That will create the containers for php, mysql and ximdex running on localhost:80. 

2. From your browser visit http://localhost to end the installation.
> Select DB as host for the database. The database should exist but empty. If the installation is aborted, please rm the .data directory at ximdex to clean it up. 


3. Play with Ximdex CMS at http://localhost using user Ximdex with the choosen password.



## B) Installing from Github with the Web Installer
When Apache2 and PHP are running with the requested packages you have to download Ximdex CMS, move it to the final destination on your document root (i.e.: /var/www/myximdex), set directory permissions and file owners (user/group) in harmony with your web server configuration and configure it using your web browser pointing to the desired URL (i.e.: http://yourhost/myximdex). You will need root access to a unix console to execute some steps...


### Requirements
*  A terminal with Telnet or SSH.
*  A user with enough permissions to create the directory where Ximdex CMS will be installed (under a document root for your web server)
*  Enough free space in the filesystem. See 'conf/diskspace.php' file for further information.
*  A database as MySQL Server (>= 5.1) or MariaDB (>=5.5) and a DB user that can create the Ximdex Database.
*  Apache2 web server with modules libapache2-mod-php5, apache-mpm-worker (recommended).
*  PHP (>= 5.2.5).
	*  and PHP modules: php5-xsl, php5-cli, php5-curl, php5-gd, php5-mysql, php-pear, php5-suhosin (recommended).
	*  To use the spelling checker in Xedit (our wysiwyg XML editor): php5-enchant module.
*  Other packages: wget.
*  A modern web browser (with javascript and cookies enabled): Firefox, Google Chrome, Safari, Opera, Microsoft Edge, …
*  An internet connection if you want to use some features as the automatic suggestion system based on ontologies (XOWL module), Dynamic Semantic Publishing (DSP) of semantic entities or to publish your content into the cloud.
*  Postfix or Sendmail (if you want to use notification by mail see 'conf/mail.php')

### Steps
1. **Download Ximdex** package (https://github.com/XIMDEX/ximdex/archive/develop.zip) and expand it:
	```
  	wget --no-check-certificate https://github.com/XIMDEX/ximdex/archive/develop.zip
	unzip develop.zip
  	```
	
	or
        
	```
  	curl -L https://github.com/XIMDEX/ximdex/archive/develop.zip > develop.zip
	unzip develop.zip
  	```
	
	> You should end with a directory (i.e.: ximdex-develop) containing all the Ximdex files and directories.

2. **Move it to your Web Server Document Root** with the name you want (i.e.: myximdex)

	```
	mv ximdex-develop /var/www/myximdex
	```
	You may need superuser privileges to do that! In that case type sudo before the command (i.e.: sudo mv ...)

	> So, in this example, 'myximdex' will be your Ximdex instance after installing it.

3. **Set File Owners and Permissions** (file owners should be those in use in your web server):
	```
	cd /var/www
	chown -R www-data:www-data myximdex
	cd myximdex
	chmod g+s data
	chmod g+s logs
	```

	You may need superuser privileges to do that! (Type sudo before the commands)

	> So, in this example, user and group 'www-data' are running the web processes as declared in the apache configuration file.

4. **Point your web browser** to your just installed Ximdex CMS instance URL (i.e.: http://YOURHOST/myximdex or http://localhost/myximdex) to run the configuration tool that will load the database, create users and install Ximdex's modules.

## C) Manual and Automated Installation methods
If the previous methods did not work, want to control all the steps or to automate the installation process, visit install/XIMDEX_manual_installation_guidelines.md file.

	
Thank you for installing Ximdex CMS. Please, contact us at help@ximdex.org if you need further assistance.
