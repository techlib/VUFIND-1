#!/bin/sh

# Harvest je možno zúžit parametrem, bez parametru (v tomto
# příkladu "katalog_ntk") budou zharvestovány všechny OAI-
# PHM endpointy určené v souboru oai.ini 
# Sklízení záznamů probíhá od data, které je obsahem soubo-
# ru last_harvest.txt ve složkách nazvaných podle sklizených
# OAI-PMH endpointů.
php harvest_oai.php katalog_ntk

# Smazání záznamů, které byly podle OAI-PMH indikovány jako
# smazané.
./batch-delete.sh katalog_ntk

# Sklizené záznamy jsou importovány do indexu VuFindu
./batch-import-marc.sh katalog_ntk

# Všechny zpracované soubory jak v případě mazání, tak v pří-
# padě importu jsou uloženy do podsložky "processed" složky
# pro každý nadefinovaný OAI-PMH endpoint. 
# Jednou za čas je můžeme promazat.
# rm -f katalog_ntk/processed/*

