#!/bin/bash

./import-marc.sh -p /var/www/vufind/import/marc_local_ntk.properties /var/www/vufind/ntk_data/bk_all_12_marc.mrc
./import-marc.sh -p /var/www/vufind/import/marc_local_ntk.properties /var/www/vufind/ntk_data/se_all_12_marc.mrc 
./import-marc.sh -p /var/www/vufind/import/marc_local_ntk.properties /var/www/vufind/ntk_data/hf_all_12_marc.mrc

  
./import-marc.sh -p /var/www/vufind/import/marc_local_vscht.properties /var/www/vufind/ntk_data/vscht/ictmarc.mrc

./import-marc.sh -p /var/www/vufind/import/marc_local_oz.properties /var/www/vufind/ntk_data/ozbws-modified.xml
./import-marc.sh -p /var/www/vufind/import/marc_local_mp.properties /var/www/vufind/ntk_data/mpwbs-modified.xml
./import-marc.sh -p /var/www/vufind/import/marc_local_oz.properties /var/www/vufind/ntk_data/diwbs-modified.xml
 
