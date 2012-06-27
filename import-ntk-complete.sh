#!/bin/bash


export VUFIND_HOME=/var/www/vufind/

#smaze index
#rm -f /data/fata5/vufind/solr/biblio/index/*
#vytvori prazdny index
#resp zkopiruje z /home/vufind/emptyindex -- trosku hack kvuli symlinku
#cp /home/vufind/emptyindex/* /data/fata5/vufind/solr/biblio/index/




./import-marc.sh -p /var/www/vufind/import/import_ntk.properties /var/www/vufind/ntk_data/bk_all_12_marc.mrc
./import-marc.sh -p /var/www/vufind/import/import_ntk.properties /var/www/vufind/ntk_data/se_all_12_marc.mrc 
./import-marc.sh -p /var/www/vufind/import/import_ntk.properties /var/www/vufind/ntk_data/hf_all_12_marc.mrc

  
./import-marc.sh -p /var/www/vufind/import/import_vscht.properties /var/www/vufind/ntk_data/vscht/ictmarc.mrc

./import-marc.sh -p /var/www/vufind/import/import_oz.properties /var/www/vufind/ntk_data/ozbws-modified.xml
./import-marc.sh -p /var/www/vufind/import/import_mp.properties /var/www/vufind/ntk_data/mpwbs-modified.xml
./import-marc.sh -p /var/www/vufind/import/import_di.properties /var/www/vufind/ntk_data/diwbs-modified.xml
 
