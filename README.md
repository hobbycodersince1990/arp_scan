# arp_scan
Is a tool to scan all devices in your local network.
A cronjob

## Prerequisites
Everything is done for UBUNTU Linux. However the same concept can be used for any Linux system.

## Install
See on the site:
http://raspberrypisolutions.wermescher.com/automatic-presence-detection-ubuntu/

## Assets

### MySQL user and database arp_scan
Herw all devices and all scans are stored.

### /wa directory
Contains all files which are necessary to scan the devices and store them into a local database.

### File: /etc/cron.d/arp-scan
here you create a cron-job to start 

### /var/www/html
Or any comparable project directory where you have apache configured to.
